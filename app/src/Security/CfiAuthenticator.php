<?php

declare(strict_types=1);

namespace App\Security;

use App\DTO\Cfi\UtilisateurGorilliasDto;
use App\Entity\User;
use App\Exception\CfiAccessDeniedException;
use App\Exception\CfiApiException;
use App\Exception\CfiTokenExpiredException;
use App\Service\Cfi\CfiAuthService;
use App\Service\Cfi\CfiSessionService;
use App\Service\Cfi\CfiTenantService;
use App\Service\Cfi\CfiUserSyncService;
use App\Service\Cfi\DivisionSyncService;
use App\Service\PasswordHasherService;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
 * Supporte 2 modes d'authentification :
 *
 * MODE TOKEN (actuel - dev) :
 * 1. User soumet formulaire avec jetonUtilisateur (Token Gorillias) + mode=token
 * 2. Appel API CFI /Utilisateurs/getUtilisateurGorillias
 * 3. Réception user data + jeton CFI
 *
 * MODE CREDENTIALS (futur - prod) :
 * 1. User soumet formulaire avec email + password + mode=credentials
 * 2. Appel API CFI /Utilisateurs/authenticate (endpoint en attente)
 * 3. Réception user data + jeton CFI (même structure)
 *
 * WORKFLOW COMMUN (après authentification API) :
 * 4. Synchronisation User et Division en BDD (upsert)
 * 5. Stockage jeton CFI en session (TTL 30 min)
 * 6. Mise à jour tracking connexion (loginCount, lastLoginAt)
 * 7. Authentification Symfony avec User Doctrine
 * 8. Redirection vers homepage
 */
class CfiAuthenticator extends AbstractAuthenticator
{
    public function __construct(
        private readonly CfiAuthService $cfiAuthService,
        private readonly CfiSessionService $cfiSessionService,
        private readonly CfiTenantService $cfiTenantService,
        private readonly CfiUserSyncService $cfiUserSyncService,
        private readonly DivisionSyncService $divisionSyncService,
        private readonly PasswordHasherService $passwordHasher,
        private readonly UrlGeneratorInterface $urlGenerator,
        #[Autowire(service: 'monolog.logger.auth')]
        private readonly LoggerInterface $logger,
        private readonly TranslatorInterface $translator,
    ) {
    }

    /**
     * Détermine si cet authenticator doit être utilisé pour cette requête.
     *
     * Supporte 2 modes :
     * - mode=token : requiert jetonUtilisateur
     * - mode=credentials : requiert email + password
     */
    public function supports(Request $request): ?bool
    {
        if (! $request->isMethod('POST') || '/login' !== $request->getPathInfo()) {
            return false;
        }

        $mode = $request->request->get('mode', 'token');

        // Mode token : vérifier présence du jetonUtilisateur
        if ('token' === $mode) {
            return null !== $request->request->get('jetonUtilisateur');
        }

        // Mode credentials : vérifier présence username + password
        if ('credentials' === $mode) {
            return null !== $request->request->get('username')
                && null !== $request->request->get('password');
        }

        return false;
    }

