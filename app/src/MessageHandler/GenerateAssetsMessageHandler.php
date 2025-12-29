<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\Asset;
use App\Entity\Project;
use App\Enum\AssetStatus;
use App\Enum\ProjectStatus;
use App\Message\GenerateAssetsMessage;
use App\Service\Marketing\AssetImageStorageService;
use App\Service\MarketingGenerationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\AssetBuilder\AbstractAssetBuilder;
use Gorillias\MarketingBundle\Service\MarketingLoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\TaggedIterator;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asynchrone pour la génération d'assets marketing multi-canal par IA.
 *
 * Responsabilités :
 * - Récupérer le projet et sa stratégie depuis la base de données
 * - Appeler les AssetBuilders spécialisés pour génération assets (9 builders disponibles)
 * - Persister les assets générés dans la base (status DRAFT)
 * - Mettre à jour le statut du projet (STRATEGY_GENERATED → ASSETS_GENERATING → ASSETS_GENERATED)
 * - Publier des événements Mercure pour notification temps réel
 * - Gérer les erreurs et logger l'exécution
 *
 * Architecture :
 * - Traite les messages GenerateAssetsMessage de manière asynchrone
 * - Exécute la génération via AssetBuilders spécialisés (Mistral Large)
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifie l'utilisateur en temps réel via Mercure
 *
 * Builders disponibles : GoogleAds, LinkedinPost, FacebookPost, InstagramPost,
 *                        Mail, BingAds, IabAsset, ArticleAsset, SmsAsset
 *
 * Durée estimée : ~20 secondes par asset (parallélisable)
 * Coût estimé : ~$0.0037-0.0233 par asset selon type
 */
#[AsMessageHandler]
final class GenerateAssetsMessageHandler
{
    private readonly LoggerInterface $logger;
    private readonly LoggerInterface $llmLogger;

    /** @var array<string, AbstractAssetBuilder> */
    private array $buildersByType = [];

    /**
     * @param iterable<AbstractAssetBuilder> $assetBuilders
     */
    public function __construct(
        #[TaggedIterator('marketing.asset_builder')]
        iterable $assetBuilders,
        private readonly MarketingGenerationPublisher $publisher,
        private readonly EntityManagerInterface $entityManager,
        private readonly AssetImageStorageService $imageStorage,
        MarketingLoggerFactory $loggerFactory,
        #[Autowire(service: 'monolog.logger.llm')]
        LoggerInterface $llmLogger,
    ) {
        $this->logger = $loggerFactory->getGeneralLogger();
        $this->llmLogger = $llmLogger;

        // Indexer les builders par type d'asset via Réflexion
        // (getAssetType() est protected dans AbstractAssetBuilder)
        foreach ($assetBuilders as $builder) {
            $reflection = new \ReflectionClass($builder);
            $method = $reflection->getMethod('getAssetType');
            $method->setAccessible(true);
            $result = $method->invoke($builder);
            // Gérer à la fois Enum et string, forcer en string pour l'indexation
            $assetTypeRaw = $result instanceof \BackedEnum ? $result->value : $result;
            $assetType = (string) $assetTypeRaw;
            $this->buildersByType[$assetType] = $builder;
        }

        // Log des builders mappés pour debug
        $this->logger->debug('AssetBuilders mappés', [
            'builders' => array_keys($this->buildersByType),
            'count' => count($this->buildersByType),
        ]);
    }

