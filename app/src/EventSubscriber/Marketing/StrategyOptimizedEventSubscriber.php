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
            $this->createStrategyFromResult($project, $result);

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
     * Le résultat peut être un objet stratégie ou un tableau avec clé 'strategy'.
     *
     * Structure attendue d'une stratégie :
     * [
     *   'positioning' => 'Positionnement stratégique...' (string),
     *   'key_messages' => 'Messages clés...' (string ou array),
     *   'recommended_channels' => 'Canaux recommandés...' (string ou array),
     *   'timeline' => 'Planning de diffusion...' (string ou array),
     *   'budget_allocation' => 'Répartition budgétaire...' (string ou array),
     *   'kpis' => 'KPIs suggérés...' (string ou array),
     *   'quality_score' => 0.92 (float)
     * ]
     *
     * @param array<string, mixed> $result
     */
    private function createStrategyFromResult(\App\Entity\Project $project, array $result): void
    {
        // Le résultat peut avoir une clé 'strategy' ou être directement la stratégie
        $strategyData = $result['strategy'] ?? $result;

        // Vérifier les champs obligatoires
        $requiredFields = ['positioning', 'key_messages', 'recommended_channels', 'timeline', 'budget_allocation', 'kpis'];
        foreach ($requiredFields as $field) {
            if (! isset($strategyData[$field])) {
                $this->logger->warning('Strategy missing required field', [
                    'field' => $field,
                    'available_keys' => array_keys($strategyData),
                ]);

                throw new \LogicException("Strategy data missing required field: {$field}");
            }
        }

        $strategy = new Strategy();
        $strategy->setProject($project);
        $strategy->setPositioning($this->normalizeJsonField($strategyData['positioning']));
        $strategy->setKeyMessages($this->normalizeJsonField($strategyData['key_messages']));
        $strategy->setRecommendedChannels($this->normalizeJsonField($strategyData['recommended_channels']));
        $strategy->setTimeline($this->normalizeJsonField($strategyData['timeline']));
        $strategy->setBudgetAllocation($this->normalizeJsonField($strategyData['budget_allocation']));
        $strategy->setKpis($this->normalizeJsonField($strategyData['kpis']));

        // Quality score optionnel
        if (isset($strategyData['quality_score'])) {
            $strategy->setQualityScore((string) $strategyData['quality_score']);
        }

        $this->entityManager->persist($strategy);

        $this->logger->debug('Strategy entity created', [
            'project_id' => $project->getId(),
            'quality_score' => $strategy->getQualityScore(),
        ]);
    }

    /**
     * Normalise un champ JSON : convertit array en JSON string, garde string tel quel.
     */
    private function normalizeJsonField(mixed $value): string
    {
        if (is_array($value)) {
            return json_encode($value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }

        if (is_string($value)) {
            return $value;
        }

        // Fallback : convertir en string
        return (string) $value;
    }
}
