<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour persister l'analyse concurrentielle GLOBALE dans Project.
 *
 * Écoute l'événement TaskCompletedEvent dispatché par le CompetitorAnalystAgent,
 * extrait l'analyse globale du marché (market_overview, threats, opportunities, recommendations)
 * et met à jour les champs de Project.
 *
 * Workflow :
 * 1. Utilisateur sélectionne les concurrents via CompetitorController::validate()
 * 2. StrategyController::recap() dispatch CompetitorAnalystAgent
 * 3. CompetitorAnalystAgent génère l'analyse globale du marché
 * 4. Ce subscriber met à jour Project avec l'analyse globale
 * 5. CompetitorToStrategySubscriber chaîne vers StrategyAnalystAgent
 */
final readonly class CompetitorAnalysisCompletedEventSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ProjectRepository $projectRepository,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            // Priorité 25 pour s'exécuter AVANT CompetitorToStrategySubscriber (priorité 20)
            TaskCompletedEvent::class => ['onCompetitorAnalysisCompleted', 25],
        ];
    }

    /**
     * Persiste l'analyse concurrentielle globale dans Project quand TaskCompletedEvent est reçu.
     *
     * Filtre sur l'agent CompetitorAnalystAgent pour ne traiter que les analyses concurrentielles.
     */
    public function onCompetitorAnalysisCompleted(TaskCompletedEvent $event): void
    {
        // Filtrer : seulement si c'est CompetitorAnalystAgent
        if (! str_contains($event->agentName, 'CompetitorAnalystAgent')) {
            return;
        }

        $taskId = $event->taskId;
        $result = $event->result;
        $context = $event->context;

        $this->logger->info('CompetitorAnalysisCompletedEventSubscriber received', [
            'task_id' => $taskId,
            'agent_name' => $event->agentName,
            'result_type' => gettype($result),
            'context_keys' => array_keys($context),
        ]);

        // Vérifier qu'on a un résultat valide
        if (! is_array($result) || empty($result)) {
            $this->logger->warning('Competitor analysis completed but result is empty', [
                'task_id' => $taskId,
            ]);

            return;
        }

        // Extraire project_id depuis context
        $projectId = $context['project_id'] ?? null;

        if (! $projectId) {
            $this->logger->error('project_id not found in context for competitor analysis', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('Project not found for competitor analysis', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Mettre à jour Project avec l'analyse globale
            $this->updateProjectWithCompetitiveAnalysis($project, $result);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('Project competitive analysis updated successfully', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update Project competitive analysis', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Met à jour Project avec l'analyse concurrentielle globale.
     *
     * Structure attendue du résultat CompetitorAnalystAgent (v3.34.0+) :
     * - market_overview : Vue d'ensemble du marché (string narrative)
     * - threats : Menaces concurrentielles globales (array)
     * - opportunities : Opportunités de marché identifiées (array)
     * - recommendations : Recommandations stratégiques (array)
     *
     * @param array<string, mixed> $result
     */
    private function updateProjectWithCompetitiveAnalysis(\App\Entity\Project $project, array $result): void
    {
        // Extraire les données d'analyse globale
        $marketOverview = $result['market_overview'] ?? '';
        $threats = $result['threats'] ?? [];
        $opportunities = $result['opportunities'] ?? [];
        $recommendations = $result['recommendations'] ?? [];

        // Mettre à jour les champs de Project
        $project->setCompetitiveMarketOverview($marketOverview);
        $project->setCompetitiveThreats($threats);
        $project->setCompetitiveOpportunities($opportunities);
        $project->setCompetitiveRecommendations($recommendations);
        $project->setCompetitiveAnalysisGeneratedAt(new \DateTimeImmutable());

        $this->logger->debug('Project updated with competitive analysis', [
            'has_market_overview' => ! empty($marketOverview),
            'has_threats' => ! empty($threats),
            'has_opportunities' => ! empty($opportunities),
            'has_recommendations' => ! empty($recommendations),
        ]);
    }
}
