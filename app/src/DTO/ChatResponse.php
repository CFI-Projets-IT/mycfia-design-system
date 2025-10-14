<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * DTO représentant la réponse d'un agent IA conversationnel.
 *
 * Structure optimisée pour :
 * - Affichage en interface (answer + metadata)
 * - Validation des "cartes preuve" (sources, links)
 * - Support streaming Mercure (message_id)
 * - Traçabilité complète (tools_used, duration_ms)
 */
final readonly class ChatResponse
{
    /**
     * @param string               $answer      Réponse textuelle de l'agent IA
     * @param array<string, mixed> $metadata    Métadonnées de la réponse (division, user, timestamp)
     * @param array<int, string>   $toolsUsed   Liste des tools appelés durant le traitement
     * @param array<int, mixed>    $proofCards  Données brutes pour génération des cartes preuve
     * @param int                  $durationMs  Durée totale du traitement (ms)
     * @param string|null          $messageId   ID unique pour streaming Mercure (UUID v4)
     * @param array<string, mixed> $rawResponse Réponse complète de l'agent (debugging)
     */
    public function __construct(
        public string $answer,
        public array $metadata = [],
        public array $toolsUsed = [],
        public array $proofCards = [],
        public int $durationMs = 0,
        public ?string $messageId = null,
        public array $rawResponse = [],
    ) {
    }

    /**
     * Sérialiser en tableau pour réponse JSON.
     *
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'answer' => $this->answer,
            'metadata' => $this->metadata,
            'tools_used' => $this->toolsUsed,
            'proof_cards' => $this->proofCards,
            'duration_ms' => $this->durationMs,
            'message_id' => $this->messageId,
        ];
    }

    /**
     * Créer une instance depuis une réponse d'agent Symfony AI.
     *
     * @param mixed                $agentResponse Réponse brute de AgentInterface
     * @param array<string, mixed> $metadata      Métadonnées contextuelles (user, division, timestamp)
     * @param int                  $durationMs    Durée du traitement (ms)
     */
    public static function fromAgentResponse(
        mixed $agentResponse,
        array $metadata = [],
        int $durationMs = 0,
    ): self {
        // TODO Sprint S1+: Parser la réponse agent pour extraire toolsUsed et proofCards
        // Format attendu : { "answer": "...", "tool_calls": [...], "metadata": {...} }

        $answer = is_string($agentResponse) ? $agentResponse : 'Réponse invalide de l\'agent';

        return new self(
            answer: $answer,
            metadata: $metadata,
            toolsUsed: [],
            proofCards: [],
            durationMs: $durationMs,
            messageId: null,
            rawResponse: is_array($agentResponse) ? $agentResponse : ['raw' => $agentResponse],
        );
    }
}
