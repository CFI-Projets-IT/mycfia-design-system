<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\Cfi\FactureDetailDto;
use App\DTO\Cfi\FactureDto;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiTokenContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Service API pour récupérer les facturations depuis CFI.
 *
 * Endpoint : /Facturations/getFacturations
 * Cache : 5 minutes (données fréquemment modifiées)
 * Filtrage automatique par tenant (idDivision)
 *
 * Responsabilités :
 * - Récupérer les factures CFI avec filtres temporels
 * - Mapper les données vers FactureDto
 * - Gérer cache et authentification
 *
 * Logging : Canal dédié 'api_services' (pas 'cfi_api')
 */
final readonly class FacturationApiService
{
    private const CACHE_TTL = 300; // 5 minutes
    private const CACHE_KEY_PREFIX = 'cfi.facturations';
    private const ENDPOINT = '/Facturations/getFacturations';

    public function __construct(
        private CfiApiService $cfiApi,
        private CfiTokenContext $cfiTokenContext,
        private CacheInterface $cache,
        #[Autowire(service: 'monolog.logger.api_services')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupérer la liste des facturations filtrées par division.
     *
     * Cache : 5min par combinaison de filtres
     * Filtrage tenant automatique (idDivision intégré)
     *
     * @param int         $idDivision ID de la division CFI
     * @param string|null $debut      Date de début au format ISO 8601 (ex: 2024-10-14T15:04:29.547Z)
     * @param string|null $fin        Date de fin au format ISO 8601 (ex: 2025-10-14T15:04:29.547Z)
     *
     * @return FactureDto[]
     */
    public function getFacturations(
        int $idDivision,
        ?string $debut = null,
        ?string $fin = null,
    ): array {
        $startTime = microtime(true);

        // Générer clé cache unique basée sur les filtres
        $cacheKey = $this->buildCacheKey($idDivision, $debut, $fin);

        $beta = null;

        $factures = $this->cache->get($cacheKey, function (ItemInterface $item) use ($idDivision, $debut, $fin, $startTime): array {
            $item->expiresAfter(self::CACHE_TTL);

            $this->logger->info('FacturationApiService: Cache MISS - Appel API CFI', [
                'cache_key' => $item->getKey(),
                'id_division' => $idDivision,
                'cache_status' => 'MISS',
            ]);

            // Récupérer le token d'authentification (contexte sync ou async)
            $jeton = $this->cfiTokenContext->getToken();
            if (null === $jeton) {
                $this->logger->error('FacturationApiService: Token CFI manquant ou expiré', [
                    'duration_ms' => (microtime(true) - $startTime) * 1000,
                ]);

                return [];
            }

            // Construire le corps de la requête (format CFI exact)
            $body = [
                'debut' => $debut,
                'fin' => $fin,
            ];

            // Note : idDivision est géré par l'authentification CFI via le jeton
            // Il est implicite dans la requête, pas dans le body

            // Appel API CFI
            $response = $this->cfiApi->post(self::ENDPOINT, $body, $jeton);

            // Mapper les données brutes vers DTOs
            $factures = [];
            // La réponse est directement un tableau de facturations
            foreach ($response as $item) {
                if (! is_array($item)) {
                    continue;
                }
                $factures[] = FactureDto::fromApiData($item);
            }

            $this->logger->info('FacturationApiService: Récupération réussie', [
                'id_division' => $idDivision,
                'nb_facturations' => count($factures),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'cache_status' => 'MISS',
            ]);

            return $factures;
        }, INF, $cacheHit);

        // Logger cache HIT si applicable
        if ($cacheHit) {
            $this->logger->info('FacturationApiService: Cache HIT', [
                'cache_key' => $cacheKey,
                'id_division' => $idDivision,
                'nb_facturations' => count($factures),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
                'cache_status' => 'HIT',
            ]);
        }

        return $factures;
    }

    /**
     * Construire une clé de cache unique basée sur les filtres.
     *
     * Note : Les caractères {}()/\@: sont interdits dans les clés Symfony Cache.
     * On remplace les ':' par '-' pour les dates ISO 8601.
     */
    private function buildCacheKey(
        int $idDivision,
        ?string $debut,
        ?string $fin,
    ): string {
        // Nettoyer les dates ISO 8601 en remplaçant les caractères interdits
        $debutSafe = $debut ? str_replace([':', '/'], ['-', '_'], $debut) : 'all';
        $finSafe = $fin ? str_replace([':', '/'], ['-', '_'], $fin) : 'all';

        $parts = [
            self::CACHE_KEY_PREFIX,
            $idDivision,
            $debutSafe,
            $finSafe,
        ];

        return implode('.', $parts);
    }

    /**
     * Récupérer une facture spécifique par son ID.
     *
     * Endpoint : POST /Facturations/getFacture
     * Sécurité : CFI vérifie automatiquement le droit 'factures_Visu'
     *
     * Pas de cache : Donnée spécifique, requête rapide
     *
     * @param int $idFacture ID de la facture à récupérer
     *
     * @return FactureDetailDto|null Facture trouvée ou null si inexistante/pas de droit
     */
    public function getFacture(int $idFacture): ?FactureDetailDto
    {
        $startTime = microtime(true);

        $this->logger->info('FacturationApiService: Récupération facture spécifique', [
            'id_facture' => $idFacture,
        ]);

        // Récupérer le token d'authentification (contexte sync ou async)
        $jeton = $this->cfiTokenContext->getToken();
        if (null === $jeton) {
            $this->logger->error('FacturationApiService: Token CFI manquant ou expiré', [
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            return null;
        }

        // Construire le corps de la requête
        $body = [
            'idFacture' => $idFacture,
        ];

        try {
            // Appel API CFI
            $response = $this->cfiApi->post('/Facturations/getFacture', $body, $jeton);

            // La réponse est directement un FactureDetailDto (pas un tableau de facturations)
            $facture = FactureDetailDto::fromApiData($response);

            $this->logger->info('FacturationApiService: Facture récupérée avec succès', [
                'id_facture' => $idFacture,
                'montant_ttc' => $facture->montantTTC,
                'nb_lignes' => count($facture->lignes),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            return $facture;
        } catch (\Exception $e) {
            // Gestion des erreurs (400 = pas de droit factures_Visu ou facture inexistante)
            $this->logger->error('FacturationApiService: Erreur récupération facture', [
                'id_facture' => $idFacture,
                'error' => $e->getMessage(),
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            return null;
        }
    }

    /**
     * Invalider le cache pour une division spécifique.
     *
     * Utile après une modification de facturation (hors scope Sprint S1, mais préparé)
     */
    public function invalidateCache(int $idDivision): void
    {
        // TODO: Implémenter invalidation par tag si besoin (Symfony Cache Tags)
        // Pour l'instant, expiration naturelle après 5min
        $this->logger->info('FacturationApiService: Invalidation cache demandée', [
            'id_division' => $idDivision,
            'note' => 'Expiration naturelle 5min - Tags non implémentés',
        ]);
    }
}
