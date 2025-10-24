<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use App\DTO\Cfi\TenantDto;
use App\DTO\Cfi\UtilisateurGorilliasDto;
use App\Entity\User;
use App\Repository\UserAccessibleDivisionRepository;
use App\Service\AsyncExecutionContext;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

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
        private readonly AsyncExecutionContext $asyncContext,
        private readonly UserAccessibleDivisionRepository $accessibleDivisionRepository,
        #[Autowire(service: 'monolog.logger.cfi_api')]
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
     * Récupérer le tenant actuel avec toutes ses données.
     *
     * Construit un TenantDto depuis les informations utilisateur CFI
     * stockées dans la session Symfony ou le contexte asynchrone.
     *
     * Stratégie de résolution :
     * 1. Si contexte async disponible → utiliser tenant injecté
     * 2. Sinon, fallback vers session HTTP (contexte synchrone)
     *
     * @param User $user Utilisateur authentifié
     *
     * @return TenantDto|null Tenant avec données complètes ou null si non trouvé
     */
    public function getTenantActuel(User $user): ?TenantDto
    {
        // Contexte asynchrone : tenant injecté manuellement
        $asyncTenant = $this->asyncContext->getTenant();
        if (null !== $asyncTenant) {
            return $asyncTenant;
        }

        // Contexte synchrone : récupérer depuis session HTTP
        $tenantId = $this->getCurrentTenantOrNull();

        if (null === $tenantId) {
            $this->logger->warning('CfiTenantService: Aucun tenant actif en session', [
                'user_id' => $user->getId(),
            ]);

            return null;
        }

        // Récupérer les données utilisateur CFI depuis la session
        $utilisateurData = $this->sessionService->getUserData();

        if (null === $utilisateurData) {
            $this->logger->error('CfiTenantService: Données utilisateur CFI introuvables en session', [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
            ]);

            return null;
        }

        // Construire TenantDto depuis UtilisateurGorilliasDto
        $utilisateur = UtilisateurGorilliasDto::fromArray($utilisateurData);

        return TenantDto::fromUtilisateur($utilisateur);
    }

    /**
     * Change le tenant actif (pour utilisateurs multi-organisations).
     *
     * Vérifie que l'utilisateur a accès à la division via la table BDD
     * user_accessible_divisions avant d'autoriser le changement de contexte.
     *
     * @param User $user       Utilisateur authentifié
     * @param int  $idDivision Identifiant de la division cible
     *
     * @throws \RuntimeException Si l'utilisateur n'a pas accès à cette division
     */
    public function switchTenant(User $user, int $idDivision): void
    {
        // Vérifier que l'utilisateur a accès à cette division
        $hasAccess = $this->accessibleDivisionRepository->hasAccess($user, $idDivision);

        if (! $hasAccess) {
            $this->logger->warning('CFI Tenant Switch Denied: accès refusé', [
                'user_id' => $user->getId(),
                'requested_division' => $idDivision,
            ]);

            throw new \RuntimeException(sprintf('L\'utilisateur #%d n\'a pas accès à la division #%d', $user->getId(), $idDivision));
        }

        // Sauvegarder l'ancien tenant pour le log
        $oldTenantId = $this->sessionService->getCurrentTenant();

        // Changer le tenant actif en session
        $this->sessionService->setCurrentTenant($idDivision);

        $this->logger->info('CFI Tenant Switched', [
            'user_id' => $user->getId(),
            'old_tenant_id' => $oldTenantId,
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
