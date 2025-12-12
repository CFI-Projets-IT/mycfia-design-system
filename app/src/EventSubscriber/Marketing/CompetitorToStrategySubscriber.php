<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Repository\ProjectRepository;
use App\Service\MarketingGenerationPublisher;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Chaîne automatiquement l'analyse concurrentielle et la génération de stratégie.
 *
 * Workflow :
 * 1. CompetitorAnalystAgent termine son analyse
 * 2. Ce subscriber récupère le résultat (competitor_analysis)
 * 3. Injecte competitor_analysis dans le context
 * 4. Dispatch StrategyAnalystAgent avec context enrichi
 *
 * Conforme au guide Marketing AI Bundle :
 * - CompetitorAnalyst TOUJOURS appelé en premier (obligatoire)
 * - StrategyAnalyst reçoit competitor_analysis dans context
 * - Fonctionne avec ou sans concurrents fournis (détection auto)
 */
final readonly class CompetitorToStrategySubscriber implements EventSubscriberInterface
{
    public function __construct(
        private AgentTaskManager $agentTaskManager,
        private LoggerInterface $logger,
        private ProjectRepository $projectRepository,
        private MarketingGenerationPublisher $marketingGenerationPublisher,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité haute (20) pour s'exécuter avant la persistance de la stratégie
            TaskCompletedEvent::class => ['onCompetitorAnalysisCompleted', 20],
        ];
    }

    /**
     * Chaîne l'analyse concurrentielle vers la génération de stratégie.
     *
     * Filtre uniquement les événements CompetitorAnalystAgent qui ont
     * strategy_context dans le context (indique qu'il faut continuer vers stratégie).
     */
    public function onCompetitorAnalysisCompleted(TaskCompletedEvent $event): void
    {
        $this->logger->info('🔍 TRACE: CompetitorToStrategySubscriber appelé', [
            'agent_name' => $event->agentName,
            'task_id' => $event->taskId,
        ]);

        // Filtrer : seulement si c'est CompetitorAnalystAgent
        if (! str_contains($event->agentName, 'CompetitorAnalystAgent')) {
            $this->logger->info('🔍 TRACE: Agent ignoré (pas CompetitorAnalystAgent)', [
                'agent_name' => $event->agentName,
            ]);

            return;
        }

        $taskId = $event->taskId;
        $competitorAnalysisResult = $event->result;
        $context = $event->context;

        // Extraire project_id depuis context (copié automatiquement par le bundle)
        $projectId = $context['project_id'] ?? null;
        $sector = $context['market'] ?? 'Unknown';

        $this->logger->info('CompetitorToStrategySubscriber: Competitor analysis completed', [
            'task_id' => $taskId,
            'agent_name' => $event->agentName,
            'context_keys' => array_keys($context),
            'project_id' => $projectId,
            'market' => $sector,
        ]);

        if (! $projectId) {
            $this->logger->error('CompetitorToStrategySubscriber: Missing project_id in context', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Vérifier que l'analyse concurrentielle a réussi
        if (! is_array($competitorAnalysisResult) || empty($competitorAnalysisResult)) {
            $this->logger->error('CompetitorToStrategySubscriber: Competitor analysis result is empty', [
                'task_id' => $taskId,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données pour construire le context
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('CompetitorToStrategySubscriber: Project not found', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Construire le context de stratégie à partir des données du projet
            $strategyContext = $this->buildStrategyContext($project);
            $strategyObjectives = ['main' => $project->getDetailedObjectives()];

            // ÉTAPE 2 : Injecter competitor_analysis dans le context (v3.34.0)
            // Récupérer l'analyse globale depuis Project
            $globalAnalysis = [
                'market_overview' => $project->getCompetitiveMarketOverview(),
                'threats' => $project->getCompetitiveThreats(),
                'opportunities' => $project->getCompetitiveOpportunities(),
                'recommendations' => $project->getCompetitiveRecommendations(),
            ];

            // Fusionner avec le résultat LLM (backward compatibility)
            $strategyContext['competitor_analysis'] = array_merge($competitorAnalysisResult, $globalAnalysis);

            // v3.34.0 : Récupérer les concurrents sélectionnés depuis les entités Competitor
            // Le template strategy_analyst_user.md.twig attend context.competitors - envoyer TOUTES les données rawData
            $selectedCompetitors = $project->getCompetitors()->filter(fn ($c) => $c->isSelected())->toArray();
            if (! empty($selectedCompetitors)) {
                $strategyContext['competitors'] = array_map(function ($c) {
                    // Fusionner rawData (contient position, keyword_source, etc.) avec validation manuelle
                    $rawData = $c->getRawData() ?? [];
                    $rawData['validation'] = [
                        'alignmentScore' => $c->getAlignmentScore(),
                        'reasoning' => $c->getReasoning(),
                        'offeringOverlap' => $c->getOfferingOverlap(),
                        'marketOverlap' => $c->getMarketOverlap(),
                        'geoOverlap' => $rawData['validation']['geoOverlap'] ?? 'N/A',
                    ];

                    return $rawData;
                }, $selectedCompetitors);
            }

            $this->logger->info('CompetitorToStrategySubscriber: Dispatching strategy analysis with competitor context', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'sector' => $project->getSector(),
                'competitors_from_entity' => count($strategyContext['competitors'] ?? []),
                'competitors_injected_v3_28' => isset($strategyContext['competitors']),
            ]);

            // ÉTAPE 3 : Dispatcher la génération de stratégie avec context enrichi
            $strategyTaskId = $this->agentTaskManager->dispatchStrategyAnalysis(
                sector: $project->getSector(),
                objectives: $strategyObjectives,
                context: $strategyContext, // ✅ Contient competitor_analysis
                options: [
                    'user_id' => $context['user_id'] ?? null,
                    'project_id' => $projectId,
                    'competitor_task_id' => $taskId, // Traçabilité
                ]
            );

            $this->logger->info('CompetitorToStrategySubscriber: Strategy analysis dispatched successfully', [
                'competitor_task_id' => $taskId,
                'strategy_task_id' => $strategyTaskId,
                'project_id' => $projectId,
            ]);

            // Publier événement Mercure pour informer l'UI que la stratégie a démarré
            $this->marketingGenerationPublisher->publishStart(
                $projectId,
                'strategy',
                'Génération de la stratégie marketing en cours...'
            );
        } catch (\Throwable $e) {
            $this->logger->error('CompetitorToStrategySubscriber: Failed to dispatch strategy analysis', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw pour que la tâche soit marquée en erreur
        }
    }

    /**
     * Construit le context de stratégie à partir des données du projet.
     *
     * Récupère toutes les données nécessaires pour générer la stratégie marketing :
     * - Informations projet (nom, entreprise, URLs)
     * - Personas sélectionnés
     * - Budget et durée
     * - Canaux marketing
     *
     * @return array<string, mixed>
     */
    private function buildStrategyContext(\App\Entity\Project $project): array
    {
        $this->logger->info('🔍 TRACE: buildStrategyContext appelé', [
            'project_id' => $project->getId(),
            'project_name' => $project->getName(),
        ]);

        // Récupérer les personas sélectionnés uniquement
        $personasData = [];
        $selectedPersonas = $project->getPersonas()->filter(fn ($p) => $p->isSelected());

        foreach ($selectedPersonas as $persona) {
            $rawData = $persona->getRawData() ?? [];
            $personasData[] = [
                'id' => $persona->getId(),
                'name' => $persona->getName(),
                'age' => $persona->getAge(),
                'gender' => $persona->getGender(),
                'job' => $persona->getJob(),
                'description' => $persona->getDescription(),
                'demographics' => $rawData['demographics'] ?? [],
                'behaviors' => $rawData['behaviors'] ?? [],
                'pain_points' => $rawData['pain_points'] ?? [],
                'goals' => $rawData['goals'] ?? [],
                'selected' => true, // Requis par StrategyAnalystAgent du bundle
            ];
        }

        // Récupérer les IDs des personas sélectionnés
        $personasIds = array_filter(
            array_map(fn ($p) => $p->getId(), $selectedPersonas->toArray()),
            fn ($id) => null !== $id
        );

        // Récupérer les canaux marketing
        $channels = $project->getSelectedAssetTypes() ?? [];

        $scrapedContent = $project->getScrapedContent();
        // TODO: getProjectContext() n'existe pas encore dans l'entité Project
        $projectContext = null;

        $this->logger->info('🔍 TRACE: Données récupérées depuis le projet', [
            'project_id' => $project->getId(),
            'has_scraped_content' => null !== $scrapedContent,
            'scraped_language' => $scrapedContent['metadata']['language'] ?? 'N/A',
        ]);

        return [
            'project_id' => $project->getId(),
            'project_name' => $project->getName(),
            'company_name' => $project->getCompanyName(),
            'personas_ids' => $personasIds,
            'personas' => $personasData,
            'budget' => (int) ((float) $project->getBudget() * 100), // Centimes
            'duration_days' => $project->getStartDate()->diff($project->getEndDate())->days,
            'website_url' => $project->getWebsiteUrl(),
            'selected_asset_types' => $project->getSelectedAssetTypes(),
            'selected_channels' => $channels,
            'scraped_content' => $scrapedContent,
            'project_context' => $projectContext,
        ];
    }
}
