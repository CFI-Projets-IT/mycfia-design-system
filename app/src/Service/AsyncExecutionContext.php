<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\Cfi\TenantDto;
use App\Entity\User;

/**
 * Service pour gérer le contexte d'exécution asynchrone (Messenger Worker).
 *
 * Responsabilités :
 * - Stocker temporairement User et Tenant pour le contexte asynchrone
 * - Permettre aux services (UserAuthenticationService, CfiTenantService) d'accéder aux données sans session HTTP
 * - Gérer le cycle de vie du contexte (injection → utilisation → nettoyage)
 *
 * Architecture :
 * - Contexte synchrone (web) : Services utilisent session HTTP (comportement par défaut)
 * - Contexte asynchrone (worker) : Services utilisent AsyncExecutionContext (fallback)
 *
 * Utilisation dans Messenger Worker :
 * 1. ChatController récupère User et Tenant depuis session
 * 2. userId et tenantId passés dans ChatStreamMessage
 * 3. ChatStreamMessageHandler injecte User et Tenant via setContext()
 * 4. Services (UserAuthenticationService, CfiTenantService) récupèrent via getContext()
 *
 * Note : Ce service n'est pas readonly car il doit pouvoir modifier le contexte.
 */
final class AsyncExecutionContext
{
    /**
     * Utilisateur authentifié pour contexte asynchrone (null en contexte synchrone).
     */
    private ?User $user = null;

    /**
     * Tenant actuel pour contexte asynchrone (null en contexte synchrone).
     */
    private ?TenantDto $tenant = null;

    /**
     * Récupérer l'utilisateur depuis le contexte asynchrone.
     *
     * @return User|null Utilisateur injecté ou null si contexte synchrone
     */
    public function getUser(): ?User
    {
        return $this->user;
    }

    /**
     * Récupérer le tenant depuis le contexte asynchrone.
     *
     * @return TenantDto|null Tenant injecté ou null si contexte synchrone
     */
    public function getTenant(): ?TenantDto
    {
        return $this->tenant;
    }

    /**
     * Injecter le contexte d'exécution asynchrone (User + Tenant).
     *
     * Utilisé par ChatStreamMessageHandler pour passer le contexte
     * récupéré depuis la session web au contexte worker.
     *
     * @param User      $user   Utilisateur authentifié
     * @param TenantDto $tenant Tenant actuel
     */
    public function setContext(User $user, TenantDto $tenant): void
    {
        $this->user = $user;
        $this->tenant = $tenant;
    }

    /**
     * Réinitialiser le contexte (utile en fin de traitement async).
     */
    public function clear(): void
    {
        $this->user = null;
        $this->tenant = null;
    }

    /**
     * Vérifier si un contexte asynchrone est actif.
     *
     * @return bool True si contexte async disponible
     */
    public function hasContext(): bool
    {
        return null !== $this->user && null !== $this->tenant;
    }
}
