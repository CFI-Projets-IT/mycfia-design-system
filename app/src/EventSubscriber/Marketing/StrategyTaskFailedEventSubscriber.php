<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskFailedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour gérer automatiquement les échecs de génération de stratégie.
 *
 * Responsabilité :
 * Réinitialiser automatiquement le statut du projet à PERSONA_GENERATED lorsque la génération
 * de stratégie échoue, permettant ainsi de relancer une nouvelle génération sans intervention manuelle.
 *
 * Workflow :
 * 1. TaskFailedEvent dispatché par le bundle (AgentTaskHandler)
 * 2. Ce subscriber filtre sur StrategyAnalystAgent
 * 3. Récupère project_id depuis context
 * 4. Remet le statut du projet à PERSONA_GENERATED
 * 5. Log l'action pour traçabilité
 *
 * Sécurité :
 * - S'exécute avec priorité 5 (APRÈS MercurePublisherSubscriber priorité 10)
 * - Filtrage strict sur le nom de l'agent
 * - Gestion des erreurs avec logging
 * - Vérification existence projet avant update
 */
final readonly class StrategyTaskFailedEventSubscriber implements EventSubscriberInterface
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
            // Priorité 5 pour s'exécuter APRÈS MercurePublisherSubscriber (priorité 10)
            // mais AVANT la persistence (priorité 0)
            TaskFailedEvent::class => ['onTaskFailed', 5],
        ];
    }

    /**
     * Réinitialise automatiquement le statut du projet si génération stratégie échoue.
     *
     * Filtre sur StrategyAnalystAgent uniquement pour éviter les effets de bord
     * sur d'autres types de tâches (personas, enrichissement, campagne, etc.).
     */
    public function onTaskFailed(TaskFailedEvent $event): void
    {
        // Filtrer : seulement si c'est StrategyAnalystAgent
        if (! str_contains($event->agentName, 'StrategyAnalystAgent')) {
            return;
        }

        $taskId = $event->taskId;
        $error = $event->error;
        $context = $event->context;

        $this->logger->warning('StrategyAnalystAgent task failed, attempting recovery', [
            'task_id' => $taskId,
            'error' => $error,
            'is_recoverable' => $event->isRecoverable,
            'retry_count' => $event->getRetryCount(),
            'context_keys' => array_keys($context),
        ]);

        // Extraire project_id depuis context (passé dans options lors du dispatch)
        $projectId = $context['project_id'] ?? null;

        if (! $projectId) {
            $this->logger->error('Cannot reset project status: project_id not found in context', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('Cannot reset project status: project not found', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Réinitialiser le statut uniquement si le projet est en STRATEGY_IN_PROGRESS
            // (éviter de modifier un projet déjà en STRATEGY_COMPLETED par exemple)
            if (ProjectStatus::STRATEGY_IN_PROGRESS !== $project->getStatus()) {
                $this->logger->info('Project status not reset: project is not in STRATEGY_IN_PROGRESS state', [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                    'current_status' => $project->getStatus()->value,
                ]);

                return;
            }

            // Réinitialiser le statut à PERSONA_GENERATED pour permettre une nouvelle génération
            $project->setStatus(ProjectStatus::PERSONA_GENERATED);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('Project status automatically reset after strategy generation failure', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'previous_status' => ProjectStatus::STRATEGY_IN_PROGRESS->value,
                'new_status' => ProjectStatus::PERSONA_GENERATED->value,
                'error_message' => $error,
            ]);
        } catch (\Throwable $e) {
            // Log l'erreur mais ne pas re-lever l'exception pour ne pas bloquer
            // le workflow de gestion d'erreur du bundle
            $this->logger->error('Failed to reset project status after strategy generation failure', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);
        }
    }
}
