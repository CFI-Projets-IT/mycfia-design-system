<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\Cfi\LigneOperationDto;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiTokenContext;
use Psr\Log\LoggerInterface;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service API pour récupérer les opérations depuis CFI.
 *
 * Cache : 5 minutes (données fréquemment modifiées)
 * Filtrage automatique par tenant (idDivision)
 */
final readonly class OperationApiService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_PREFIX = 'cfi.operations';
    private const ENDPOINT = '/Campagnes/getLignesCampagnes';

    public function __construct(
        private CfiApiService $cfiApi,
        private CfiTokenContext $cfiTokenContext,
        private CacheInterface $cache,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupérer la liste des lignes d'opérations filtrées par division.
     *
     * Cache : 5min par combinaison de filtres
     * Filtrage tenant automatique (idDivision)
     *
     * @return LigneOperationDto[]
     */
    public function getLignesOperations(
        int $idDivision,
        ?string $type = null,
        ?string $dateDebut = null,
        ?string $dateFin = null,
        ?string $statut = null,
    ): array {
        // Générer clé cache unique basée sur les filtres
        $cacheKey = $this->buildCacheKey($idDivision, $type, $dateDebut, $dateFin, $statut);

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($idDivision, $type, $dateDebut, $dateFin, $statut): array {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->info('OperationApiService: Cache MISS - Appel API CFI', [
                'cache_key' => $item->getKey(),
                'id_division' => $idDivision,
            ]);

            // Récupérer le token d'authentification (contexte sync ou async)
            $jeton = $this->cfiTokenContext->getToken();
            if (null === $jeton) {
                $this->logger->error('OperationApiService: Token CFI manquant ou expiré');

                return [];
            }

            // Construire le corps de la requête
            $body = [
                'idDivision' => $idDivision,
            ];

            if (null !== $type) {
                $body['type'] = $type;
            }
            if (null !== $dateDebut) {
                $body['dateDebut'] = $dateDebut;
            }
            if (null !== $dateFin) {
                $body['dateFin'] = $dateFin;
            }
            if (null !== $statut) {
                $body['statut'] = $statut;
            }

            // Appel API CFI
            $response = $this->cfiApi->post(self::ENDPOINT, $body, $jeton);

            // Mapper les données brutes vers DTOs
            // IMPORTANT : CFI retourne un tableau direct, pas {data: [...]}
            $operations = [];
            $dataArray = isset($response['data']) && is_array($response['data']) ? $response['data'] : $response;

            foreach ($dataArray as $item) {
                if (! is_array($item)) {
                    continue;
                }

                try {
                    $operations[] = LigneOperationDto::fromApiData($item);
                } catch (\Exception $e) {
                    $this->logger->warning('OperationApiService: Erreur mapping opération', [
                        'item' => $item,
                        'error' => $e->getMessage(),
                    ]);

                    continue;
                }
            }

            $this->logger->info('OperationApiService: Récupération réussie', [
                'id_division' => $idDivision,
                'nb_operations' => count($operations),
            ]);

            return $operations;
        });
    }

    /**
     * Construire une clé de cache unique basée sur les filtres.
     */
    private function buildCacheKey(
        int $idDivision,
        ?string $type,
        ?string $dateDebut,
        ?string $dateFin,
        ?string $statut,
    ): string {
        $parts = [
            self::CACHE_KEY_PREFIX,
            $idDivision,
            $type ?? 'all',
            $dateDebut ?? 'all',
            $dateFin ?? 'all',
            $statut ?? 'all',
        ];

        return implode('.', $parts);
    }

    /**
     * Invalider le cache pour une division spécifique.
     *
     * Utile après une modification d'opération (hors scope Sprint S1, mais préparé)
     */
    public function invalidateCache(int $idDivision): void
    {
        // TODO: Implémenter invalidation par tag si besoin (Symfony Cache Tags)
        // Pour l'instant, expiration naturelle après 5min
        $this->logger->info('OperationApiService: Invalidation cache demandée', [
            'id_division' => $idDivision,
            'note' => 'Expiration naturelle 5min - Tags non implémentés',
        ]);
    }
}
