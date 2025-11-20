<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Asset;
use App\Entity\Project;
use App\Enum\AssetStatus;
use App\Enum\ProjectStatus;
use App\Message\GenerateAssetsMessage;
use App\Service\MarketingGenerationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Agent\ContentCreatorAgent;
use Gorillias\MarketingBundle\Service\MarketingLoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asynchrone pour la génération d'assets marketing multi-canal par IA.
 *
 * Responsabilités :
 * - Récupérer le projet et sa stratégie depuis la base de données
 * - Appeler ContentCreatorAgent pour génération assets (8 builders disponibles)
 * - Persister les assets générés dans la base (status DRAFT)
 * - Mettre à jour le statut du projet (STRATEGY_GENERATED → ASSETS_GENERATING → ASSETS_GENERATED)
 * - Publier des événements Mercure pour notification temps réel
 * - Gérer les erreurs et logger l'exécution
 *
 * Architecture :
 * - Traite les messages GenerateAssetsMessage de manière asynchrone
 * - Exécute la génération via ContentCreatorAgent + AssetBuilders (Mistral Large)
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifie l'utilisateur en temps réel via Mercure
 *
 * Builders disponibles : GoogleAds, LinkedinPost, FacebookPost, InstagramPost,
 *                        Mail, BingAds, IabAsset, ArticleAsset
 *
 * Durée estimée : ~20 secondes par asset (parallélisable)
 * Coût estimé : ~$0.0037-0.0233 par asset selon type
 */
#[AsMessageHandler]
final readonly class GenerateAssetsMessageHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private ContentCreatorAgent $contentCreator,
        private MarketingGenerationPublisher $publisher,
        private EntityManagerInterface $entityManager,
        MarketingLoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->getGeneralLogger();
    }

    /**
     * Traiter le message de génération d'assets de manière asynchrone.
     *
     * @param GenerateAssetsMessage $message Message contenant les paramètres de génération
     */
    public function __invoke(GenerateAssetsMessage $message): void
    {
        $startTime = microtime(true);

        try {
            // 1. Récupérer le projet depuis la base de données
            $project = $this->entityManager->getRepository(Project::class)->find($message->projectId);
            if (null === $project) {
                throw new \RuntimeException(sprintf('Project with ID %d not found', $message->projectId));
            }

            // Récupérer la première stratégie (ou la plus récente)
            $strategies = $project->getStrategies();
            if ($strategies->isEmpty()) {
                throw new \RuntimeException(sprintf('No strategy found for project %d', $message->projectId));
            }
            $strategy = $strategies->first();

            $totalAssets = count($message->assetTypes) * $message->numberOfVariations;

            $this->logger->info('GenerateAssetsMessageHandler: Starting assets generation', [
                'project_id' => $message->projectId,
                'user_id' => $message->userId,
                'tenant_id' => $message->tenantId,
                'asset_types' => $message->assetTypes,
                'variations' => $message->numberOfVariations,
                'total_assets' => $totalAssets,
            ]);

            // 2. Publier événement de démarrage
            $this->publisher->publishStart(
                $message->projectId,
                'assets',
                sprintf('Génération de %d assets marketing en cours...', $totalAssets)
            );

            // 3. Générer les assets pour chaque type demandé
            $generatedCount = 0;

            foreach ($message->assetTypes as $assetType) {
                for ($variation = 1; $variation <= $message->numberOfVariations; ++$variation) {
                    $this->publisher->publishProgress(
                        $message->projectId,
                        'assets',
                        sprintf('Génération asset %d/%d : %s (variation %d)...', $generatedCount + 1, $totalAssets, $assetType, $variation),
                        [
                            'progress' => round(($generatedCount / $totalAssets) * 100, 2),
                            'current_type' => $assetType,
                            'current_variation' => $variation,
                        ]
                    );

                    // Préparer le brief pour ContentCreatorAgent (vraie API)
                    $brief = [
                        'persona' => sprintf(
                            'Persona du projet %s',
                            $project->getDescription() ?: $project->getGoalType()->value
                        ),
                        'objectif' => $project->getGoalType()->value,
                        'tone' => $message->toneOfVoice ?: 'professionnel',
                        'context' => sprintf(
                            'Stratégie: %s. Contexte: %s',
                            $strategy->getPositioning() ?: '',
                            $message->additionalContext
                        ),
                    ];

                    // Vraie API : createContent(assetType, brief, options)
                    $assetData = $this->contentCreator->createContent(
                        assetType: $assetType,
                        brief: $brief,
                        options: []
                    );

                    // Persister l'asset généré (mapper vers vrais champs TEXT)
                    $asset = new Asset();
                    $asset->setProject($project);

                    // Champs de l'entité Asset
                    $asset->setAssetType($assetType);
                    $asset->setChannel($assetType);  // Même valeur pour assetType et channel
                    $asset->setContent(json_encode($assetData, JSON_THROW_ON_ERROR));  // Tout le contenu en JSON
                    $asset->setVariations(null);  // Pas de variations pour l'instant
                    $asset->setStatus(AssetStatus::DRAFT);

                    // Calculer le score de qualité via l'analyse du bundle (retourne 0-100, converti en 0-1)
                    $qualityScore = $this->contentCreator->analyzeContentQuality($assetData) / 100;
                    $asset->setQualityScore((string) $qualityScore);

                    // createdAt/updatedAt gérés automatiquement par Gedmo (#[Gedmo\Timestampable])

                    $this->entityManager->persist($asset);

                    ++$generatedCount;
                }
            }

            // 4. Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::ASSETS_GENERATED);
            $this->entityManager->flush();

            // 5. Publier événement de succès
            $duration = (microtime(true) - $startTime) * 1000;

            $this->publisher->publishComplete(
                $message->projectId,
                'assets',
                sprintf('%d assets générés avec succès ! Vous pouvez maintenant les valider.', $generatedCount),
                [
                    'assets_count' => $generatedCount,
                    'asset_types' => $message->assetTypes,
                    'duration_ms' => round($duration, 2),
                ]
            );

            $this->logger->info('GenerateAssetsMessageHandler: Assets generation completed successfully', [
                'project_id' => $message->projectId,
                'assets_count' => $generatedCount,
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('GenerateAssetsMessageHandler: Assets generation failed', [
                'project_id' => $message->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->publisher->publishError(
                $message->projectId,
                'assets',
                'Échec de la génération des assets. Veuillez réessayer.',
                $e->getMessage()
            );

            throw $e;
        }
    }
}
