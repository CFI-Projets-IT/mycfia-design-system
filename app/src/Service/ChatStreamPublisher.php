<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Service pour publication de messages de chat en streaming via Mercure SSE.
 *
 * Responsabilités :
 * - Publier des chunks de réponse IA progressivement
 * - Publier des événements de statut (start, chunk, complete, error)
 * - Gérer les erreurs de publication Mercure
 * - Logger les événements de streaming pour debug
 *
 * Format des messages :
 * - type: "start" | "chunk" | "complete" | "error"
 * - messageId: UUID v4 pour tracking
 * - conversationId: UUID v4 de la conversation
 * - chunk: Contenu progressif (pour type "chunk")
 * - metadata: Informations contextuelles (pour type "complete")
 */
final readonly class ChatStreamPublisher
{
    public function __construct(
        private HubInterface $hub,
        #[Autowire(service: 'monolog.logger.streaming')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Publier le début d'un streaming de réponse.
     *
     * @param string $conversationId UUID v4 de la conversation
     * @param string $messageId      UUID v4 du message en cours
     * @param string $context        Contexte du chat (factures|commandes|stocks|general)
     */
    public function publishStart(string $conversationId, string $messageId, string $context): void
    {
        $this->publish($conversationId, [
            'type' => 'start',
            'messageId' => $messageId,
            'conversationId' => $conversationId,
            'context' => $context,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('ChatStreamPublisher: Start event published', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'context' => $context,
        ]);
    }

    /**
     * Publier un chunk de réponse IA progressif.
     *
     * @param string $conversationId UUID v4 de la conversation
     * @param string $messageId      UUID v4 du message en cours
     * @param string $chunk          Contenu textuel du chunk
     */
    public function publishChunk(string $conversationId, string $messageId, string $chunk): void
    {
        $this->publish($conversationId, [
            'type' => 'chunk',
            'messageId' => $messageId,
            'chunk' => $chunk,
        ]);

        $this->logger->debug('ChatStreamPublisher: Chunk published', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'chunk_length' => strlen($chunk),
        ]);
    }

    /**
     * Publier la fin du streaming avec métadonnées.
     *
     * @param string              $conversationId UUID v4 de la conversation
     * @param string              $messageId      UUID v4 du message terminé
     * @param array<string,mixed> $metadata       Métadonnées finales (tools_used, duration_ms, etc.)
     */
    public function publishComplete(string $conversationId, string $messageId, array $metadata): void
    {
        $this->publish($conversationId, [
            'type' => 'complete',
            'messageId' => $messageId,
            'metadata' => $metadata,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('ChatStreamPublisher: Complete event published', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Publier une erreur survenue pendant le streaming.
     *
     * @param string $conversationId UUID v4 de la conversation
     * @param string $messageId      UUID v4 du message en erreur
     * @param string $error          Message d'erreur utilisateur
     */
    public function publishError(string $conversationId, string $messageId, string $error): void
    {
        $this->publish($conversationId, [
            'type' => 'error',
            'messageId' => $messageId,
            'error' => $error,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->error('ChatStreamPublisher: Error event published', [
            'conversation_id' => $conversationId,
            'message_id' => $messageId,
            'error' => $error,
        ]);
    }

    /**
     * Publier un message Mercure sur le topic de la conversation.
     *
     * @param string              $conversationId UUID v4 de la conversation (topic Mercure)
     * @param array<string,mixed> $data           Données JSON à publier
     */
    private function publish(string $conversationId, array $data): void
    {
        try {
            $topic = sprintf('chat/%s', $conversationId);
            $update = new Update(
                $topic,
                json_encode($data, JSON_THROW_ON_ERROR),
            );

            $this->hub->publish($update);
        } catch (\Exception $e) {
            $this->logger->error('ChatStreamPublisher: Failed to publish to Mercure', [
                'conversation_id' => $conversationId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
