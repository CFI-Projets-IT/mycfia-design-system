<?php

declare(strict_types=1);

namespace App\Controller\Api;

use App\Entity\User;
use App\Repository\UserAccessibleDivisionRepository;
use App\Service\Cfi\CfiTenantService;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur API pour la gestion multi-tenant.
 *
 * Permet à l'utilisateur de :
 * - Lister les divisions accessibles
 * - Changer de division active (tenant switch)
 */
#[Route('/api/tenant', name: 'api_tenant_')]
#[IsGranted('ROLE_USER')]
class TenantController extends AbstractController
{
    public function __construct(
        private readonly CfiTenantService $tenantService,
        private readonly UserAccessibleDivisionRepository $divisionRepository,
        #[Autowire(service: 'monolog.logger.api_services')]
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Liste les divisions accessibles à l'utilisateur.
     *
     * Retourne toutes les divisions synchronisées depuis l'API CFI
     * avec indication de la division actuellement active.
     *
     * @return JsonResponse {divisions: [{id, nom, current}]}
     */
    #[Route('/divisions', name: 'divisions', methods: ['GET'])]
    public function getDivisions(): JsonResponse
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        try {
            // Récupérer toutes les divisions accessibles depuis BDD
            $divisions = $this->divisionRepository->findDivisionsByUser($user);

            // Division actuellement active
            $currentTenantId = $this->tenantService->getCurrentTenantOrNull();

            // Mapper vers format API
            $divisionsData = array_map(
                fn ($division) => [
                    'id' => $division->getIdDivision(),
                    'nom' => $division->getNomDivision(),
                    'current' => $division->getIdDivision() === $currentTenantId,
                ],
                $divisions
            );

            $this->logger->info('API Tenant: Liste divisions accessibles', [
                'user_id' => $user->getId(),
                'nb_divisions' => count($divisionsData),
                'current_tenant' => $currentTenantId,
            ]);

            return $this->json([
                'success' => true,
                'divisions' => $divisionsData,
                'current_tenant_id' => $currentTenantId,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('API Tenant: Erreur récupération divisions', [
                'user_id' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Erreur lors de la récupération des divisions',
            ], 500);
        }
    }

    /**
     * Change la division active (tenant switch).
     *
     * Vérifie que l'utilisateur a accès à la division demandée
     * avant de changer le contexte tenant en session.
     *
     * @return JsonResponse {success: true} ou {error: string}
     */
    #[Route('/switch', name: 'switch', methods: ['POST'])]
    public function switchTenant(Request $request): JsonResponse
    {
        $user = $this->getUser();

        if (! $user instanceof User) {
            return $this->json(['error' => 'Utilisateur non authentifié'], 401);
        }

        // Récupérer l'ID division depuis le body JSON
        $data = json_decode($request->getContent(), true);
        $idDivision = $data['idDivision'] ?? null;

        if (null === $idDivision || ! is_int($idDivision)) {
            return $this->json([
                'error' => 'Paramètre idDivision requis (integer)',
            ], 400);
        }

        try {
            // Tenter le switch (validation hasAccess incluse)
            $this->tenantService->switchTenant($user, $idDivision);

            $this->logger->info('API Tenant: Switch réussi', [
                'user_id' => $user->getId(),
                'new_tenant_id' => $idDivision,
            ]);

            return $this->json([
                'success' => true,
                'message' => 'Division changée avec succès',
                'new_tenant_id' => $idDivision,
            ]);
        } catch (\RuntimeException $e) {
            // Accès refusé
            $this->logger->warning('API Tenant: Switch refusé', [
                'user_id' => $user->getId(),
                'requested_division' => $idDivision,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => $e->getMessage(),
            ], 403);
        } catch (\Exception $e) {
            // Erreur inattendue
            $this->logger->error('API Tenant: Erreur switch', [
                'user_id' => $user->getId(),
                'requested_division' => $idDivision,
                'error' => $e->getMessage(),
            ]);

            return $this->json([
                'error' => 'Erreur lors du changement de division',
            ], 500);
        }
    }
}
