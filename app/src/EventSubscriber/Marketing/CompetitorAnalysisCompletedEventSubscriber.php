<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour persister les résultats d'analyse concurrentielle dans l'entité CompetitorAnalysis.
 *
 * Écoute l'événement TaskCompletedEvent dispatché par le CompetitorAnalystAgent,
 * extrait l'analyse détaillée (strengths, weaknesses, market_positioning, etc.)
 * et met à jour l'entité CompetitorAnalysis existante.
 *
 * Workflow :
 * 1. CompetitorController::validate() crée l'entité CompetitorAnalysis avec champs vides
 * 2. StrategyController::recap() dispatch CompetitorAnalystAgent
 * 3. CompetitorAnalystAgent génère l'analyse détaillée
 * 4. Ce subscriber met à jour l'entité avec les résultats
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
     * Persiste les résultats d'analyse concurrentielle quand TaskCompletedEvent est reçu.
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

        // Récupérer l'entité CompetitorAnalysis existante
        $competitorAnalysis = $project->getCompetitorAnalysis();

        if (null === $competitorAnalysis) {
            $this->logger->error('CompetitorAnalysis entity not found for project', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Mettre à jour l'entité avec les résultats de l'agent
            $this->updateCompetitorAnalysisFromResult($competitorAnalysis, $result);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('CompetitorAnalysis updated successfully', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to update CompetitorAnalysis', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Met à jour l'entité CompetitorAnalysis depuis le résultat de l'agent IA.
     *
     * Structure attendue du résultat CompetitorAnalystAgent :
     * - competitors : Liste des concurrents analysés
     * - strengths : Forces identifiées par concurrent
     * - weaknesses : Faiblesses identifiées par concurrent
     * - market_positioning : Positionnement marché
     * - differentiation_opportunities : Opportunités de différenciation
     * - marketing_strategies : Stratégies marketing des concurrents
     *
     * @param array<string, mixed> $result
     */
    private function updateCompetitorAnalysisFromResult(\App\Entity\CompetitorAnalysis $competitorAnalysis, array $result): void
    {
        // Extraire et normaliser les données
        $strengths = $result['strengths'] ?? $result['competitor_strengths'] ?? [];
        $weaknesses = $result['weaknesses'] ?? $result['competitor_weaknesses'] ?? [];
        $marketPositioning = $result['market_positioning'] ?? $result['positioning'] ?? [];
        $differentiationOpportunities = $result['differentiation_opportunities'] ?? $result['opportunities'] ?? [];
        $marketingStrategies = $result['marketing_strategies'] ?? $result['strategies'] ?? [];

        // Mettre à jour les champs
        $competitorAnalysis->setStrengths($this->normalizeJsonField($strengths));
        $competitorAnalysis->setWeaknesses($this->normalizeJsonField($weaknesses));
        $competitorAnalysis->setMarketPositioning($this->normalizeJsonField($marketPositioning));
        $competitorAnalysis->setDifferentiationOpportunities($this->normalizeJsonField($differentiationOpportunities));
        $competitorAnalysis->setMarketingStrategies($this->normalizeJsonField($marketingStrategies));

        $this->logger->debug('CompetitorAnalysis entity updated from agent result', [
            'has_strengths' => ! empty($strengths),
            'has_weaknesses' => ! empty($weaknesses),
            'has_positioning' => ! empty($marketPositioning),
            'has_opportunities' => ! empty($differentiationOpportunities),
            'has_strategies' => ! empty($marketingStrategies),
        ]);
    }

    /**
     * Normalise un champ JSON : convertit array en JSON string, garde string tel quel.
     */
    private function normalizeJsonField(mixed $value): string
    {
        if (is_array($value)) {
            $json = json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            if (false === $json) {
                throw new \RuntimeException('Failed to encode array to JSON: '.json_last_error_msg());
            }

            return $json;
        }

        if (is_string($value)) {
            return $value;
        }

        // Fallback : convertir en string
        return (string) $value;
    }
}
