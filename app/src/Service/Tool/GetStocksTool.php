<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\StockDto;
use App\Security\UserAuthenticationService;
use App\Service\AiLoggerService;
use App\Service\Api\StockApiService;
use App\Service\ToolCallCollector;
use App\Service\ToolResultCollector;
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
        private ToolResultCollector $toolResultCollector,
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

            // Générer actions suggérées pour liens cliquables
            $suggestedActions = [];

            // Actions contextuelles selon les filtres appliqués
            if (null === $enAlerte && $nbAlertes > 0) {
                $suggestedActions[] = [
                    'type' => 'stocks_alertes',
                    'prompt' => 'Montre-moi uniquement les stocks en alerte',
                ];
            }

            if (null !== $enAlerte) {
                $suggestedActions[] = [
                    'type' => 'stocks_tous',
                    'prompt' => 'Montre-moi tous les stocks',
                ];
            }

            if (null !== $reference) {
                $suggestedActions[] = [
                    'type' => 'stocks_tous',
                    'prompt' => 'Montre-moi tous les stocks sans filtre',
                ];
            }

            // Générer table_data structuré pour DataTable component
            $tableData = $this->generateTableData($stocks, $suggestedActions);

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_stocks',
                params: ['reference' => $reference, 'enAlerte' => $enAlerte],
                result: ['count' => count($formattedStocks), 'nb_alertes' => $nbAlertes],
                durationMs: $durationMs
            );

            // Log KPI pour monitoring
            $this->logger->info('Tool executed successfully', [
                'tool_name' => 'get_stocks',
                'mode' => 'LISTE',
                'duration_ms' => $durationMs,
                'nb_stocks' => count($formattedStocks),
                'nb_alertes' => $nbAlertes,
                'user_id' => $user->getId(),
                'division_id' => $tenant->getIdCfi(),
            ]);

            $result = [
                'success' => true,
                'count' => count($formattedStocks),
                'nb_alertes' => $nbAlertes,
                'stocks' => $formattedStocks,
                'suggested_actions' => $suggestedActions,
                'table_data' => $tableData,
                'metadata' => [
                    'source' => 'CFI API',
                    'endpoint' => '/Stocks/getStocks',
                    'cache_ttl' => '5 minutes',
                    'division' => $tenant->getNom(),
                    'duration_ms' => $durationMs,
                    'mode' => 'LISTE',
                ],
            ];

            // Collecter le résultat pour transmission au frontend
            $this->toolResultCollector->addToolResult('get_stocks', $result);

            return $result;
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

    /**
     * Générer table_data structuré pour le composant DataTable.
     *
     * @param StockDto[]                       $stocks           Liste des stocks
     * @param array<int, array<string, mixed>> $suggestedActions Actions suggérées pour liens cliquables
     *
     * @return array{headers: array<int, string>, rows: array<int, array<string, mixed>>, totalRow: array<string, mixed>, linkColumns: array<string, string>}
     */
    private function generateTableData(array $stocks, array $suggestedActions): array
    {
        // En-têtes du tableau
        $headers = [
            'ID',
            'RÉFÉRENCE',
            'DÉSIGNATION',
            'QUANTITÉ ACTUELLE',
            'QUANTITÉ MINIMALE',
            'STATUT',
        ];

        // Lignes du tableau
        $rows = [];
        $totalQuantite = 0;
        $nbAlertes = 0;

        foreach ($stocks as $stock) {
            $isEnAlerte = $stock->isEnAlerte();
            $statutLabel = $isEnAlerte ? '⚠️ ALERTE' : '✅ OK';

            // Utiliser refStockage ou générer une référence par défaut
            $reference = $stock->refStockage ?? sprintf('STOCK-%d', $stock->id);

            $rows[] = [
                'id' => (string) $stock->id,
                'reference' => $reference,
                'designation' => $stock->nom ?? '',
                'quantite_actuelle' => null !== $stock->qte ? (string) $stock->qte : '0',
                'quantite_min' => null !== $stock->stockMinimum ? (string) $stock->stockMinimum : '0',
                'statut' => $statutLabel,
            ];

            $totalQuantite += $stock->qte ?? 0;

            if ($isEnAlerte) {
                ++$nbAlertes;
            }
        }

        // Ligne de total
        $totalRow = [
            'label' => 'TOTAL',
            'nb_produits' => (string) count($stocks),
            'quantite_totale' => (string) $totalQuantite,
            'nb_alertes' => "⚠️ {$nbAlertes} alerte(s)",
        ];

        // Colonnes cliquables (pas de mode DÉTAIL pour les stocks)
        $linkColumns = [];

        return [
            'headers' => $headers,
            'rows' => $rows,
            'totalRow' => $totalRow,
            'linkColumns' => $linkColumns,
        ];
    }
}
