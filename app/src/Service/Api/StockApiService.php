<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\Cfi\StockDto;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiTokenContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service API pour récupérer les stocks depuis CFI.
 *
 * Cache : 5 minutes (données fréquemment modifiées)
 * Filtrage automatique par tenant (idDivision)
 *
 * Logging : Canal dédié 'api_services' (pas 'cfi_api')
 */
final readonly class StockApiService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_PREFIX = 'cfi.stocks';
    private const ENDPOINT = '/Stocks/getStocks';

    public function __construct(
        private CfiApiService $cfiApi,
        private CfiTokenContext $cfiTokenContext,
        private CacheInterface $cache,
        #[Autowire(service: 'monolog.logger.api_services')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupérer la liste des stocks filtrés par division.
     *
     * Cache : 5min par combinaison de filtres
     * Filtrage tenant automatique (idDivision)
     *
     * @return StockDto[]
     */
    public function getStocks(
        int $idDivision,
        ?string $reference = null,
        ?bool $enAlerte = null,
    ): array {
        // Générer clé cache unique basée sur les filtres
        $cacheKey = $this->buildCacheKey($idDivision, $reference, $enAlerte);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($idDivision, $reference, $enAlerte): array {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->info('StockApiService: Cache MISS - Appel API CFI', [
                'cache_key' => $item->getKey(),
                'id_division' => $idDivision,
            ]);

            // Récupérer le token d'authentification (contexte sync ou async)
            $jeton = $this->cfiTokenContext->getToken();
            if (null === $jeton) {
                $this->logger->error('StockApiService: Token CFI manquant ou expiré');

                return [];
            }

            // Construire le corps de la requête
            $body = [
                'idDivision' => $idDivision,
            ];

            if (null !== $reference) {
                $body['reference'] = $reference;
            }

            // Appel API CFI
            $response = $this->cfiApi->post(self::ENDPOINT, $body, $jeton);

            // Mapper les données brutes vers DTOs
            // IMPORTANT : CFI retourne un tableau direct, pas {data: [...]}
            $stocks = [];
            $dataArray = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;

            foreach ($dataArray as $item) {
                if (! is_array($item)) {
                    continue;
                }

                try {
                    $stock = StockDto::fromApiData($item);

                    // Filtrer par alerte si demandé (filtrage côté application)
                    if (null !== $enAlerte && $stock->isEnAlerte() !== $enAlerte) {
                        continue;
                    }

                    $stocks[] = $stock;
                } catch (\Exception $e) {
                    $this->logger->warning('StockApiService: Erreur mapping stock', [
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            $this->logger->info('StockApiService: Récupération réussie', [
                'id_division' => $idDivision,
                'nb_stocks' => count($stocks),
            ]);

            return $stocks;
        });
    }

    /**
     * Construire une clé de cache unique basée sur les filtres.
     */
    private function buildCacheKey(
        int $idDivision,
        ?string $reference,
        ?bool $enAlerte,
    ): string {
        $parts = [
            self::CACHE_KEY_PREFIX,
            $idDivision,
            $reference ?? 'all',
            true === $enAlerte ? 'alerte' : (false === $enAlerte ? 'no-alerte' : 'all'),
        ];

        return implode('.', $parts);
    }

    /**
     * Invalider le cache pour une division spécifique.
     *
     * Utile après une modification de stock (hors scope Sprint S1, mais préparé)
     */
    public function invalidateCache(int $idDivision): void
    {
        // TODO: Implémenter invalidation par tag si besoin (Symfony Cache Tags)
        // Pour l'instant, expiration naturelle après 5min
        $this->logger->info('StockApiService: Invalidation cache demandée', [
            'id_division' => $idDivision,
            'note' => 'Expiration naturelle 5min - Tags non implémentés',
        ]);
    }
}
