<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\Cfi\EtatOperationDto;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiSessionService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service API pour récupérer les états d'opérations depuis CFI.
 *
 * Cache : 1 heure (données de référence rarement modifiées)
 * Pas de filtrage tenant (données globales)
 *
 * Logging : Canal dédié 'api_services' (pas 'cfi_api')
 */
final readonly class EtatOperationApiService
{
    private const CACHE_TTL = 3600; // 1 heure
    private const CACHE_KEY = 'cfi.etats_operations';
    private const ENDPOINT = '/Operations/getEtatsOperations';

    public function __construct(
        private CfiApiService $cfiApi,
        private CfiSessionService $cfiSession,
        private CacheInterface $cache,
        #[Autowire(service: 'monolog.logger.api_services')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupérer la liste des états d'opérations (données de référence).
     *
     * Cache : 1h (données rarement modifiées)
     * Pas de filtrage tenant (données globales partagées)
     *
     * @return EtatOperationDto[]
     */
    public function getEtatsOperations(): array
    {
        return $this->cache->get(self::CACHE_KEY, function (ItemInterface $item): array {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->info('EtatOperationApiService: Cache MISS - Appel API CFI', [
                'cache_key' => $item->getKey(),
            ]);

            // Récupérer le token d'authentification
            $jeton = $this->cfiSession->getToken();
            if (null === $jeton) {
                $this->logger->error('EtatOperationApiService: Token CFI manquant');

                return [];
            }

            // Appel API CFI (pas de corps nécessaire pour données de référence)
            $response = $this->cfiApi->post(self::ENDPOINT, [], $jeton);

            // Mapper les données brutes vers DTOs
            $etats = [];
            if (isset($response['data']) && is_array($response['data'])) {
                foreach ($response['data'] as $item) {
                    if (! is_array($item)) {
                        continue;
                    }
                    $etats[] = EtatOperationDto::fromApiData($item);
                }
            }

            $this->logger->info('EtatOperationApiService: Récupération réussie', [
                'nb_etats' => count($etats),
            ]);

            return $etats;
        });
    }

    /**
     * Récupérer un état d'opération par son ID.
     *
     * Utilise le cache global des états.
     */
    public function getEtatOperationById(int $id): ?EtatOperationDto
    {
        $etats = $this->getEtatsOperations();

        foreach ($etats as $etat) {
            if ($etat->id === $id) {
                return $etat;
            }
        }

        $this->logger->warning('EtatOperationApiService: État non trouvé', [
            'id_etat' => $id,
        ]);

        return null;
    }

    /**
     * Invalider le cache des états d'opérations.
     *
     * Utile après une modification admin des états (hors scope Sprint S1, mais préparé)
     */
    public function invalidateCache(): void
    {
        $this->cache->delete(self::CACHE_KEY);

        $this->logger->info('EtatOperationApiService: Cache invalidé manuellement');
    }
}
