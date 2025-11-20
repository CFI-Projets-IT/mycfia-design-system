<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\CompetitorAnalysis;
use App\Entity\Project;
use App\Entity\Strategy;
use App\Enum\ProjectStatus;
use App\Message\GenerateStrategyMessage;
use App\Service\MarketingGenerationPublisher;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Agent\CompetitorAnalystAgent;
use Gorillias\MarketingBundle\Agent\StrategyAnalystAgent;
use Gorillias\MarketingBundle\Service\MarketingLoggerFactory;
use Psr\Log\LoggerInterface;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asynchrone pour la génération de stratégie marketing par IA.
 *
 * Responsabilités :
 * - Récupérer le projet et ses personas depuis la base de données
 * - Appeler StrategyAnalystAgent pour génération stratégie
 * - Appeler CompetitorAnalystAgent si demandé (analyse concurrentielle)
 * - Persister la stratégie et analyse concurrentielle dans la base
 * - Mettre à jour le statut du projet (PERSONA_GENERATED → STRATEGY_GENERATED)
 * - Publier des événements Mercure pour notification temps réel
 * - Gérer les erreurs et logger l'exécution
 *
 * Architecture :
 * - Traite les messages GenerateStrategyMessage de manière asynchrone
 * - Exécute la génération via StrategyAnalystAgent + CompetitorAnalystAgent (Mistral Large)
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 * - Notifie l'utilisateur en temps réel via Mercure
 *
 * Durée estimée : 30-45 secondes selon analyse concurrentielle
 * Coût estimé : ~$0.006-0.010 par génération
 */
