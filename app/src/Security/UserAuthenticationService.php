<?php

declare(strict_types=1);

namespace App\Security;

use App\DTO\Cfi\TenantDto;
use App\Entity\User;
use App\Service\Cfi\CfiTenantService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Service centralisé pour l'authentification et récupération du contexte utilisateur.
 *
 * Pattern Strategy : Centralise la logique d'authentification utilisée par
 * différentes couches applicatives (Controllers, Tools IA, Services).
 *
 * Responsabilités :
 * - Récupérer l'utilisateur authentifié avec type-safety
 * - Récupérer le tenant (division) actuel de l'utilisateur
 * - Valider le contexte complet (User + Tenant)
 *
 * Avantages :
 * - Single Responsibility Principle (SRP)
 * - DRY : Évite duplication entre Controllers et Tools
 * - Type-safe : Garantit que l'utilisateur est bien de type User
 * - Testable : Logique isolée, facile à mocker
 */
final readonly class UserAuthenticationService
{
    public function __construct(
        private Security $security,
        private CfiTenantService $tenantService,
    ) {
    }

    /**
     * Récupérer l'utilisateur authentifié avec validation de type.
     *
     * @return User|null Utilisateur authentifié de type User, ou null si non authentifié
     */
    public function getAuthenticatedUser(): ?User
    {
        $user = $this->security->getUser();

        if (null === $user || ! $user instanceof User) {
            return null;
        }

        return $user;
    }

    /**
     * Récupérer l'utilisateur et son tenant actuel.
     *
     * Valide que :
     * - Un utilisateur est authentifié
     * - L'utilisateur est de type User (pas seulement UserInterface)
     * - Un tenant (division) est actif pour cet utilisateur
     *
     * @return array{user: User, tenant: TenantDto}|null Utilisateur et tenant, ou null si invalide
     */
    public function getUserWithTenant(): ?array
    {
        // Récupérer utilisateur authentifié
        $user = $this->getAuthenticatedUser();
        if (null === $user) {
            return null;
        }

        // Récupérer tenant actuel
        $tenant = $this->tenantService->getTenantActuel($user);
        if (null === $tenant) {
            return null;
        }

        return [
            'user' => $user,
            'tenant' => $tenant,
        ];
    }

    /**
     * Vérifier si l'utilisateur courant est authentifié.
     *
     * @return bool True si utilisateur authentifié de type User
     */
    public function isAuthenticated(): bool
    {
        return null !== $this->getAuthenticatedUser();
    }
}
