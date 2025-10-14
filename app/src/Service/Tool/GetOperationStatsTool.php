<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\LigneOperationDto;
use App\Service\AiLoggerService;
use App\Service\Api\OperationApiService;
use App\Service\Cfi\CfiTenantService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\AI\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Tool IA pour calculer des statistiques sur les opérations.
 *
 * Calcule des métriques agrégées :
 * - Nombre d'opérations par type
 * - Taux d'envoi (nbEnvoyes / nbDestinataires)
 * - Répartition par statut
 * - Statistiques temporelles (par jour, semaine, mois)
 *
 * Retour structuré avec métadonnées pour "cartes preuve".
 */
#[AsTool(
    name: 'get_operation_stats',
    description: 'Calcule des statistiques agrégées sur les opérations (par type, statut, période). Retourne les métriques avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 80)]
final readonly class GetOperationStatsTool
{
    public function __construct(
        private OperationApiService $operationApi,
        private CfiTenantService $tenantService,
        private AiLoggerService $aiLogger,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Calculer des statistiques sur les opérations.
     *
     * @param string|null $dateDebut Date de début (format YYYY-MM-DD)
     * @param string|null $dateFin   Date de fin (format YYYY-MM-DD)
     * @param string      $groupBy   Grouper par : 'type', 'statut', 'jour', 'semaine', 'mois' (défaut: 'type')
     *
     * @return array{stats: array, metadata: array}
     */
    public function __invoke(
        ?string $dateDebut = null,
        ?string $dateFin = null,
        string $groupBy = 'type',
    ): array {
        $startTime = microtime(true);

        try {
            // Récupérer utilisateur et tenant
            $user = $this->security->getUser();
            if (null === $user) {
                return $this->errorResponse('Utilisateur non authentifié');
            }

            $tenant = $this->tenantService->getTenantActuel($user);
            if (null === $tenant) {
                return $this->errorResponse('Division non trouvée');
            }

            $idDivision = $tenant->getIdCfi();

            // Appel API CFI via service (avec cache 5min)
            $operations = $this->operationApi->getLignesOperations(
                idDivision: $idDivision,
                dateDebut: $dateDebut,
                dateFin: $dateFin,
            );

            // Calculer statistiques selon groupBy
            $stats = match ($groupBy) {
                'type' => $this->statsByType($operations),
                'statut' => $this->statsByStatut($operations),
                'jour' => $this->statsByPeriod($operations, 'Y-m-d'),
                'semaine' => $this->statsByPeriod($operations, 'Y-W'),
                'mois' => $this->statsByPeriod($operations, 'Y-m'),
                default => $this->statsByType($operations),
            };

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_operation_stats',
                input: ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'groupBy' => $groupBy],
                output: ['count' => count($operations), 'stats_keys' => array_keys($stats)],
                durationMs: $durationMs
            );

            return [
                'success' => true,
                'total_operations' => count($operations),
                'group_by' => $groupBy,
                'stats' => $stats,
                'metadata' => [
                    'source' => 'CFI API /Campagnes/getLignesCampagnes',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                    'period' => $dateDebut && $dateFin ? "{$dateDebut} → {$dateFin}" : 'Toutes périodes',
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('GetOperationStatsTool: Erreur lors du calcul des statistiques', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Erreur lors du calcul des statistiques : '.$e->getMessage());
        }
    }

    /**
     * Calculer statistiques par type d'opération.
     *
     * @param LigneOperationDto[] $operations
     *
     * @return array<string, array{count: int, nb_destinataires: int, nb_envoyes: int, taux_envoi: float}>
     */
    private function statsByType(array $operations): array
    {
        $stats = [];

        foreach ($operations as $op) {
            $type = $op->type;

            if (! isset($stats[$type])) {
                $stats[$type] = [
                    'count' => 0,
                    'nb_destinataires' => 0,
                    'nb_envoyes' => 0,
                ];
            }

            ++$stats[$type]['count'];
            $stats[$type]['nb_destinataires'] += $op->nbDestinataires ?? 0;
            $stats[$type]['nb_envoyes'] += $op->nbEnvoyes ?? 0;
        }

        // Calculer taux d'envoi
        foreach ($stats as $type => &$data) {
            $data['taux_envoi'] = $data['nb_destinataires'] > 0
                ? round(($data['nb_envoyes'] / $data['nb_destinataires']) * 100, 2)
                : 0.0;
        }

        return $stats;
    }

    /**
     * Calculer statistiques par statut.
     *
     * @param LigneOperationDto[] $operations
     *
     * @return array<string, int>
     */
    private function statsByStatut(array $operations): array
    {
        $stats = [];

        foreach ($operations as $op) {
            $statut = $op->statut ?? 'Non défini';
            $stats[$statut] = ($stats[$statut] ?? 0) + 1;
        }

        return $stats;
    }

    /**
     * Calculer statistiques par période (jour, semaine, mois).
     *
     * @param LigneOperationDto[] $operations
     * @param string              $format     Format date PHP ('Y-m-d', 'Y-W', 'Y-m')
     *
     * @return array<string, int>
     */
    private function statsByPeriod(array $operations, string $format): array
    {
        $stats = [];

        foreach ($operations as $op) {
            $period = $op->dateCreation->format($format);
            $stats[$period] = ($stats[$period] ?? 0) + 1;
        }

        // Trier par période
        ksort($stats);

        return $stats;
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * @return array{success: bool, error: string}
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'stats' => [],
        ];
    }
}
