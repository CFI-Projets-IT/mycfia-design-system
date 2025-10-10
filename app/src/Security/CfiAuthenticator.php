<?php

declare(strict_types=1);

namespace App\Security;

use App\Entity\User;
use App\Exception\CfiApiException;
use App\Service\Cfi\CfiAuthService;
use App\Service\Cfi\CfiSessionService;
use App\Service\Cfi\CfiTenantService;
use App\Service\Cfi\CfiUserSyncService;
use Psr\Log\LoggerInterface;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authenticator\AbstractAuthenticator;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\CsrfTokenBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Badge\UserBadge;
use Symfony\Component\Security\Http\Authenticator\Passport\Passport;
use Symfony\Component\Security\Http\Authenticator\Passport\SelfValidatingPassport;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Authenticator personnalisé pour l'authentification via API CFI.
 *
 * Workflow :
 * 1. User soumet formulaire avec jetonUtilisateur (Token Gorillias)
 * 2. Appel API CFI /Utilisateurs/getUtilisateurGorillias
 * 3. Réception user data + jeton CFI
 * 4. Synchronisation User et Division en BDD (upsert)
 * 5. Stockage jeton CFI en session (TTL 30 min)
 * 6. Mise à jour tracking connexion (loginCount, lastLoginAt)
 * 7. Authentification Symfony avec User Doctrine
 * 8. Redirection vers homepage
 *
 * TODO Sprint S0+ : Ajouter workflow authentification email/password direct
 * Quand CFI fournira l'endpoint dédié, remplacer getUtilisateurGorillias
 * par l'endpoint d'authentification par credentials.
 */
class CfiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly CfiAuthService $cfiAuthService,
        private readonly CfiSessionService $cfiSessionService,
        private readonly CfiTenantService $cfiTenantService,
        private readonly CfiUserSyncService $cfiUserSyncService,
        private readonly UrlGeneratorInterface $urlGenerator,
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Détermine si cet authenticator doit être utilisé pour cette requête.
     */
    public function supports(Request $request): ?bool
    {
        return $request->isMethod('POST')
            && '/login' === $request->getPathInfo()
            && null !== $request->request->get('jetonUtilisateur');
    }

    /**
     * Authentifie l'utilisateur via l'API CFI.
     */
    public function authenticate(Request $request): Passport
    {
        $jetonUtilisateur = $request->request->get('jetonUtilisateur', '');
        $csrfToken = $request->request->get('_csrf_token', '');

        $this->logger->info('CFI Authenticator: Tentative d\'authentification', [
            'jeton_length' => strlen($jetonUtilisateur),
            'has_csrf' => ! empty($csrfToken),
        ]);

        // Validation format token Gorillias
        if (empty($jetonUtilisateur) || ! $this->cfiAuthService->isValidTokenFormat($jetonUtilisateur)) {
            $this->logger->warning('CFI Authenticator: Token Gorillias invalide', [
                'jeton' => substr($jetonUtilisateur, 0, 8).'...',
            ]);

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.invalid_token', [], 'security'));
        }

        try {
            // Appel API CFI pour authentification
            $utilisateurDto = $this->cfiAuthService->authenticate($jetonUtilisateur);

            $this->logger->info('CFI Authenticator: Authentification API réussie', [
                'user_id' => $utilisateurDto->id,
                'user_email' => $utilisateurDto->email,
                'division' => $utilisateurDto->nomDivision,
            ]);

            // Synchroniser User et Division en BDD (upsert)
            $user = $this->cfiUserSyncService->syncUserFromCfi($utilisateurDto);
            $this->logger->info('CFI Authenticator: User synchronisé en BDD', [
                'userId' => $user->getId(),
                'idCfi' => $user->getIdCfi(),
                'divisionId' => $user->getDivision()?->getId(),
            ]);

            // Stocker le token CFI en session
            if (null !== $utilisateurDto->jeton) {
                $this->cfiSessionService->setToken($utilisateurDto->jeton);
                $this->logger->debug('CFI Authenticator: Token CFI stocké en session', [
                    'ttl_seconds' => 1800,
                ]);
            }

            // Initialiser le contexte tenant
            $this->cfiTenantService->initializeTenantFromUser($utilisateurDto);

            // Stocker les données utilisateur en session
            $this->cfiSessionService->setUserData($utilisateurDto->toArray());

            // Mettre à jour tracking connexion (loginCount, lastLoginAt)
            $this->cfiUserSyncService->updateLoginTracking($user);

            // Créer le Passport avec UserBadge et CSRF (User Doctrine)
            return new SelfValidatingPassport(
                new UserBadge(
                    $user->getUserIdentifier(),
                    fn () => $user
                ),
                [
                    new CsrfTokenBadge('authenticate', $csrfToken),
                ]
            );
        } catch (CfiApiException $e) {
            $this->logger->error('CFI Authenticator: Erreur API CFI', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.authentication_failed', [], 'security'), previous: $e);
        } catch (\Exception $e) {
            $this->logger->error('CFI Authenticator: Erreur inattendue', [
                'message' => $e->getMessage(),
                'exception' => $e::class,
            ]);

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.unexpected', [], 'security'), previous: $e);
        }
    }

    /**
     * Redirection après authentification réussie.
     */
    public function onAuthenticationSuccess(Request $request, TokenInterface $token, string $firewallName): ?Response
    {
        $user = $token->getUser();

        if ($user instanceof User) {
            $this->logger->info('CFI Authenticator: Authentification réussie, redirection homepage', [
                'userId' => $user->getId(),
                'idCfi' => $user->getIdCfi(),
                'email' => $user->getEmail(),
                'loginCount' => $user->getLoginCount(),
            ]);
        }

        // Redirection vers homepage
        return new RedirectResponse($this->urlGenerator->generate('home_index'));
    }

    /**
     * Redirection après échec d'authentification.
     */
    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): ?Response
    {
        $this->logger->warning('CFI Authenticator: Échec d\'authentification', [
            'message' => $exception->getMessage(),
        ]);

        // Stocker l'erreur en session pour affichage dans le formulaire
        $request->getSession()->set('_security.last_error', $exception);

        // Redirection vers formulaire login avec erreur
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
