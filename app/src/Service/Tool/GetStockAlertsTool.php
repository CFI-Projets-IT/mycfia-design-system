<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\StockDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\StockApiService;
use App\Service\ToolCallCollector;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Tool IA pour identifier les stocks en alerte (quantité < quantiteMin).
 *
 * Version simplifiée de GetStocksTool, focalisée uniquement sur les alertes.
 * Retourne les stocks nécessitant une attention urgente.
 *
 * Retour structuré avec métadonnées pour "cartes preuve".
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_stock_alerts',
    description: 'Identifie les stocks en alerte (quantité < quantiteMin). Retourne uniquement les stocks nécessitant une attention urgente avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 85)]
final readonly class GetStockAlertsTool
{
    use AuthenticatedToolTrait;

    public function __construct(
        private StockApiService $stockApi,
        private UserAuthenticationService $authService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        #[Autowire(service: 'monolog.logger.tools')]
        private LoggerInterface $logger,
        private TranslatorInterface $translator,
    ) {
    }

    /**
     * Récupérer les stocks en alerte uniquement.
     *
     * @param int|null $limit Limiter le nombre de résultats retournés (défaut: tous)
     *
     * @return array{count: int, alerts: array, metadata: array}
     */
    public function __invoke(?int $limit = null): array
    {
        $startTime = microtime(true);

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_stock_alerts');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
            $idDivision = $tenant->getIdCfi();

            // Appel API CFI via service (avec cache 5min) - Filtrer alertes uniquement
            $stocks = $this->stockApi->getStocks(
                idDivision: $idDivision,
                enAlerte: true,
            );

            // Formatter données pour l'agent IA (utilise toArray() pour mapping)
            $formattedAlerts = array_map(
                function (StockDto $stock) {
                    $data = $stock->toArray();
                    $data['deficit'] = ($stock->stockMinimum ?? 0) - ($stock->qte ?? 0);
                    $data['niveau_alerte'] = $this->getNiveauAlerte($stock);
                    $data['metadata'] = [
                        'source' => 'CFI API /Stocks/getStocks',
                        'link' => "/stocks/{$stock->id}",
                    ];

                    return $data;
                },
                $stocks
            );

            // Trier par niveau d'alerte (critique en premier)
            usort($formattedAlerts, fn ($a, $b) => $b['deficit'] <=> $a['deficit']);

            // Appliquer la limite si spécifiée
            $totalCount = count($formattedAlerts);
            if (null !== $limit && $limit > 0) {
                $formattedAlerts = array_slice($formattedAlerts, 0, $limit);
            }

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_stock_alerts',
                params: ['limit' => $limit],
                result: ['count' => count($formattedAlerts), 'total' => $totalCount],
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
            // Log détaillé pour développeurs (technique)
            $this->logger->error('GetStockAlertsTool: Erreur lors de la récupération des alertes', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('stocks.error.alerts_failed', [], 'tools');

            return $this->errorResponse($userMessage);
        }
    }

    /**
     * Déterminer le niveau d'alerte d'un stock.
     */
    private function getNiveauAlerte(StockDto $stock): string
    {
        if (null === $stock->stockMinimum || null === $stock->qte) {
            return 'N/A';
        }

        $deficit = $stock->stockMinimum - $stock->qte;
        $pourcentage = $stock->stockMinimum > 0 ? ($deficit / $stock->stockMinimum) * 100 : 0;

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
