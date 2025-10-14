<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\TenantDto;
use App\Entity\User;
use App\Service\Cfi\CfiTenantService;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Trait pour gérer l'authentification et la récupération du tenant dans les tools IA.
 *
 * Centralise la logique de validation utilisateur et tenant pour éviter
 * la duplication de code dans tous les tools nécessitant une authentification.
 */
trait AuthenticatedToolTrait
{
    /**
     * Récupérer l'utilisateur authentifié et son tenant actuel.
     *
     * Valide que :
     * - Un utilisateur est authentifié
     * - L'utilisateur est de type User (pas seulement UserInterface)
     * - Un tenant (division) est actif pour cet utilisateur
     *
     * @param Security         $security      Service de sécurité Symfony
     * @param CfiTenantService $tenantService Service de gestion multi-tenant
     *
     * @return array{user: User, tenant: TenantDto}|array{error: array} Utilisateur et tenant, ou erreur formatée
     */
    protected function getUserAndTenant(Security $security, CfiTenantService $tenantService): array
    {
        // Récupérer utilisateur authentifié
        $user = $security->getUser();
        if (null === $user || ! $user instanceof User) {
            return ['error' => $this->errorResponse('Utilisateur non authentifié')];
        }

        // Récupérer tenant actuel
        $tenant = $tenantService->getTenantActuel($user);
        if (null === $tenant) {
            return ['error' => $this->errorResponse('Division non trouvée')];
        }

        return [
            'user' => $user,
            'tenant' => $tenant,
        ];
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * Cette méthode doit être implémentée par chaque tool qui utilise le trait.
     *
     * @param string $message Message d'erreur
     *
     * @return array{success: bool, error: string} Réponse d'erreur standardisée
     */
    abstract protected function errorResponse(string $message): array;
}
