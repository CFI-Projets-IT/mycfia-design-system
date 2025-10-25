<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\Cfi\DroitsUtilisateurDto;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiTokenContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service API pour récupérer les droits utilisateur depuis CFI.
 *
 * Endpoint : /Utilisateurs/getDroitsUtilisateur
 * Cache : 30 minutes (aligné avec TTL du token CFI)
 *
 * Responsabilités :
 * - Récupérer les 25 permissions + quota HD de l'utilisateur connecté
 * - Mapper les données vers DroitsUtilisateurDto
 * - Gérer cache et authentification
 *
 * Logging : Canal dédié 'api_services' (pas 'cfi_api')
 */
final readonly class UtilisateurApiService
{
    private const CACHE_TTL = 1800; // 30 minutes (aligné avec TTL token CFI)
    private const CACHE_KEY_PREFIX = 'cfi.utilisateur.droits';
    private const ENDPOINT = '/Utilisateurs/getDroitsUtilisateur';

    public function __construct(
        private CfiApiService $cfiApi,
        private CfiTokenContext $cfiTokenContext,
        private CacheInterface $cache,
        #[Autowire(service: 'monolog.logger.api_services')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupérer les droits et permissions de l'utilisateur connecté.
     *
     * Cache : 30min par utilisateur (basé sur token CFI)
     * Authentification : Token CFI dans le contexte
     *
     * @param int $idUtilisateur ID CFI de l'utilisateur (pour la clé cache)
     *
     * @return array<string, bool|float> Tableau associatif des permissions
     */
    public function getDroitsUtilisateur(int $idUtilisateur): array
    {
        $startTime = microtime(true);

        // Générer clé cache unique basée sur l'ID utilisateur
        $cacheKey = $this->buildCacheKey($idUtilisateur);

        $beta = null;

        $droits = $this->cache->get($cacheKey, function (ItemInterface $item) use ($idUtilisateur, $startTime): array {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->info('UtilisateurApiService: Cache MISS - Appel API CFI', [
                'cache_key' => $item->getKey(),
                'id_utilisateur' => $idUtilisateur,
                'cache_status' => 'MISS',
            ]);

            // Récupérer le token d'authentification (contexte sync ou async)
            $jeton = $this->cfiTokenContext->getToken();
            if (null === $jeton) {
                $this->logger->error('UtilisateurApiService: Token CFI manquant ou expiré', [
                    'duration_ms' => (microtime(true) - $startTime) * 1000,
                ]);

                return [];
            }

            // Appel API CFI (body vide, l'utilisateur est identifié par le token)
            $response = $this->cfiApi->post(self::ENDPOINT, [], $jeton);

            // Mapper les données brutes vers DTO puis array
            $droitsDto = DroitsUtilisateurDto::fromApiData($response);
            $droitsArray = $droitsDto->toArray();

            $this->logger->info('UtilisateurApiService: Récupération réussie', [
                'id_utilisateur' => $idUtilisateur,
                'nb_permissions' => count($droitsArray),
                'administrateur' => $droitsDto->administrateur,
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'cache_status' => 'MISS',
            ]);

            return $droitsArray;
        }, INF, $cacheHit);

        // Logger cache HIT si applicable
        if ($cacheHit) {
            $this->logger->info('UtilisateurApiService: Cache HIT', [
                'cache_key' => $cacheKey,
                'id_utilisateur' => $idUtilisateur,
                'nb_permissions' => count($droits),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'cache_status' => 'HIT',
            ]);
        }

        return $droits;
    }

    /**
     * Construire une clé de cache unique basée sur l'ID utilisateur.
     */
    private function buildCacheKey(int $idUtilisateur): string
    {
        return self::CACHE_KEY_PREFIX.'.'.$idUtilisateur;
    }
}
