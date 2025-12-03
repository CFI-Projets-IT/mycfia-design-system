<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Entity\Competitor;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour persister les concurrents détectés dans la base de données.
 *
 * Écoute l'événement TaskCompletedEvent dispatché par CompetitorIntelligenceTool,
 * extrait la liste des concurrents validés avec leurs métadonnées LLM,
 * et crée des entités Competitor individuelles.
 *
 * Workflow :
 * 1. CompetitorIntelligenceTool détecte concurrents (Phase 1-4)
 * 2. Ce subscriber crée des entités Competitor (selected = false par défaut)
 * 3. Utilisateur sélectionne les concurrents pertinents via CompetitorController::validate()
 * 4. CompetitorAnalystAgent génère l'analyse globale du marché
 * 5. CompetitorAnalysisCompletedEventSubscriber met à jour Project avec analyse globale
 */
final readonly class CompetitorDetectedEventSubscriber implements EventSubscriberInterface
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
            // Priorité 30 pour s'exécuter en premier (avant CompetitorAnalysisCompleted)
            TaskCompletedEvent::class => ['onCompetitorDetected', 30],
        ];
    }

    /**
     * Persiste les concurrents détectés quand TaskCompletedEvent est reçu.
     *
     * Filtre sur l'outil CompetitorIntelligenceTool pour ne traiter que les détections de concurrents.
     */
    public function onCompetitorDetected(TaskCompletedEvent $event): void
    {
        // Filtrer : seulement si c'est CompetitorIntelligenceTool
        if (! str_contains($event->agentName, 'CompetitorIntelligenceTool')) {
            return;
        }

        $taskId = $event->taskId;
        $result = $event->result;
        $context = $event->context;

        $this->logger->info('CompetitorDetectedEventSubscriber received', [
            'task_id' => $taskId,
            'agent_name' => $event->agentName,
            'result_type' => gettype($result),
            'context_keys' => array_keys($context),
        ]);

        // Vérifier qu'on a un résultat valide
        if (! is_array($result) || ! isset($result['competitors']) || empty($result['competitors'])) {
            $this->logger->warning('Competitor detection completed but no competitors found', [
                'task_id' => $taskId,
            ]);

            return;
        }

        // Extraire project_id depuis context
        $projectId = $context['project_id'] ?? null;

        if (! $projectId) {
            $this->logger->error('project_id not found in context for competitor detection', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('Project not found for competitor detection', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Extraire les concurrents depuis le résultat
            $competitors = $result['competitors'];

            $this->logger->info('CompetitorDetectedEventSubscriber processing competitors', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'competitors_count' => count($competitors),
            ]);

            // Créer les entités Competitor
            $count = $this->createCompetitorEntities($project, $competitors, $taskId);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('Competitors created successfully', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'count' => $count,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to create competitors', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Crée des entités Competitor depuis le résultat de CompetitorIntelligenceTool.
     *
     * Structure attendue de chaque concurrent (v3.22.0+) :
     * {
     *   "domain": "example.com",
     *   "title": "Example Corp - Leader SaaS",
     *   "url": "https://example.com",
     *   "validation": {
     *     "alignmentScore": 85,
     *     "isCompetitor": true,
     *     "reasoning": "...",
     *     "offeringOverlap": "High",
     *     "marketOverlap": "Direct"
     *   },
     *   "has_ads": true,
     *   "firecrawl_extract": {...},
     *   "scraping_status": "success"
     * }
     *
     * @param array<int, array<string, mixed>> $competitors
     *
     * @return int Nombre de concurrents créés
     */
    private function createCompetitorEntities(\App\Entity\Project $project, array $competitors, string $taskId): int
    {
        $count = 0;

        foreach ($competitors as $competitorData) {
            // Extraire les données de validation LLM
            $validation = $competitorData['validation'] ?? [];

            // Créer l'entité Competitor
            $competitor = new Competitor();
            $competitor->setProject($project);
            $competitor->setDomain($competitorData['domain'] ?? 'N/A');
            $competitor->setTitle($competitorData['title'] ?? 'N/A');
            $competitor->setUrl($competitorData['url'] ?? null);
            $competitor->setAlignmentScore($validation['alignmentScore'] ?? 0);
            $competitor->setReasoning($validation['reasoning'] ?? null);
            $competitor->setOfferingOverlap($validation['offeringOverlap'] ?? null);
            $competitor->setMarketOverlap($validation['marketOverlap'] ?? null);
            $competitor->setHasAds($competitorData['has_ads'] ?? false);
            $competitor->setRawData($competitorData); // Stocker toutes les métadonnées
            $competitor->setSelected(false); // Par défaut non sélectionné

            $this->entityManager->persist($competitor);
            ++$count;

            $this->logger->debug('Competitor entity created', [
                'task_id' => $taskId,
                'domain' => $competitor->getDomain(),
                'alignment_score' => $competitor->getAlignmentScore(),
            ]);
        }

        return $count;
    }
}
