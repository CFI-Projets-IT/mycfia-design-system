<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\StockDto;
use App\Service\AiLoggerService;
use App\Service\Api\StockApiService;
use App\Service\Cfi\CfiTenantService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\AI\Toolbox\Attribute\AsTool;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

/**
 * Tool IA pour récupérer l'état des stocks depuis CFI.
 *
 * Permet à l'agent IA de consulter les stocks avec filtres :
 * - Référence produit
 * - Stocks en alerte (quantité < quantiteMin)
 *
 * Retour structuré avec métadonnées pour "cartes preuve".
 */
#[AsTool(
    name: 'get_stocks',
    description: 'Récupère l\'état des stocks (quantités, références, alertes) avec filtres optionnels. Retourne les détails complets avec sources CFI et métadonnées.'
)]
#[AsTaggedItem(priority: 90)]
final readonly class GetStocksTool
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
            $stocks = $this->stockApi->getStocks(
                idDivision: $idDivision,
                reference: $reference,
                enAlerte: $enAlerte,
            );

            // Formatter données pour l'agent IA
            $formattedStocks = array_map(
                fn (StockDto $stock) => [
                    'id' => $stock->id,
                    'reference' => $stock->reference,
                    'designation' => $stock->designation,
                    'quantite' => $stock->quantite,
                    'quantiteMin' => $stock->quantiteMin,
                    'quantiteMax' => $stock->quantiteMax,
                    'unite' => $stock->unite,
                    'dateDerniereMAJ' => $stock->dateDerniereMAJ?->format('Y-m-d H:i:s'),
                    'isEnAlerte' => $stock->isEnAlerte(),
                    'metadata' => [
                        'source' => 'CFI API /Stocks/getStocks',
                        'dateMAJ' => $stock->dateDerniereMAJ?->format('Y-m-d H:i:s') ?? 'N/A',
                        'link' => "/stocks/{$stock->id}",
                    ],
                ],
                $stocks
            );

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Statistiques alertes
            $nbAlertes = count(array_filter($formattedStocks, fn ($s) => $s['isEnAlerte']));

            // Log tool call
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'get_stocks',
                input: ['reference' => $reference, 'enAlerte' => $enAlerte],
                output: ['count' => count($formattedStocks), 'nb_alertes' => $nbAlertes],
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
            $this->logger->error('GetStocksTool: Erreur lors de la récupération des stocks', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->errorResponse('Erreur lors de la récupération des stocks : '.$e->getMessage());
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
