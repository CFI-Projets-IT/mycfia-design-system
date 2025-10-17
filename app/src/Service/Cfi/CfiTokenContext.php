<?php

declare(strict_types=1);

namespace App\Service\Cfi;

/**
 * Service pour gérer le token CFI dans différents contextes d'exécution.
 *
 * Responsabilités :
 * - Stockage temporaire du token CFI pour le contexte actuel
 * - Support du contexte asynchrone (Messenger Worker) sans session HTTP
 * - Fallback vers CfiSessionService pour le contexte synchrone (requête web)
 *
 * Architecture :
 * - Contexte synchrone (web) : Utilise CfiSessionService (session HTTP)
 * - Contexte asynchrone (worker) : Utilise propriété $token injectée manuellement
 *
 * Utilisation dans Messenger Worker :
 * 1. ChatController récupère token depuis CfiSessionService
 * 2. Token passé dans ChatStreamMessage
 * 3. ChatStreamMessageHandler injecte token via setToken()
 * 4. Tools IA récupèrent token via getToken()
 *
 * Note : Ce service n'est pas readonly car il doit pouvoir modifier $token
 */
final class CfiTokenContext
{
    /**
     * Token CFI pour contexte asynchrone (null en contexte synchrone).
     */
    private ?string $token = null;

    public function __construct(
        private CfiSessionService $cfiSession,
    ) {
    }

    /**
     * Récupérer le token CFI depuis le contexte approprié.
     *
     * Stratégie de résolution :
     * 1. Si token injecté manuellement (contexte async) → utiliser ce token
     * 2. Sinon, fallback vers CfiSessionService (contexte sync)
     *
     * @return string|null Token CFI ou null si non disponible
     */
    public function getToken(): ?string
    {
        // Contexte asynchrone : token injecté manuellement
        if (null !== $this->token) {
            return $this->token;
        }

        // Contexte synchrone : récupérer depuis session HTTP
        try {
            return $this->cfiSession->getToken();
        } catch (\RuntimeException) {
            // Pas de session disponible (ex: CLI, tests)
            return null;
        }
    }

    /**
     * Injecter manuellement le token CFI pour contexte asynchrone.
     *
     * Utilisé par ChatStreamMessageHandler pour passer le token
     * récupéré depuis la session web au contexte worker.
     *
     * @param string $token Token CFI valide
     */
    public function setToken(string $token): void
    {
        $this->token = $token;
    }

    /**
     * Réinitialiser le token (utile en fin de traitement async).
     */
    public function clearToken(): void
    {
        $this->token = null;
    }

    /**
     * Vérifier si un token est disponible dans le contexte actuel.
     *
     * @return bool True si token disponible (sync ou async)
     */
    public function hasToken(): bool
    {
        return null !== $this->getToken();
    }
}
