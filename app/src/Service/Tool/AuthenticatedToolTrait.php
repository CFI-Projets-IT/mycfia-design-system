<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\TenantDto;
use App\Entity\User;
use App\Security\UserAuthenticationService;

/**
 * Trait pour gérer l'authentification et la récupération du tenant dans les tools IA.
 *
 * Centralise la logique de validation utilisateur et tenant pour éviter
 * la duplication de code dans tous les tools nécessitant une authentification.
 *
 * Architecture : Utilise UserAuthenticationService (pattern Strategy)
 * pour centraliser la logique d'authentification partagée entre
 * Controllers et Tools IA.
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
     * @param UserAuthenticationService $authService Service d'authentification centralisé
     *
     * @return array{user: User, tenant: TenantDto}|array{error: array} Utilisateur et tenant, ou erreur formatée
     */
    protected function getUserAndTenant(UserAuthenticationService $authService): array
    {
        // Récupérer utilisateur et tenant via service centralisé
        $context = $authService->getUserWithTenant();

        if (null === $context) {
            return ['error' => $this->errorResponse('Utilisateur non authentifié ou division non trouvée')];
        }

        return $context;
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
