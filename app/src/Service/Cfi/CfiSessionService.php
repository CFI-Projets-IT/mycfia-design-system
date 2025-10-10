<?php

declare(strict_types=1);

namespace App\Service\Cfi;

use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpFoundation\Session\SessionInterface;

/**
 * Service de gestion de session CFI.
 *
 * Gere le stockage du token CFI (jeton) en session
 * avec gestion automatique du TTL (30 minutes)
 */
class CfiSessionService
{
    private const TOKEN_KEY = 'cfi_jeton';
    private const TOKEN_TIMESTAMP_KEY = 'cfi_jeton_timestamp';
    private const USER_DATA_KEY = 'cfi_user_data';
    private const TENANT_KEY = 'cfi_current_tenant';

    public function __construct(
        private readonly RequestStack $requestStack,
        private readonly int $tokenTtl,
        private readonly int $tokenRefreshThreshold,
    ) {
    }

    /**
     * Stocke le token CFI en session avec timestamp.
     */
    public function setToken(string $jeton): void
    {
        $session = $this->getSession();
        $session->set(self::TOKEN_KEY, $jeton);
        $session->set(self::TOKEN_TIMESTAMP_KEY, time());
    }

    /**
     * Recupere le token CFI depuis la session.
     *
     * @return string|null Token CFI ou null si absent ou expire
     */
    public function getToken(): ?string
    {
        $session = $this->getSession();
        $token = $session->get(self::TOKEN_KEY);

        if (null === $token) {
            return null;
        }

        // Verifier si le token est expire
        if ($this->isTokenExpired()) {
            $this->clear();

            return null;
        }

        return $token;
    }

    /**
     * Verifie si le token CFI est expire (TTL depasse).
     */
    public function isTokenExpired(): bool
    {
        $session = $this->getSession();
        $timestamp = $session->get(self::TOKEN_TIMESTAMP_KEY);

        if (null === $timestamp) {
            return true;
        }

        $age = time() - (int) $timestamp;

        return $age >= $this->tokenTtl;
    }

    /**
     * Verifie si le token doit etre rafraichi (seuil de refresh atteint).
     *
     * Par defaut : 25 minutes sur un TTL de 30 minutes
     */
    public function shouldRefreshToken(): bool
    {
        $session = $this->getSession();
        $timestamp = $session->get(self::TOKEN_TIMESTAMP_KEY);

        if (null === $timestamp) {
            return false;
        }

        $age = time() - (int) $timestamp;

        return $age >= $this->tokenRefreshThreshold;
    }

    /**
     * Stocke les donnees utilisateur en session.
     *
     * @param array<string, mixed> $userData
     */
    public function setUserData(array $userData): void
    {
        $session = $this->getSession();
        $session->set(self::USER_DATA_KEY, $userData);
    }

    /**
     * Recupere les donnees utilisateur depuis la session.
     *
     * @return array<string, mixed>|null
     */
    public function getUserData(): ?array
    {
        $session = $this->getSession();

        return $session->get(self::USER_DATA_KEY);
    }

    /**
     * Stocke l'identifiant du tenant actif.
     */
    public function setCurrentTenant(int $idDivision): void
    {
        $session = $this->getSession();
        $session->set(self::TENANT_KEY, $idDivision);
    }

    /**
     * Recupere l'identifiant du tenant actif.
     */
    public function getCurrentTenant(): ?int
    {
        $session = $this->getSession();

        return $session->get(self::TENANT_KEY);
    }

    /**
     * Efface toutes les donnees CFI de la session.
     */
    public function clear(): void
    {
        $session = $this->getSession();
        $session->remove(self::TOKEN_KEY);
        $session->remove(self::TOKEN_TIMESTAMP_KEY);
        $session->remove(self::USER_DATA_KEY);
        $session->remove(self::TENANT_KEY);
    }

    /**
     * Retourne le temps restant avant expiration du token (en secondes).
     */
    public function getTimeRemaining(): int
    {
        $session = $this->getSession();
        $timestamp = $session->get(self::TOKEN_TIMESTAMP_KEY);

        if (null === $timestamp) {
            return 0;
        }

        $age = time() - (int) $timestamp;
        $remaining = $this->tokenTtl - $age;

        return max(0, $remaining);
    }

    /**
     * Retourne la session courante.
     */
    private function getSession(): SessionInterface
    {
        $request = $this->requestStack->getCurrentRequest();

        if (null === $request) {
            throw new \RuntimeException('No request available in RequestStack');
        }

        return $request->getSession();
    }
}
