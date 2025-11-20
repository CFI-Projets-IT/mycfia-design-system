<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message Messenger pour génération asynchrone de personas marketing par IA.
 *
 * Ce message est dispatché par PersonaController et traité par GeneratePersonasMessageHandler
 * pour exécuter la génération en arrière-plan via PersonaGeneratorAgent.
 *
 * Responsabilités :
 * - Transporter les paramètres de génération (projectId, numberOfPersonas, context)
 * - Être sérialisable pour passage entre processes (synchrone → asynchrone)
 * - Inclure userId et tenantId pour contexte async (pas de session)
 *
 * Architecture :
 * - Dispatché par PersonaController::generate() après validation formulaire
 * - Traité par GeneratePersonasMessageHandler de manière asynchrone
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifications temps réel via Mercure (MarketingGenerationPublisher)
 */
final readonly class GeneratePersonasMessage
{
    public function __construct(
        public int $projectId,
        public int $userId,
        public ?string $tenantId,
        public int $numberOfPersonas,
        public string $additionalContext,
    ) {
    }
}
