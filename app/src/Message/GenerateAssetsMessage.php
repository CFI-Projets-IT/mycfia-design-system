<?php

declare(strict_types=1);

namespace App\Message;

/**
 * Message Messenger pour génération asynchrone d'assets marketing multi-canal par IA.
 *
 * Ce message est dispatché par AssetController et traité par GenerateAssetsMessageHandler
 * pour exécuter la génération en arrière-plan via ContentCreatorAgent + 8 AssetBuilders.
 *
 * Responsabilités :
 * - Transporter les paramètres de génération (projectId, assetTypes, variations, tone, context)
 * - Être sérialisable pour passage entre processes (synchrone → asynchrone)
 * - Inclure userId et tenantId pour contexte async (pas de session)
 *
 * Architecture :
 * - Dispatché par AssetController::generate() après validation formulaire
 * - Traité par GenerateAssetsMessageHandler de manière asynchrone
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifications temps réel via Mercure (MarketingGenerationPublisher)
 */
final readonly class GenerateAssetsMessage
{
    /**
     * @param array<int,string>                                   $assetTypes   Types d'assets à générer (google_ads, linkedin_post, etc.)
     * @param array<string,array{generate?:string,style?:string}> $imageOptions Options de génération d'images par type d'asset
     */
    public function __construct(
        public int $projectId,
        public int $userId,
        public ?string $tenantId,
        public array $assetTypes,
        public int $numberOfVariations,
        public ?string $toneOfVoice,
        public string $additionalContext,
        public array $imageOptions = [],
    ) {
    }
}
