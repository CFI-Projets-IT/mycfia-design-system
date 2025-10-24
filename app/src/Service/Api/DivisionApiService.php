<?php

declare(strict_types=1);

namespace App\Service\Api;

use App\DTO\Cfi\DivisionDto;
use App\DTO\Cfi\UtilisateurDto;
use App\Service\Cfi\CfiApiService;
use App\Service\Cfi\CfiTokenContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Service API pour récupérer les divisions et utilisateurs depuis CFI.
 *
 * Endpoints :
 * - /Division/getDivisions : Divisions enfants accessibles (hiérarchie)
 * - /Division/getUtilisateurs : Utilisateurs enfants accessibles (hiérarchie)
 *
 * Responsabilités :
 * - Appeler l'API CFI pour récupérer les données brutes
 * - Mapper les données vers les DTOs (DivisionDto, UtilisateurDto)
 * - Pas de cache (géré par DivisionSyncService via BDD)
 *
 * Logging : Canal dédié 'api_services' (pas 'cfi_api')
 */
final readonly class DivisionApiService
{
    private const ENDPOINT_GET_DIVISIONS = '/Division/getDivisions';
    private const ENDPOINT_GET_UTILISATEURS = '/Division/getUtilisateurs';

    public function __construct(
        private CfiApiService $cfiApi,
        private CfiTokenContext $cfiTokenContext,
        #[Autowire(service: 'monolog.logger.api_services')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Récupère les divisions enfants accessibles à l'utilisateur loggé.
     *
     * Appelle directement l'API CFI (pas de cache).
     * Le cache est géré par DivisionSyncService via stockage BDD.
     *
     * @return array<int, DivisionDto>
     *
     * @throws \RuntimeException Si token CFI manquant
     * @throws \Exception        Si l'API CFI échoue
     */
    public function getDivisions(): array
    {
        $startTime = microtime(true);

        // Récupérer le token d'authentification
        $jeton = $this->cfiTokenContext->getToken();
        if (null === $jeton) {
            $this->logger->error('DivisionApiService: Token CFI manquant', [
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            throw new \RuntimeException('Token CFI manquant pour appeler /Division/getDivisions');
        }

        // Appel API CFI (body vide, divisions déterminées par le token user)
        $response = $this->cfiApi->post(self::ENDPOINT_GET_DIVISIONS, [], $jeton);

        // Mapper chaque élément vers DivisionDto
        $divisions = array_values(array_map(
            fn (array $divisionData) => DivisionDto::fromApiData($divisionData),
            $response
        ));

        $this->logger->info('DivisionApiService: Divisions récupérées', [
            'nb_divisions' => count($divisions),
            'duration_ms' => (microtime(true) - $startTime) * 1000,
        ]);

        return $divisions;
    }

    /**
     * Récupère les utilisateurs enfants accessibles à l'utilisateur loggé.
     *
     * Appelle directement l'API CFI (pas de cache).
     *
     * @return array<int, UtilisateurDto>
     *
     * @throws \RuntimeException Si token CFI manquant
     * @throws \Exception        Si l'API CFI échoue
     */
    public function getUtilisateurs(): array
    {
        $startTime = microtime(true);

        // Récupérer le token d'authentification
        $jeton = $this->cfiTokenContext->getToken();
        if (null === $jeton) {
            $this->logger->error('DivisionApiService: Token CFI manquant', [
                'duration_ms' => (microtime(true) - $startTime) * 1000,
            ]);

            throw new \RuntimeException('Token CFI manquant pour appeler /Division/getUtilisateurs');
        }

        // Appel API CFI (body vide, utilisateurs déterminés par le token user)
        $response = $this->cfiApi->post(self::ENDPOINT_GET_UTILISATEURS, [], $jeton);

        // Mapper chaque élément vers UtilisateurDto
        $utilisateurs = array_values(array_map(
            fn (array $userData) => UtilisateurDto::fromApiData($userData),
            $response
        ));

        $this->logger->info('DivisionApiService: Utilisateurs récupérés', [
            'nb_utilisateurs' => count($utilisateurs),
            'duration_ms' => (microtime(true) - $startTime) * 1000,
        ]);

        return $utilisateurs;
    }
}
