<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\StockDto;
use App\Service\AiLoggerService;
use App\Service\Api\StockApiService;
use App\Service\Cfi\CfiTenantService;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Tool IA pour identifier les stocks en alerte (quantité < quantiteMin).
 *
 * Version simplifiée de GetStocksTool, focalisée uniquement sur les alertes.
 * Retourne les stocks nécessitant une attention urgente.
 *
 * Retour structuré avec métadonnées pour "cartes preuve".
 */
#[AsTool(
    name: 'get_stock_alerts',
    description: 'Identifie les stocks en alerte (quantité < quantiteMin). Retourne uniquement les stocks nécessitant une attention urgente avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 85)]
final readonly class GetStockAlertsTool
{
    public function __construct(
        private StockApiService $stockApi,
        private CfiTenantService $tenantService,
        private AiLoggerService $aiLogger,
        private Security $security,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupérer les stocks en alerte uniquement.
     *
     * @return array{count: int, alerts: array, metadata: array}
     */
    public function __invoke(): array
    {
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

            // Appel API CFI via service (avec cache 5min) - Filtrer alertes uniquement
            $stocks = $this->stockApi->getStocks(
                idDivision: $idDivision,
                enAlerte: true,
            );

            // Formatter données pour l'agent IA
            $formattedAlerts = array_map(
                fn (StockDto $stock) => [
                    'id' => $stock->id,
                    'reference' => $stock->reference,
                    'designation' => $stock->designation,
                    'quantite' => $stock->quantite,
                    'quantiteMin' => $stock->quantiteMin,
                    'quantiteMax' => $stock->quantiteMax,
                    'unite' => $stock->unite,
                    'dateDerniereMAJ' => $stock->dateDerniereMAJ?->format('Y-m-d H:i:s'),
                    'deficit' => ($stock->quantiteMin ?? 0) - $stock->quantite,
                    'niveau_alerte' => $this->getNiveauAlerte($stock),
                    'metadata' => [
                        'source' => 'CFI API /Stocks/getStocks',
                        'dateMAJ' => $stock->dateDerniereMAJ?->format('Y-m-d H:i:s') ?? 'N/A',
                        'link' => "/stocks/{$stock->id}",
                    ],
                ],
                $stocks
            );

            // Trier par niveau d'alerte (critique en premier)
            usort($formattedAlerts, fn ($a, $b) => $b['deficit'] <=> $a['deficit']);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_stock_alerts',
                params: [],
                result: ['count' => count($formattedAlerts)],
                durationMs: $durationMs
            );

            return [
                'success' => true,
                'count' => count($formattedAlerts),
                'niveau_urgence' => $this->getNiveauUrgenceGlobal($formattedAlerts),
                'alerts' => $formattedAlerts,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Stocks/getStocks',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                ],
            ];
        } catch (\Exception $e) {
            $this->logger->error('GetStockAlertsTool: Erreur lors de la récupération des alertes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Erreur lors de la récupération des alertes : '.$e->getMessage());
        }
    }

    /**
     * Déterminer le niveau d'alerte d'un stock.
     */
    private function getNiveauAlerte(StockDto $stock): string
    {
        if (null === $stock->quantiteMin) {
            return 'N/A';
        }

        $deficit = $stock->quantiteMin - $stock->quantite;
        $pourcentage = $stock->quantiteMin > 0 ? ($deficit / $stock->quantiteMin) * 100 : 0;

        return match (true) {
            $pourcentage >= 50 => 'Critique',
            $pourcentage >= 25 => 'Élevé',
            default => 'Modéré',
        };
    }

    /**
     * Déterminer le niveau d'urgence global.
     */
    private function getNiveauUrgenceGlobal(array $alerts): string
    {
        if (0 === count($alerts)) {
            return 'Aucune alerte';
        }

        $critiques = count(array_filter($alerts, fn ($a) => 'Critique' === $a['niveau_alerte']));
        $eleves = count(array_filter($alerts, fn ($a) => 'Élevé' === $a['niveau_alerte']));

        return match (true) {
            $critiques > 0 => 'Attention urgente requise',
            $eleves > 0 => 'Surveillance recommandée',
            default => 'Surveillance normale',
        };
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
            'count' => 0,
            'alerts' => [],
        ];
    }
}