    /**
     * Authentifie l'utilisateur via l'API CFI.
     *
     * Détecte automatiquement le mode d'authentification (token vs credentials)
     * et route vers la bonne méthode du CfiAuthService.
     */
    public function authenticate(Request $request): Passport
    {
        $mode = $request->request->get('mode', 'token');
        $csrfToken = $request->request->get('_csrf_token', '');

        $this->logger->info('CFI Authenticator: Tentative d\'authentification', [
            'mode' => $mode,
            'has_csrf' => ! empty($csrfToken),
        ]);

        try {
            // Router vers la bonne méthode selon le mode
            if ('token' === $mode) {
                $utilisateurDto = $this->authenticateWithToken($request);
            } else {
                $utilisateurDto = $this->authenticateWithCredentials($request);
            }

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

            // Stocker le token CFI en session AVANT les sync (nécessaire pour appels API)
            if (null !== $utilisateurDto->jeton) {
                $this->cfiSessionService->setToken($utilisateurDto->jeton);
                $this->logger->debug('CFI Authenticator: Token CFI stocké en session', [
                    'ttl_seconds' => 1800,
                ]);
            }

            // Synchroniser les permissions utilisateur depuis l'API CFI
            $this->cfiUserSyncService->syncUserPermissions($user);

            // Synchroniser les divisions accessibles depuis l'API CFI
            try {
                $divisions = $this->divisionSyncService->syncUserDivisions($user);
                $this->logger->info('CFI Authenticator: Divisions accessibles synchronisées', [
                    'userId' => $user->getId(),
                    'nbDivisions' => count($divisions),
                ]);
            } catch (\Exception $e) {
                // Ne pas bloquer l'authentification si la sync divisions échoue
                // Le mode dégradé utilisera les divisions BDD existantes
                $this->logger->error('CFI Authenticator: Erreur sync divisions (non-bloquant)', [
                    'userId' => $user->getId(),
                    'error' => $e->getMessage(),
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
        } catch (CfiTokenExpiredException $e) {
            // 401 : Token expire ou invalide → nettoyer session et demander reconnexion
            $this->logger->warning('CFI Authenticator: Token CFI expire ou invalide', [
                'correlation_id' => $e->getCorrelationId(),
                'message' => $e->getMessage(),
            ]);

            // Nettoyer la session CFI
            $this->cfiSessionService->clear();

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.token_expired', [], 'security'), previous: $e);
        } catch (CfiAccessDeniedException $e) {
            // 403 : Acces refuse → droits insuffisants
            $this->logger->warning('CFI Authenticator: Acces refuse par API CFI', [
                'correlation_id' => $e->getCorrelationId(),
                'message' => $e->getMessage(),
            ]);

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.access_denied', [], 'security'), previous: $e);
        } catch (CfiApiException $e) {
            // Autres erreurs API CFI (400, 404, 5xx, etc.)
            $this->logger->error('CFI Authenticator: Erreur API CFI', [
                'message' => $e->getMessage(),
                'code' => $e->getCode(),
            ]);

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.authentication_failed', [], 'security'), previous: $e);
        } catch (\Exception $e) {
            // Erreurs inattendues
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

        // BUGFIX: Forcer l'écriture de la session AVANT la redirection
        // Race condition fix: Sans ceci, si l'utilisateur clique trop vite sur une page protégée,
        // le token de sécurité n'est pas encore écrit en session et Symfony redirige vers /login
        $session = $request->getSession();
        $session->save();

        $this->logger->debug('CFI Authenticator: Session saved before redirect', [
            'session_id' => $session->getId(),
        ]);

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

    /**
     * Authentification via Token Gorillias (mode actuel).
     */
    private function authenticateWithToken(Request $request): UtilisateurGorilliasDto
    {
        $jetonUtilisateur = $request->request->get('jetonUtilisateur', '');

        // Validation format token Gorillias
        if (empty($jetonUtilisateur) || ! $this->cfiAuthService->isValidTokenFormat($jetonUtilisateur)) {
            $this->logger->warning('CFI Authenticator: Token Gorillias invalide', [
                'jeton' => substr($jetonUtilisateur, 0, 8).'...',
            ]);

            throw new AuthenticationException($this->translator->trans('cfi.auth.error.invalid_token', [], 'security'));
        }

        // Appel API CFI pour authentification
        return $this->cfiAuthService->authenticate($jetonUtilisateur);
    }

    /**
     * Authentification via Identifiant/Password.
     */
    private function authenticateWithCredentials(Request $request): UtilisateurGorilliasDto
    {
        $username = $request->request->get('username', '');
        $password = $request->request->get('password', '');

        $this->logger->info('CFI Authenticator: Tentative authentification credentials', [
            'username' => $username,
        ]);

        // Validation basique
        if (empty($username) || empty($password)) {
            throw new AuthenticationException($this->translator->trans('cfi.auth.error.empty_credentials', [], 'security'));
        }

        // Hasher le mot de passe avec SHA-512 et clé de salage CFI
        $hashedPassword = $this->passwordHasher->hashPassword($password);

        $this->logger->debug('CFI Authenticator: Mot de passe hashé', [
            'username' => $username,
            'hash_length' => strlen($hashedPassword),
        ]);

        // Appel API CFI pour authentification avec password hashé
        return $this->cfiAuthService->authenticateWithCredentials($username, $hashedPassword);
    }
}
