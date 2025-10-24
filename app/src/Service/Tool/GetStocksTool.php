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
 * Tool IA pour récupérer l'état des stocks depuis CFI.
 *
 * Permet à l'agent IA de consulter les stocks avec filtres :
 * - Référence produit
 * - Stocks en alerte (quantité < quantiteMin)
 *
 * Retour structuré avec métadonnées pour "cartes preuve".
 *
 * Logging : Canal dédié 'tools' (pas 'chat')
 */
#[AsTool(
    name: 'get_stocks',
    description: 'Récupère l\'état des stocks (quantités, références, alertes) avec filtres optionnels. Retourne les détails complets avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 90)]
final readonly class GetStocksTool
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
     * Récupérer les stocks avec filtres.
     *
     * @param string|null $reference Référence produit (partiel ou complet)
     * @param bool|null   $enAlerte  Filtrer uniquement les stocks en alerte (quantité < min)
     *
     * @return array{count: int, stocks: array, metadata: array}
     */
    public function __invoke(
        ?string $reference = null,
        ?bool $enAlerte = null,
    ): array {
        $startTime = microtime(true);

        // Enregistrer l'appel du tool
        $this->toolCallCollector->addToolCall('get_stocks');

        try {
            // Récupérer utilisateur et tenant via le trait
            $auth = $this->getUserAndTenant($this->authService, $this->translator);
            if (isset($auth['error'])) {
                return $auth['error'];
            }

            ['user' => $user, 'tenant' => $tenant] = $auth;
            $idDivision = $tenant->getIdCfi();

            // Appel API CFI via service (avec cache 5min)
            $stocks = $this->stockApi->getStocks(
                idDivision: $idDivision,
                reference: $reference,
                enAlerte: $enAlerte,
            );

            // Formatter données pour l'agent IA (utilise toArray() pour mapping)
            $formattedStocks = array_map(
                function (StockDto $stock) {
                    $data = $stock->toArray();
                    $data['metadata'] = [
                        'source' => 'CFI API /Stocks/getStocks',
                        'link' => "/stocks/{$stock->id}",
                    ];

                    return $data;
                },
                $stocks
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Statistiques alertes
            $nbAlertes = count(array_filter($formattedStocks, fn ($s) => $s['isEnAlerte']));

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_stocks',
                params: ['reference' => $reference, 'enAlerte' => $enAlerte],
                result: ['count' => count($formattedStocks), 'nb_alertes' => $nbAlertes],
                durationMs: $durationMs
            );

            return [
                'success' => true,
                'count' => count($formattedStocks),
                'nb_alertes' => $nbAlertes,
                'stocks' => $formattedStocks,
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
            $this->logger->error('GetStocksTool: Erreur lors de la récupération des stocks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
                'params' => ['reference' => $reference, 'enAlerte' => $enAlerte],
            ]);

            // Message traduit générique pour utilisateur final (via agent IA)
            $userMessage = $this->translator->trans('stocks.error.fetch_failed', [], 'tools');

            return $this->errorResponse($userMessage);
        }
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
            'stocks' => [],
        ];
    }
}
