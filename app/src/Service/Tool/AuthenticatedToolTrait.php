<?php

declare(strict_types=1);

namespace App\Service\Tool;

use App\DTO\Cfi\TenantDto;
use App\Entity\User;
use App\Security\UserAuthenticationService;
use Symfony\Contracts\Translation\TranslatorInterface;

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
     * @param TranslatorInterface       $translator  Service de traduction pour messages i18n
     *
     * @return array{user: User, tenant: TenantDto}|array{error: array} Utilisateur et tenant, ou erreur formatée
     */
    protected function getUserAndTenant(UserAuthenticationService $authService, TranslatorInterface $translator): array
    {
        // Récupérer utilisateur et tenant via service centralisé
        $context = $authService->getUserWithTenant();

        if (null === $context) {
            // Message traduit pour l'utilisateur final (via agent IA)
            $userMessage = $translator->trans('auth.error.not_authenticated', [], 'tools');

            return ['error' => $this->errorResponse($userMessage)];
        }

        return $context;
    }

    /**
     * Formatter réponse d'erreur structurée.
     *
     * Cette méthode doit être implémentée par chaque tool qui utilise le trait.
     *
     * @param string $message Message d'erreur traduit pour l'utilisateur final
     *
     * @return array{success: bool, error: string} Réponse d'erreur standardisée
     */
    abstract protected function errorResponse(string $message): array;
}
