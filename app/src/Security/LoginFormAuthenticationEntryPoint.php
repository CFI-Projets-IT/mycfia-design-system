<?php

declare(strict_types=1);

namespace App\Security;

use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

/**
 * Entry point pour rediriger les utilisateurs non authentifiés vers /login.
 *
 * Cette classe gère la redirection des utilisateurs non authentifiés
 * qui tentent d'accéder à des ressources protégées.
 */
class LoginFormAuthenticationEntryPoint implements AuthenticationEntryPointInterface
{
    public function __construct(
        private readonly UrlGeneratorInterface $urlGenerator
    ) {
    }

    /**
     * Lance le processus d'authentification en redirigeant vers /login.
     */
    public function start(Request $request, ?AuthenticationException $authException = null): Response
    {
        // Rediriger vers la page de connexion
        return new RedirectResponse($this->urlGenerator->generate('app_login'));
    }
}
