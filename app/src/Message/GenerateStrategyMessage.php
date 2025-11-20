<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message Messenger pour génération asynchrone de stratégie marketing par IA.
 *
 * Ce message est dispatché par StrategyController et traité par GenerateStrategyMessageHandler
 * pour exécuter la génération en arrière-plan via StrategyAnalystAgent + CompetitorAnalystAgent.
 *
 * Responsabilités :
 * - Transporter les paramètres de génération (projectId, includeCompetitorAnalysis, focusChannels, context)
 * - Être sérialisable pour passage entre processes (synchrone → asynchrone)
 * - Inclure userId et tenantId pour contexte async (pas de session)
 *
 * Architecture :
 * - Dispatché par StrategyController::generate() après validation formulaire
 * - Traité par GenerateStrategyMessageHandler de manière asynchrone
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifications temps réel via Mercure (MarketingGenerationPublisher)
 */
final readonly class GenerateStrategyMessage
{
    /**
     * @param array<int,string> $focusChannels Canaux marketing privilégiés (social, search, display, email, content)
     */
    public function __construct(
        public int $projectId,
        public int $userId,
        public ?string $tenantId,
        public bool $includeCompetitorAnalysis,
        public array $focusChannels,
        public string $additionalContext,
    ) {
    }
}