    /**
     * Récupérer le builder approprié pour un type d'asset donné.
     *
     * @param string $assetType Type d'asset (linkedin_post, google_ads, sms, etc.)
     *
     * @return AbstractAssetBuilder Builder spécialisé pour ce type d'asset
     *
     * @throws \RuntimeException Si aucun builder n'est trouvé pour ce type
     */
    private function getBuilder(string $assetType): AbstractAssetBuilder
    {
        if (! isset($this->buildersByType[$assetType])) {
            throw new \RuntimeException(sprintf('Aucun AssetBuilder trouvé pour le type "%s". Types disponibles : %s', $assetType, implode(', ', array_keys($this->buildersByType))));
        }

        $builder = $this->buildersByType[$assetType];
        $this->logger->debug('AssetBuilder récupéré', [
            'asset_type' => $assetType,
            'builder_class' => get_class($builder),
        ]);

        return $builder;
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

            // 3. Préparer les options avec contexte d'exécution pour events v3.32.0
            // Le bundle crée AgentExecutionContext::fromArray($options) automatiquement
            $baseOptions = [
                'user_id' => $message->userId,
                'client_id' => null !== $message->tenantId ? (int) $message->tenantId : null,
                'project_id' => $message->projectId,
                'metadata' => [
                    'step' => 'asset_generation',
                    'total_assets' => $totalAssets,
                ],
            ];

            // 4. Générer les assets pour chaque type demandé
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

                    // Récupérer le builder spécialisé pour ce type d'asset
                    $builder = $this->getBuilder($assetType);

                    // Construire les paramètres requis par le builder
                    $strategyData = [
                        'name' => 'Stratégie marketing',
                        'positioning' => $strategy->getPositioning(),
                        'key_messages' => $strategy->getKeyMessages(),
                        'recommended_channels' => $strategy->getRecommendedChannels(),
                        'timeline' => $strategy->getTimeline(),
                        'budget_allocation' => $strategy->getBudgetAllocationData() ?? [],
                        'kpis' => json_decode($strategy->getKpis(), true) ?? [],
                    ];

                    $projectData = [
                        'project_id' => $project->getId(),
                        'user_id' => $message->userId,
                        'client_id' => null !== $message->tenantId ? (int) $message->tenantId : null,
                        'project_name' => $project->getName(),
                        'company_name' => $project->getCompanyName() ?: $project->getName(),
                        'sector' => $project->getSector() ?: '',
                        'goal_type' => $project->getGoalType()->value,
                        'budget' => $project->getBudget(),
                        'description' => $project->getDescription() ?: '',
                        'website_url' => $project->getWebsiteUrl() ?: '',
                    ];

                    // Agent personnalisé (optionnel)
                    $customAgent = null;
                    if ($message->toneOfVoice || $message->additionalContext) {
                        $customAgent = [
                            'tone_of_voice' => $message->toneOfVoice ?? 'professionnel',
                            'additional_context' => $message->additionalContext,
                        ];
                    }

                    // Options de génération d'images spécifiques à cet asset
                    $assetImageConfig = $message->imageOptions[$assetType] ?? [];
                    $generateImage = isset($assetImageConfig['generate']) && '1' === $assetImageConfig['generate'];
                    $imageStyle = $assetImageConfig['style'] ?? 'flat_illustration';

                    $options = [
                        'generate_images' => $generateImage,
                        'image_style' => $imageStyle,
                    ];

                    // Appel du builder qui retourne la structure spécialisée
                    $assetData = $builder->build($strategyData, $projectData, $customAgent, $options);

                    // Log centralisé LLM pour Grafana (génération asset)
                    $this->llmLogger->info('Campaign LLM Call', [
                        'step' => 'asset_generation',
                        'user_id' => $message->userId,
                        'project_id' => $message->projectId,
                        'tenant_id' => $message->tenantId,
                        'asset_type' => $assetType,
                        'variation' => $variation,
                        'tokens_input' => $assetData['tokens_used']['input'] ?? 0,
                        'tokens_output' => $assetData['tokens_used']['output'] ?? 0,
                        'total_tokens' => $assetData['tokens_used']['total'] ?? 0,
                        'duration_ms' => $assetData['duration_ms'] ?? 0,
                        'model' => $assetData['model_used'] ?? 'unknown',
                    ]);

                    // Traiter l'image générée : stocker sur filesystem au lieu de base64 en BDD
                    if (isset($assetData['image']) && is_array($assetData['image']) && isset($assetData['image']['image_data'])) {
                        try {
                            // Persister d'abord l'asset pour obtenir son ID
                            $asset = new Asset();
                            $asset->setProject($project);
                            $asset->setAssetType($assetType);
                            $asset->setChannel($assetType);
                            $asset->setStatus(AssetStatus::DRAFT);
                            $asset->setQualityScore((string) ($assetData['quality_score'] ?? 0.5));
                            $asset->setContent('{}'); // Temporaire
                            $this->entityManager->persist($asset);
                            $this->entityManager->flush(); // Obtenir l'ID

                            // Stocker l'image sur filesystem
                            $imageFormat = $assetData['image']['format'] ?? 'png';
                            $imageDescription = $assetData['image_description'] ?? '';
                            $storageResult = $this->imageStorage->store(
                                $assetData['image']['image_data'],
                                (int) $asset->getId(),
                                $imageFormat,
                                $imageDescription
                            );

                            // Remplacer les données base64 par l'URL publique
                            $assetData['image_url'] = $storageResult['url'];
                            $assetData['image_path'] = $storageResult['url']; // Alias pour compatibilité
                            $assetData['image_size_bytes'] = $storageResult['size_bytes'];
                            unset($assetData['image']['image_data']); // Supprimer le base64 lourd

                            $this->logger->info('Image asset sauvegardée', [
                                'asset_id' => $asset->getId(),
                                'url' => $storageResult['url'],
                                'size_bytes' => $storageResult['size_bytes'],
                            ]);
                        } catch (\Throwable $e) {
                            $this->logger->error('Échec stockage image asset', [
                                'asset_type' => $assetType,
                                'error' => $e->getMessage(),
                            ]);
                            // Continuer sans image plutôt que de faire échouer toute la génération
                            unset($assetData['image']);
                        }
                    }

                    // Si l'asset n'a pas encore été persisté (pas d'image), le créer maintenant
                    if (! isset($asset)) {
                        $asset = new Asset();
                        $asset->setProject($project);
                        $asset->setAssetType($assetType);
                        $asset->setChannel($assetType);
                        $asset->setStatus(AssetStatus::DRAFT);
                        $asset->setQualityScore((string) ($assetData['quality_score'] ?? 0.5));
                    }

                    // Mettre à jour le contenu avec les données finales (sans base64 si image)
                    $asset->setContent(json_encode($assetData, JSON_THROW_ON_ERROR));

                    // Extraire et encoder les variations si présentes
                    $variations = null;
                    if (isset($assetData['variations']) && is_array($assetData['variations']) && ! empty($assetData['variations'])) {
                        $variations = json_encode($assetData['variations'], JSON_THROW_ON_ERROR);
                    }
                    $asset->setVariations($variations);

                    // Si l'asset n'était pas encore persisté (cas sans image), le persist maintenant
                    if (! $this->entityManager->contains($asset)) {
                        $this->entityManager->persist($asset);
                    }

                    // createdAt/updatedAt gérés automatiquement par Gedmo (#[Gedmo\Timestampable])

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
