<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Gorillias\MarketingBundle\Event\TaskFailedEvent;
use Gorillias\MarketingBundle\Event\TaskStartedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Publie les événements de tâches asynchrones sur le hub Mercure pour notifications temps réel.
 *
 * Écoute tous les événements de cycle de vie des tâches IA (TaskStartedEvent, TaskCompletedEvent,
 * TaskFailedEvent) et les publie sur le hub Mercure pour permettre aux clients JavaScript de
 * recevoir des notifications en temps réel via EventSource.
 *
 * Architecture Mercure :
 * 1. Backend dispatche événement Symfony (ex: TaskStartedEvent)
 * 2. Ce subscriber publie sur hub Mercure avec topic `/tasks/{taskId}`
 * 3. Client JavaScript EventSource reçoit notification instantanée
 * 4. Interface actualise (loader, résultats, erreurs)
 *
 * Note: ProjectEnrichedEvent n'est plus écouté (remplacé par TaskCompletedEvent depuis bundle v2.6.0)
 *
 * @see https://mercure.rocks Documentation Mercure
 */
final readonly class MercurePublisherSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private HubInterface $hub,
        private LoggerInterface $logger,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            TaskStartedEvent::class => 'onTaskStarted',
            TaskCompletedEvent::class => 'onTaskCompleted',
            TaskFailedEvent::class => 'onTaskFailed',
        ];
    }

    /**
     * Publie TaskStartedEvent sur Mercure.
     *
     * Notifie le client que la tâche a démarré pour afficher un loader.
     */
    public function onTaskStarted(TaskStartedEvent $event): void
    {
        $taskId = $event->taskId;

        $update = new Update(
            topics: "/tasks/{$taskId}",
            data: json_encode([
                'type' => 'TaskStartedEvent',
                'taskId' => $taskId,
                'agentName' => $event->agentName,
                'methodName' => $event->methodName,
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]),
            private: false, // Public pour simplicité en développement (utiliser JWT en production)
        );

        try {
            $this->hub->publish($update);

            $this->logger->info('TaskStartedEvent published to Mercure', [
                'task_id' => $taskId,
                'agent_name' => $event->agentName,
                'topic' => "/tasks/{$taskId}",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish TaskStartedEvent to Mercure', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publie TaskCompletedEvent sur Mercure.
     *
     * Notifie le client que la tâche est terminée avec succès.
     */
    public function onTaskCompleted(TaskCompletedEvent $event): void
    {
        $taskId = $event->taskId;
        $executionTime = $event->getExecutionTime();

        $update = new Update(
            topics: "/tasks/{$taskId}",
            data: json_encode([
                'type' => 'TaskCompletedEvent',
                'taskId' => $taskId,
                'agentName' => $event->agentName,
                'result' => $event->result,
                'executionTime' => $executionTime,
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]),
            private: false,
        );

        try {
            $this->hub->publish($update);

            $this->logger->info('TaskCompletedEvent published to Mercure', [
                'task_id' => $taskId,
                'agent_name' => $event->agentName,
                'execution_time_s' => $executionTime,
                'topic' => "/tasks/{$taskId}",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish TaskCompletedEvent to Mercure', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
        }
    }

    /**
     * Publie TaskFailedEvent sur Mercure.
     *
     * Notifie le client que la tâche a échoué avec le message d'erreur.
     */
    public function onTaskFailed(TaskFailedEvent $event): void
    {
        $taskId = $event->taskId;

        $update = new Update(
            topics: "/tasks/{$taskId}",
            data: json_encode([
                'type' => 'TaskFailedEvent',
                'taskId' => $taskId,
                'agentName' => $event->agentName,
                'error' => $event->error,
                'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
            ]),
            private: false,
        );

        try {
            $this->hub->publish($update);

            $this->logger->error('TaskFailedEvent published to Mercure', [
                'task_id' => $taskId,
                'agent_name' => $event->agentName,
                'error' => $event->error,
                'topic' => "/tasks/{$taskId}",
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to publish TaskFailedEvent to Mercure', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
