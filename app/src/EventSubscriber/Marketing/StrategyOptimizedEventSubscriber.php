<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Entity\Strategy;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour persister la stratégie générée par l'IA en base de données.
 *
 * Écoute l'événement TaskCompletedEvent dispatché par le StrategyAnalystAgent,
 * extrait la stratégie du résultat, la persiste en base de données et met à jour
 * le statut du projet à STRATEGY_GENERATED.
 *
 * Workflow :
 * 1. StrategyController dispatch task via AgentTaskManager avec options['project_id']
 * 2. StrategyAnalystAgent génère la stratégie marketing complète
 * 3. TaskCompletedEvent est dispatché avec result contenant la stratégie
 * 4. Ce subscriber :
 *    - Vérifie que c'est bien une génération de stratégie
 *    - Extrait project_id depuis context
 *    - Mappe les données vers entité Strategy
 *    - Persiste en base de données
 *    - Met à jour le statut du projet
 */
final readonly class StrategyOptimizedEventSubscriber implements EventSubscriberInterface
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
            TaskCompletedEvent::class => 'onTaskCompleted',
        ];
    }

    /**
     * Persiste la stratégie générée quand TaskCompletedEvent est reçu.
     *
     * Filtre sur l'agent StrategyAnalystAgent pour ne traiter que les générations de stratégie.
     */
    public function onTaskCompleted(TaskCompletedEvent $event): void
    {
        // Filtrer : seulement si c'est StrategyAnalystAgent
        if (! str_contains($event->agentName, 'StrategyAnalystAgent')) {
            return;
        }

        $taskId = $event->taskId;
        $result = $event->result;
        $context = $event->context;

        $this->logger->info('StrategyOptimizedEvent received', [
            'task_id' => $taskId,
            'agent_name' => $event->agentName,
            'result_type' => gettype($result),
            'context_keys' => array_keys($context),
        ]);

        // Vérifier qu'on a un résultat valide
        if (! is_array($result) || empty($result)) {
            $this->logger->warning('Task completed but result is empty', [
                'task_id' => $taskId,
            ]);

            return;
        }

        // Extraire project_id depuis context (passé dans options lors du dispatch)
        $projectId = $context['project_id'] ?? null;

        if (! $projectId) {
            $this->logger->error('project_id not found in context', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('Project not found', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Supprimer l'ancienne stratégie si regénération
            $this->deleteExistingStrategy($project);

            // Créer et persister la nouvelle entité Strategy
            $this->createStrategyFromResult($project, $result, $context);

            // Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::STRATEGY_GENERATED);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('Strategy persisted successfully', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to persist strategy', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e; // Re-throw pour que la tâche soit marquée en erreur
        }
    }

    /**
     * Supprime la stratégie existante du projet (si regénération).
     */
    private function deleteExistingStrategy(\App\Entity\Project $project): void
    {
        $existingStrategies = $project->getStrategies();

        if ($existingStrategies->isEmpty()) {
            return;
        }

        $this->logger->info('Deleting existing strategies before regeneration', [
            'project_id' => $project->getId(),
            'existing_count' => $existingStrategies->count(),
        ]);

        foreach ($existingStrategies as $strategy) {
            $this->entityManager->remove($strategy);
        }

        $this->entityManager->flush(); // Flush pour vider avant création
    }

    /**
     * Crée l'entité Strategy depuis le résultat de l'agent IA.
     *
     * Bundle v3.6.0+ utilise StrategyStructuredOutput :
     * [
     *   'strategy' => 'Description narrative de la stratégie' (string),
     *   'tactics' => ['Tactique 1', 'Tactique 2', ...] (array<string>),
     *   'kpis' => ['traffic' => '+50%', 'leads' => '100/mois', ...] (array<string, mixed>),
     *   'risks' => ['Risque 1', 'Risque 2', ...] (array<string>),
     *   'recommendations' => ['Recommandation 1', ...] (array<string>)
     * ]
     *
     * Mapping vers l'entité Strategy (ancienne structure) :
     * - strategy → positioning
     * - tactics → keyMessages (JSON)
     * - recommendations → recommendedChannels (JSON)
     * - risks → timeline (JSON)
     * - kpis → kpis (JSON)
     * - budget_allocation → v3.29.0 depuis contexte BudgetOptimizerTool
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    private function createStrategyFromResult(\App\Entity\Project $project, array $result, array $context = []): void
    {
        // Vérifier les champs obligatoires du nouveau format StrategyStructuredOutput
        $requiredFields = ['strategy', 'tactics', 'kpis', 'risks', 'recommendations'];
        foreach ($requiredFields as $field) {
            if (! isset($result[$field])) {
                $this->logger->warning('Strategy missing required field (StructuredOutput)', [
                    'field' => $field,
                    'available_keys' => array_keys($result),
                ]);

                throw new \LogicException("Strategy data missing required field: {$field}");
            }
        }

        $strategy = new Strategy();
        $strategy->setProject($project);

        // Mapping nouveau format → ancienne entité
        $strategy->setPositioning($result['strategy']); // Description narrative
        $strategy->setKeyMessages($this->normalizeJsonField($result['tactics'])); // Tactiques en JSON
        $strategy->setRecommendedChannels($this->normalizeJsonField($result['recommendations'])); // Recommandations en JSON
        $strategy->setTimeline($this->normalizeJsonField($result['risks'])); // Risques en JSON (temporaire)

        // v3.29.0 : Allocation budgétaire depuis Project (persistée avant dispatch)
        $budgetAllocation = $project->getBudgetAllocation();
        $strategy->setBudgetAllocation($this->normalizeJsonField($budgetAllocation ?: 'Non calculé'));

        $strategy->setKpis($this->normalizeJsonField($result['kpis'])); // KPIs en JSON

        // Quality score optionnel (pas encore dans StrategyStructuredOutput)
        if (isset($result['quality_score'])) {
            $strategy->setQualityScore((string) ($result['quality_score'] / 100)); // 0-100 → 0.0-1.0
        }

        $this->entityManager->persist($strategy);

        $this->logger->debug('Strategy entity created from StructuredOutput', [
            'project_id' => $project->getId(),
            'has_tactics' => ! empty($result['tactics']),
            'has_recommendations' => ! empty($result['recommendations']),
            'kpis_count' => is_array($result['kpis']) ? count($result['kpis']) : 0,
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
