<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\LigneOperationDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\OperationApiService;
use App\Service\ToolCallCollector;
use App\Service\ToolResultCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

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
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_operation_stats',
    description: 'Calcule des statistiques agrégées sur les opérations (par type, statut, période). Retourne les métriques avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 80)]
final readonly class GetOperationStatsTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private OperationApiService $operationApi,
        private UserAuthenticationService $authService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        private ToolResultCollector $toolResultCollector,
        #[Autowire(service: 'monolog.logger.tools')]
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
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

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_operation_stats');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
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
                params: ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'groupBy' => $groupBy],
                result: ['count' => count($operations), 'stats_keys' => array_keys($stats)],
                durationMs: $durationMs
            );

            // Générer suggested_actions
            $suggestedActions = [];
            if (count($operations) > 0) {
                $suggestedActions[] = [
                    'label' => 'Voir les opérations détaillées',
                    'icon' => '📋',
                    'prompt' => 'Affiche-moi toutes les opérations',
                ];
            }

            // Générer table_data pour le composant DataTable
            $tableData = $this->generateTableData($stats, $groupBy, $suggestedActions);

            // Log KPI pour monitoring
            $this->logger->info('Tool executed successfully', [
                'tool_name' => 'get_operation_stats',
                'mode' => 'STATISTIQUES',
                'duration_ms' => $durationMs,
                'nb_operations' => count($operations),
                'group_by' => $groupBy,
                'user_id' => $user->getId(),
                'division_id' => $tenant->getIdCfi(),
            ]);

            $result = [
                'success' => true,
                'total_operations' => count($operations),
                'group_by' => $groupBy,
                'stats' => $stats,
                'suggested_actions' => $suggestedActions,
                'table_data' => $tableData,
                'metadata' => [
                    'source' => 'CFI API /Operations/getLignesOperations',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                    'period' => $dateDebut && $dateFin ? "{$dateDebut} → {$dateFin}" : 'Toutes périodes',
                    'mode' => 'STATISTIQUES',
                ],
            ];

            // Collecter le résultat pour transmission au frontend
            $this->toolResultCollector->addToolResult('get_operation_stats', $result);

            return $result;
        } catch (\Exception $e) {
            // Log détaillé pour développeurs (technique)
            $this->logger->error('GetOperationStatsTool: Erreur lors du calcul des statistiques', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => ['dateDebut' => $dateDebut, 'dateFin' => $dateFin, 'groupBy' => $groupBy],
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('operations.error.stats_failed', [], 'tools');

            return $this->errorResponse($userMessage);
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

    /**
     * Générer table_data structuré pour le composant DataTable.
     *
     * @param array<string, mixed>             $stats            Statistiques calculées
     * @param string                           $groupBy          Type de groupement
     * @param array<int, array<string, mixed>> $suggestedActions Actions suggérées pour liens cliquables
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, totalRow: array<string, mixed>, linkColumns: array<string, string>}
     */
    private function generateTableData(array $stats, string $groupBy, array $suggestedActions): array
    {
        // Adapter les en-têtes selon le type de groupement
        $headers = match ($groupBy) {
            'type' => ['TYPE', 'NB OPÉRATIONS', 'NB DESTINATAIRES', 'NB ENVOYÉS', 'TAUX ENVOI'],
            'statut' => ['STATUT', 'NB OPÉRATIONS'],
            'jour', 'semaine', 'mois' => ['PÉRIODE', 'NB OPÉRATIONS'],
            default => ['CATÉGORIE', 'VALEUR'],
        };

        // Générer les lignes selon le type de stats
        $rows = [];
        $totalOperations = 0;
        $totalDestinataires = 0;
        $totalEnvoyes = 0;

        foreach ($stats as $key => $value) {
            if ('type' === $groupBy && is_array($value)) {
                // Stats par type (avec détails)
                $rows[] = [
                    'type' => $key,
                    'count' => (string) $value['count'],
                    'nb_destinataires' => (string) $value['nb_destinataires'],
                    'nb_envoyes' => (string) $value['nb_envoyes'],
                    'taux_envoi' => "{$value['taux_envoi']}%",
                ];
                $totalOperations += $value['count'];
                $totalDestinataires += $value['nb_destinataires'];
                $totalEnvoyes += $value['nb_envoyes'];
            } else {
                // Stats simples (statut, période)
                $rows[] = [
                    'categorie' => $key,
                    'count' => (string) $value,
                ];
                $totalOperations += is_int($value) ? $value : 0;
            }
        }

        // Ligne de total
        if ('type' === $groupBy) {
            $tauxEnvoiTotal = $totalDestinataires > 0
                ? round(($totalEnvoyes / $totalDestinataires) * 100, 1)
                : 0.0;

            $totalRow = [
                'label' => 'TOTAL',
                'total_operations' => (string) $totalOperations,
                'total_destinataires' => (string) $totalDestinataires,
                'total_envoyes' => (string) $totalEnvoyes,
                'taux_envoi_global' => "{$tauxEnvoiTotal}%",
            ];
        } else {
            $totalRow = [
                'label' => 'TOTAL',
                'total_operations' => (string) $totalOperations,
            ];
        }

        // Colonnes cliquables (pas de liens pour les statistiques)
        $linkColumns = [];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
        ];
    }
}
