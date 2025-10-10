<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\DTO\Cfi\UtilisateurGorilliasDto;
use Psr\Log\LoggerInterface;

/**
 * Service de gestion multi-tenant CFI.
 *
 * Gere le contexte tenant (idDivision) de l'utilisateur
 * et permet le switch entre differentes organisations
 */
class CfiTenantService
{
    public function __construct(
        private readonly CfiSessionService $sessionService,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Initialise le tenant depuis les donnees utilisateur CFI.
     */
    public function initializeTenantFromUser(UtilisateurGorilliasDto $utilisateur): void
    {
        $this->sessionService->setCurrentTenant($utilisateur->idDivision);

        $this->logger->info('CFI Tenant Initialized', [
            'tenant_id' => $utilisateur->idDivision,
            'tenant_name' => $utilisateur->nomDivision,
            'user_id' => $utilisateur->id,
        ]);
    }

    /**
     * Recupere l'identifiant du tenant actif.
     *
     * @throws \RuntimeException Si aucun tenant n'est defini
     */
    public function getCurrentTenant(): int
    {
        $tenantId = $this->sessionService->getCurrentTenant();

        if (null === $tenantId) {
            throw new \RuntimeException('No tenant defined in session. User must authenticate first.');
        }

        return $tenantId;
    }

    /**
     * Recupere l'identifiant du tenant actif ou null si non defini.
     */
    public function getCurrentTenantOrNull(): ?int
    {
        return $this->sessionService->getCurrentTenant();
    }

    /**
     * Change le tenant actif (pour utilisateurs multi-organisations).
     *
     * Note: Dans Sprint S0, cette fonctionnalite n'est pas encore implementee.
     * Elle sera developpee dans un sprint ulterieur pour gerer le multi-tenant complet.
     *
     * @param int $idDivision Identifiant de la division cible
     *
     * @throws \RuntimeException Si l'utilisateur n'a pas acces a cette division
     */
    public function switchTenant(int $idDivision): void
    {
        // TODO Sprint S0+: Verifier que l'utilisateur a acces a cette division
        // via un appel API CFI ou une liste stockee en session

        $this->sessionService->setCurrentTenant($idDivision);

        $this->logger->info('CFI Tenant Switched', [
            'new_tenant_id' => $idDivision,
        ]);
    }

    /**
     * Verifie si un tenant est defini en session.
     */
    public function hasTenant(): bool
    {
        return null !== $this->sessionService->getCurrentTenant();
    }

    /**
     * Efface le contexte tenant de la session.
     */
    public function clearTenant(): void
    {
        $oldTenant = $this->sessionService->getCurrentTenant();

        if (null !== $oldTenant) {
            $this->logger->info('CFI Tenant Cleared', [
                'old_tenant_id' => $oldTenant,
            ]);
        }

        // Le tenant sera efface via sessionService->clear() lors du logout
    }
}