#[AsMessageHandler]
final readonly class GenerateStrategyMessageHandler
{
    private readonly LoggerInterface $logger;

    public function __construct(
        private StrategyAnalystAgent $strategyAnalyst,
        private CompetitorAnalystAgent $competitorAnalyst,
        private MarketingGenerationPublisher $publisher,
        private EntityManagerInterface $entityManager,
        MarketingLoggerFactory $loggerFactory,
    ) {
        $this->logger = $loggerFactory->getGeneralLogger();
    }

    /**
     * Traiter le message de génération de stratégie de manière asynchrone.
     *
     * @param GenerateStrategyMessage $message Message contenant les paramètres de génération
     */
    public function __invoke(GenerateStrategyMessage $message): void
    {
        $startTime = microtime(true);

        try {
            // 1. Récupérer le projet depuis la base de données
            $project = $this->entityManager->getRepository(Project::class)->find($message->projectId);
            if (null === $project) {
                throw new \RuntimeException(sprintf('Project with ID %d not found', $message->projectId));
            }

            $this->logger->info('GenerateStrategyMessageHandler: Starting strategy generation', [
                'project_id' => $message->projectId,
                'user_id' => $message->userId,
                'tenant_id' => $message->tenantId,
                'include_competitor' => $message->includeCompetitorAnalysis,
            ]);

            // 2. Publier événement de démarrage
            $this->publisher->publishStart(
                $message->projectId,
                'strategy',
                'Génération de la stratégie marketing en cours...'
            );

            // 3. Générer l'analyse concurrentielle si demandée (vraie API)
            $competitorAnalysisData = null;
            if ($message->includeCompetitorAnalysis && ! empty($message->additionalContext)) {
                $this->publisher->publishProgress(
                    $message->projectId,
                    'strategy',
                    'Analyse concurrentielle en cours...'
                );

                // Vraie API : analyzeCompetitor(market, competitors, dimensions, options)
                $competitorAnalysisData = $this->competitorAnalyst->analyzeCompetitor(
                    market: $project->getDescription() ?: $project->getGoalType()->value,
                    competitors: array_filter(explode(',', $message->additionalContext)),
                    dimensions: ['positioning', 'strengths', 'weaknesses', 'messaging'],
                    options: []
                );
            }

            // 4. Préparer les données pour la stratégie
            $this->publisher->publishProgress(
                $message->projectId,
                'strategy',
                'Élaboration de la stratégie marketing...'
            );

            // Préparer objectives depuis le projet
            $objectives = [
                $project->getGoalType()->value => $project->getDescription() ?: 'Objectif principal',
            ];

            // Préparer contexte avec budget, timeline, etc.
            $context = [
                'budget' => $project->getBudget(),
                'constraints' => $message->additionalContext,
            ];

            if (! empty($message->focusChannels)) {
                $context['preferred_channels'] = $message->focusChannels;
            }

            if (null !== $competitorAnalysisData) {
                $context['competitor_insights'] = $competitorAnalysisData['market_overview'];
            }

            // Vraie API : analyzeStrategy(sector, objectives, context, options)
            $strategyData = $this->strategyAnalyst->analyzeStrategy(
                sector: $project->getDescription() ?: $project->getGoalType()->value,
                objectives: $objectives,
                context: $context,
                options: []
            );

            // 5. Persister la stratégie (mapper vers vrais champs TEXT de l'entité)
            $this->publisher->publishProgress(
                $message->projectId,
                'strategy',
                'Enregistrement de la stratégie...'
            );

            $strategy = new Strategy();
            $strategy->setProject($project);

            // Champs TEXT stockant JSON/texte
            $strategy->setPositioning($strategyData['strategy']);
            $strategy->setKeyMessages(json_encode($strategyData['tactics'], JSON_THROW_ON_ERROR));
            $strategy->setRecommendedChannels(json_encode($message->focusChannels, JSON_THROW_ON_ERROR));
            $strategy->setTimeline(json_encode(['start' => 'now', 'duration' => 'project'], JSON_THROW_ON_ERROR));
            $strategy->setBudgetAllocation(json_encode(['total' => $project->getBudget()], JSON_THROW_ON_ERROR));
            $strategy->setKpis(json_encode($strategyData['kpis'], JSON_THROW_ON_ERROR));

            // Calculer le score de confiance via l'analyse du bundle (retourne 0-100, converti en 0-1)
            $confidenceScore = $this->strategyAnalyst->analyzeStrategyQuality($strategyData) / 100;
            $strategy->setQualityScore((string) $confidenceScore);

            // createdAt géré automatiquement par Gedmo (#[Gedmo\Timestampable(on: 'create')])

            $this->entityManager->persist($strategy);

            // 6. Persister l'analyse concurrentielle si présente (mapper vers vrais champs TEXT)
            if (null !== $competitorAnalysisData) {
                $competitorAnalysis = new CompetitorAnalysis();
                $competitorAnalysis->setProject($project);

                // Champs TEXT stockant JSON/texte
                $competitorAnalysis->setCompetitors(json_encode($competitorAnalysisData['competitors'], JSON_THROW_ON_ERROR));
                $competitorAnalysis->setStrengths(json_encode($competitorAnalysisData['competitors'], JSON_THROW_ON_ERROR));
                $competitorAnalysis->setWeaknesses(json_encode($competitorAnalysisData['threats'], JSON_THROW_ON_ERROR));
                $competitorAnalysis->setMarketPositioning($competitorAnalysisData['market_overview']);
                $competitorAnalysis->setDifferentiationOpportunities(json_encode($competitorAnalysisData['opportunities'], JSON_THROW_ON_ERROR));
                $competitorAnalysis->setMarketingStrategies(json_encode($competitorAnalysisData['recommendations'], JSON_THROW_ON_ERROR));

                // createdAt/updatedAt gérés automatiquement par Gedmo

                $this->entityManager->persist($competitorAnalysis);
            }

            // 7. Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::STRATEGY_GENERATED);
            $this->entityManager->flush();

            // 8. Publier événement de succès
            $duration = (microtime(true) - $startTime) * 1000;

            $this->publisher->publishComplete(
                $message->projectId,
                'strategy',
                'Stratégie marketing générée avec succès !',
                [
                    'has_competitor_analysis' => null !== $competitorAnalysisData,
                    'duration_ms' => round($duration, 2),
                ]
            );

            $this->logger->info('GenerateStrategyMessageHandler: Strategy generation completed successfully', [
                'project_id' => $message->projectId,
                'has_competitor_analysis' => null !== $competitorAnalysisData,
                'duration_ms' => round($duration, 2),
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('GenerateStrategyMessageHandler: Strategy generation failed', [
                'project_id' => $message->projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            $this->publisher->publishError(
                $message->projectId,
                'strategy',
                'Échec de la génération de la stratégie. Veuillez réessayer.',
                $e->getMessage()
            );

            throw $e;
        }
    }
}
