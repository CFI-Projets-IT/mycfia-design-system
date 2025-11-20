<?php

namespace App\Service;

use App\Entity\Task;
use App\Repository\TaskRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\DTO\TaskData;
use Gorillias\MarketingBundle\DTO\TaskResult;
use Gorillias\MarketingBundle\Storage\TaskStorageInterface;
use Psr\Log\LoggerInterface;

/**
 * Implémentation de la persistence des tâches Marketing AI Bundle.
 *
 * Stocke l'historique des tâches asynchrones avec métriques LLM complètes
 * pour analytics, debugging et audit.
 */
final readonly class AppTaskStorage implements TaskStorageInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private TaskRepository $taskRepository,
        private LoggerInterface $logger,
    ) {
    }

    public function storeTaskStarted(TaskData $data): void
    {
        try {
            $task = new Task();
            $task->setUuid($data->uuid);
            $task->setName($data->name);
            $task->setType($data->type);
            $task->setStatus('processing');
            $task->setAgentClass($data->agentClass);
            $task->setMethodName($data->methodName);
            /** @var array<string, mixed> $arguments */
            $arguments = $data->arguments;
            $task->setArguments($arguments);
            /** @var array<string, mixed> $context */
            $context = $data->context;
            $task->setContext($context);
            $task->setStartedAt($data->startedAt);

            $this->entityManager->persist($task);
            $this->entityManager->flush();

            $this->logger->info('Task started persisted', [
                'task_uuid' => $data->uuid,
                'agent' => $data->getAgentShortName(),
                'type' => $data->type,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to persist task started', [
                'task_uuid' => $data->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function storeTaskCompleted(TaskResult $result): void
    {
        try {
            $task = $this->taskRepository->findByUuid($result->uuid);

            if (null === $task) {
                $this->logger->warning('Task not found for completion', [
                    'task_uuid' => $result->uuid,
                ]);

                return;
            }

            $task->setStatus($result->status);
            $task->setResult($result->result);
            $task->setTokensInput($result->tokensInput);
            $task->setTokensOutput($result->tokensOutput);
            $task->setTokensTotal($result->tokensTotal);
            $task->setCost($result->cost);
            $task->setDurationMs($result->durationMs);
            $task->setModelUsed($result->modelUsed);
            $task->setCompletedAt($result->completedAt);

            $this->entityManager->flush();

            $this->logger->info('Task completed persisted', [
                'task_uuid' => $result->uuid,
                'duration_s' => $result->getDurationSeconds(),
                'tokens_total' => $result->tokensTotal,
                'cost' => $result->getFormattedCost(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to persist task completed', [
                'task_uuid' => $result->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }

    public function storeTaskFailed(TaskResult $result): void
    {
        try {
            $task = $this->taskRepository->findByUuid($result->uuid);

            if (null === $task) {
                $this->logger->warning('Task not found for failure', [
                    'task_uuid' => $result->uuid,
                ]);

                return;
            }

            $task->setStatus($result->status);
            $task->setErrorMessage($result->errorMessage);
            $task->setErrorTrace($result->errorTrace);
            $task->setTokensInput($result->tokensInput);
            $task->setTokensOutput($result->tokensOutput);
            $task->setTokensTotal($result->tokensTotal);
            $task->setCost($result->cost);
            $task->setDurationMs($result->durationMs);
            $task->setModelUsed($result->modelUsed);
            $task->setCompletedAt($result->completedAt);

            $this->entityManager->flush();

            $this->logger->error('Task failed persisted', [
                'task_uuid' => $result->uuid,
                'error_message' => $result->errorMessage,
                'duration_s' => $result->getDurationSeconds(),
            ]);
        } catch (\Exception $e) {
            $this->logger->error('Failed to persist task failure', [
                'task_uuid' => $result->uuid,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
