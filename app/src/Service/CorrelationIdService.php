<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service de gestion des IDs de corrélation pour traçabilité cross-service.
 *
 * Permet de suivre une requête utilisateur à travers tous les layers :
 * Controller → ChatService → Tools → ApiServices → CfiApiService
 *
 * Pattern d'utilisation :
 * 1. Générer un ID au début d'une requête HTTP ou tâche async
 * 2. Récupérer l'ID dans tous les services pour logger
 * 3. Tous les logs liés auront le même correlation_id
 *
 * Exemple de recherche :
 * grep "correlation_id\":\"req_678abc\"" var/log/symfony/*.log
 */
final class CorrelationIdService
{
    private ?string $currentId = null;

    /**
     * Générer un nouvel ID de corrélation unique.
     *
     * Format : req_{timestamp}_{random}
     * Exemple : req_1706635845_abc123def
     *
     * À appeler au début d'une requête HTTP ou tâche Messenger.
     */
    public function generate(): string
    {
        $this->currentId = sprintf(
            'req_%s_%s',
            time(),
            bin2hex(random_bytes(4))
        );

        return $this->currentId;
    }

    /**
     * Récupérer l'ID de corrélation actuel.
     *
     * Retourne null si aucun ID n'a été généré pour cette requête.
     * Les services doivent vérifier si non-null avant de logger.
     */
    public function get(): ?string
    {
        return $this->currentId;
    }

    /**
     * Définir manuellement un ID de corrélation.
     *
     * Utile pour :
     * - Propager un ID reçu depuis un système externe
     * - Tester avec un ID spécifique
     */
    public function set(string $correlationId): void
    {
        $this->currentId = $correlationId;
    }

    /**
     * Réinitialiser l'ID de corrélation (pour tests).
     */
    public function reset(): void
    {
        $this->currentId = null;
    }

    /**
     * Récupérer ou générer un ID de corrélation.
     *
     * Si un ID existe déjà, le retourner.
     * Sinon, en générer un nouveau automatiquement.
     *
     * Pratique pour les services qui ne savent pas si l'ID a été initialisé.
     */
    public function getOrGenerate(): string
    {
        if (null === $this->currentId) {
            return $this->generate();
        }

        return $this->currentId;
    }
}
