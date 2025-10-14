<?php

declare(strict_types=1);

namespace App\Exception;

/**
 * Exception métier pour les erreurs de traitement du chat IA.
 *
 * Centralise toutes les erreurs liées :
 * - Agent IA (timeout, erreur API Mistral, limite de tokens)
 * - Tools (échec d'appel CFI API, données invalides)
 * - Contexte utilisateur (tenant non trouvé, permissions)
 * - Streaming Mercure (connexion perdue, erreur publication)
 */
final class ChatException extends \RuntimeException
{
    /**
     * Créer une exception pour échec d'agent IA.
     */
    public static function agentFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Échec de l'agent IA : {$reason}", 500, $previous);
    }

    /**
     * Créer une exception pour échec d'outil (tool).
     */
    public static function toolFailed(string $toolName, string $reason, ?\Throwable $previous = null): self
    {
        return new self("Échec du tool '{$toolName}' : {$reason}", 500, $previous);
    }

    /**
     * Créer une exception pour contexte utilisateur invalide.
     */
    public static function invalidContext(string $reason): self
    {
        return new self("Contexte utilisateur invalide : {$reason}", 400);
    }

    /**
     * Créer une exception pour échec de streaming Mercure.
     */
    public static function streamingFailed(string $reason, ?\Throwable $previous = null): self
    {
        return new self("Échec du streaming Mercure : {$reason}", 500, $previous);
    }

    /**
     * Créer une exception pour timeout de traitement.
     */
    public static function timeout(int $timeoutSeconds): self
    {
        return new self("Timeout de traitement dépassé ({$timeoutSeconds}s)", 504);
    }
}
