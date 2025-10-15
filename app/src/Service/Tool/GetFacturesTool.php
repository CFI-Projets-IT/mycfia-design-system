<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\FactureDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\FacturationApiService;
use App\Service\ToolCallCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA spécialisé pour récupérer les FACTURES depuis CFI.
 *
 * Utilise l'endpoint CFI /Facturations/getFacturations pour récupérer
 * les vraies factures avec détails complets (montants HT/TTC, lignes, types).
 *
 * Responsabilités :
 * - Récupérer facturations mensuelles avec filtres temporels
 * - Retour structuré avec métadonnées CFI
 * - Cache 5 minutes via FacturationApiService
 *
 * Structure retournée :
 * - Facturations mensuelles (id, mois, date mise à disposition)
 * - Factures individuelles (montants, adresse, commande, demandeur)
 * - Lignes de détail (libellé, quantité, montant, TVA)
 */
#[AsTool(
    name: 'get_factures',
    description: 'Récupère les factures CFI avec détails complets (montants HT/TTC, lignes de facturation). Filtrage par période. Retourne les facturations mensuelles avec toutes les factures et lignes associées.'
)]
#[AsTaggedItem(priority: 95)]
final readonly class GetFacturesTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private FacturationApiService $facturationApi,
        private UserAuthenticationService $authService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Récupérer les factures avec filtres temporels.
     *
     * @param string|null $dateDebut Date de début (format ISO 8601, ex: 2024-10-14T00:00:00Z ou YYYY-MM-DD)
     * @param string|null $dateFin   Date de fin (format ISO 8601, ex: 2025-10-14T23:59:59Z ou YYYY-MM-DD)
     *
     * @return array{success: bool, count: int, facturations: array, metadata: array}
     */
    public function __invoke(
        ?string $dateDebut = null,
        ?string $dateFin = null,
    ): array {
        $startTime = microtime(true);

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_factures');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
            $idDivision = $tenant->getIdCfi();

            // Convertir dates YYYY-MM-DD en ISO 8601 si nécessaire
            $debut = $dateDebut ? $this->normalizeDate($dateDebut) : null;
            $fin = $dateFin ? $this->normalizeDate($dateFin) : null;

            // Appel API CFI via service (avec cache 5min)
            $facturations = $this->facturationApi->getFacturations(
                idDivision: $idDivision,
                debut: $debut,
                fin: $fin,
            );

            // Formatter données pour l'agent IA
            $formattedFacturations = array_map(
                fn (FactureDto $facturation) => [
                    'id' => $facturation->id,
                    'moisFacturation' => $facturation->moisFacturation->format('Y-m'),
                    'dateMiseADispo' => $facturation->dateMiseADispo->format('Y-m-d'),
                    'nb_factures' => count($facturation->factures),
                    'montant_total_ht' => $facturation->getMontantTotalHT(),
                    'montant_total_ttc' => $facturation->getMontantTotalTTC(),
                    'factures' => array_map(
                        fn ($facture) => [
                            'id' => $facture->id,
                            'nomCommande' => $facture->nomCommande,
                            'demandeur' => $facture->demandeur,
                            'montantHT' => $facture->montantHT,
                            'montantTTC' => $facture->montantTTC,
                            'nb_lignes' => count($facture->lignes),
                        ],
                        $facturation->factures
                    ),
                ],
                $facturations
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_factures',
                params: ['dateDebut' => $dateDebut, 'dateFin' => $dateFin],
                result: ['count' => count($formattedFacturations)],
                durationMs: $durationMs
            );

            return [
                'success' => true,
                'count' => count($formattedFacturations),
                'facturations' => $formattedFacturations,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Facturations/getFacturations',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];
        } catch (\Exception $e) {
            // Log détaillé pour développeurs (technique)
            $this->logger->error('GetFacturesTool: Erreur lors de la récupération des factures', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => ['dateDebut' => $dateDebut, 'dateFin' => $dateFin],
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('operations.error.fetch_failed', [], 'tools');

            return $this->errorResponse($userMessage);
        }
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * @return array{success: false, error: string, count: 0, facturations: array<empty, empty>}
     */
    private function errorResponse(string $message): array
    {
        return [
            'success' => false,
            'error' => $message,
            'count' => 0,
            'facturations' => [],
        ];
    }

    /**
     * Normaliser une date en format ISO 8601 pour l'API CFI.
     *
     * Accepte :
     * - Format ISO 8601 complet : "2024-10-14T15:04:29.547Z"
     * - Format date simple : "2024-10-14"
     *
     * Retourne toujours un format ISO 8601 complet compatible CFI.
     */
    private function normalizeDate(string $date): string
    {
        // Si déjà au format ISO 8601 avec heure, retourner tel quel
        if (str_contains($date, 'T')) {
            return $date;
        }

        // Sinon, convertir YYYY-MM-DD en ISO 8601 avec heure 00:00:00
        try {
            $dateTime = new \DateTime($date);

            return $dateTime->format('Y-m-d\TH:i:s.v\Z');
        } catch (\Exception $e) {
            // Fallback : retourner la date telle quelle
            return $date;
        }
    }
}
