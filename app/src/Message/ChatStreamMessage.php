<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message Messenger pour exécution asynchrone du streaming chat IA.
 *
 * Ce message est dispatché par le contrôleur et traité par ChatStreamMessageHandler
 * pour exécuter le streaming en arrière-plan sans bloquer la réponse HTTP.
 *
 * Responsabilités :
 * - Transporter les données nécessaires au streaming (question, user, context, conversationId)
 * - Être sérialisable pour passage entre processes (synchrone → asynchrone)
 * - Inclure tenantId pour éviter dépendance à la session en contexte async
 * - Inclure cfiToken pour authentification API CFI en contexte async (pas de session)
 *
 * Architecture :
 * - Dispatché par ChatController::streamMessage() immédiatement après validation
 * - Traité par ChatStreamMessageHandler de manière asynchrone
 * - Permet au contrôleur de retourner "streaming_started" instantanément
 */
final readonly class ChatStreamMessage
{
    public function __construct(
        public string $question,
        public int $userId,
        public int $tenantId,
        public string $context,
        public string $conversationId,
        public string $messageId,
        public string $cfiToken,
    ) {
    }
}
