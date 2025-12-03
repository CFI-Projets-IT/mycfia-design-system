<?php

declare(strict_types=1);

namespace App\MessageHandler;

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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
    private readonly LoggerInterface $llmLogger;

    public function __construct(
        private StrategyAnalystAgent $strategyAnalyst,
        private CompetitorAnalystAgent $competitorAnalyst,
        private MarketingGenerationPublisher $publisher,
        private EntityManagerInterface $entityManager,
        MarketingLoggerFactory $loggerFactory,
        #[Autowire(service: 'monolog.logger.llm')]
        LoggerInterface $llmLogger,
    ) {
        $this->logger = $loggerFactory->getGeneralLogger();
        $this->llmLogger = $llmLogger;
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

            // 3. Préparer les options avec contexte d'exécution pour events v3.32.0
            // Le bundle crée AgentExecutionContext::fromArray($options) automatiquement
            $baseOptions = [
                'user_id' => $message->userId,
                'client_id' => null !== $message->tenantId ? (int) $message->tenantId : null,
                'project_id' => $message->projectId,
                'metadata' => [
                    'step' => 'strategy_generation',
                ],
            ];

            // 4. Générer l'analyse concurrentielle si demandée (vraie API)
            $competitorAnalysisData = null;
            if ($message->includeCompetitorAnalysis && ! empty($message->additionalContext)) {
                $this->publisher->publishProgress(
                    $message->projectId,
                    'strategy',
                    'Analyse concurrentielle en cours...'
                );

                // Vraie API avec contexte v3.32.0 : analyzeCompetitor(market, competitors, dimensions, options)
                $competitorOptions = array_merge($baseOptions, [
                    'metadata' => ['step' => 'competitor_analysis'],
                ]);
                $competitorAnalysisData = $this->competitorAnalyst->analyzeCompetitor(
                    market: $project->getDescription() ?: $project->getGoalType()->value,
                    competitors: array_filter(explode(',', $message->additionalContext)),
                    dimensions: ['positioning', 'strengths', 'weaknesses', 'messaging'],
                    options: $competitorOptions
                );

                // Log centralisé LLM pour Grafana (analyse concurrentielle)
                $this->llmLogger->info('Campaign LLM Call', [
                    'step' => 'competitor_analysis',
                    'user_id' => $message->userId,
                    'project_id' => $message->projectId,
                    'tenant_id' => $message->tenantId,
                    'competitors_count' => count($competitorAnalysisData['competitors'] ?? []),
                    'tokens_input' => $competitorAnalysisData['tokens_used']['input'] ?? 0,
                    'tokens_output' => $competitorAnalysisData['tokens_used']['output'] ?? 0,
                    'total_tokens' => $competitorAnalysisData['tokens_used']['total'] ?? 0,
                    'duration_ms' => $competitorAnalysisData['duration_ms'] ?? 0,
                    'model' => $competitorAnalysisData['model_used'] ?? 'unknown',
                ]);
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

            // Vraie API avec contexte v3.32.0 : analyzeStrategy(sector, objectives, context, options)
            $strategyData = $this->strategyAnalyst->analyzeStrategy(
                sector: $project->getDescription() ?: $project->getGoalType()->value,
                objectives: $objectives,
                context: $context,
                options: $baseOptions
            );

            // Log centralisé LLM pour Grafana (génération stratégie)
            $this->llmLogger->info('Campaign LLM Call', [
                'step' => 'strategy_generation',
                'user_id' => $message->userId,
                'project_id' => $message->projectId,
                'tenant_id' => $message->tenantId,
                'tokens_input' => $strategyData['tokens_used']['input'] ?? 0,
                'tokens_output' => $strategyData['tokens_used']['output'] ?? 0,
                'total_tokens' => $strategyData['tokens_used']['total'] ?? 0,
                'duration_ms' => $strategyData['duration_ms'] ?? 0,
                'model' => $strategyData['model_used'] ?? 'unknown',
            ]);

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

            // 6. Stocker l'analyse concurrentielle dans Project si présente
            if (null !== $competitorAnalysisData) {
                $project->setCompetitiveMarketOverview($competitorAnalysisData['market_overview'] ?? null);
                $project->setCompetitiveThreats($competitorAnalysisData['threats'] ?? null);
                $project->setCompetitiveOpportunities($competitorAnalysisData['opportunities'] ?? null);
                $project->setCompetitiveRecommendations($competitorAnalysisData['recommendations'] ?? null);
                $project->setCompetitiveAnalysisGeneratedAt(new \DateTimeImmutable());
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
