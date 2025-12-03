<?php

declare(strict_types=1);

namespace App\Service;

use Gorillias\MarketingBundle\Contract\NotificationPublisherInterface;
use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Implementation de NotificationPublisherInterface avec Mercure SSE.
 *
 * Cette implementation permet au bundle marketing-ai de publier des
 * notifications temps reel via Mercure sans etre couple a l'infrastructure
 * specifique de l'application cliente.
 *
 * Topics Mercure utilises:
 * - /user/{userId} : Notifications personnelles utilisateur
 * - /task/{taskUuid} : Evenements de cycle de vie d'une tache
 *
 * Configuration requise dans services.yaml:
 * ```yaml
 * Gorillias\MarketingBundle\Contract\NotificationPublisherInterface:
 *     class: App\Service\MercureNotificationPublisher
 * ```
 *
 * @see https://mercure.rocks Documentation Mercure
 */
final readonly class MercureNotificationPublisher implements NotificationPublisherInterface
{
    public function __construct(
        private HubInterface $hub,
        #[Autowire(service: 'monolog.logger.marketing.general')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Publie une notification pour un utilisateur specifique.
     *
     * Topic: /user/{userId}
     *
     * @param mixed               $userId Identifiant utilisateur (int, string, UUID)
     * @param string              $type   Type de notification (task_completed, task_failed, etc.)
     * @param array<string,mixed> $data   Donnees de la notification
     *
     * @throws \RuntimeException Si la publication echoue
     */
    public function publish($userId, string $type, array $data): void
    {
        $topic = sprintf('/user/%s', (string) $userId);

        $payload = [
            'type' => $type,
            'userId' => $userId,
            'data' => $data,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        try {
            $update = new Update(
                topics: $topic,
                data: json_encode($payload, JSON_THROW_ON_ERROR),
                private: true, // Notification privee pour l'utilisateur
                type: $type,   // Type SSE pour filtrage cote client
            );

            $this->hub->publish($update);

            $this->logger->info('MercureNotificationPublisher: User notification published', [
                'user_id' => $userId,
                'type' => $type,
                'topic' => $topic,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('MercureNotificationPublisher: Failed to publish user notification', [
                'user_id' => $userId,
                'type' => $type,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(sprintf('Failed to publish notification for user %s: %s', $userId, $e->getMessage()), 0, $e);
        }
    }

    /**
     * Publie une notification pour une tache specifique.
     *
     * Topic: /task/{taskUuid}
     *
     * @param string              $taskUuid UUID de la tache (format RFC 4122)
     * @param string              $event    Type d'evenement (task.started, task.completed, task.failed)
     * @param array<string,mixed> $data     Donnees de l'evenement
     *
     * @throws \InvalidArgumentException Si le taskUuid n'est pas un UUID valide
     * @throws \RuntimeException         Si la publication echoue
     */
    public function publishTaskEvent(string $taskUuid, string $event, array $data): void
    {
        // Validation basique de l'UUID
        if (! preg_match('/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i', $taskUuid)) {
            throw new \InvalidArgumentException(sprintf('Invalid UUID format: %s', $taskUuid));
        }

        $topic = sprintf('/task/%s', $taskUuid);

        $payload = [
            'event' => $event,
            'taskUuid' => $taskUuid,
            'data' => $data,
            'timestamp' => (new \DateTimeImmutable())->format(\DateTimeInterface::ATOM),
        ];

        try {
            $update = new Update(
                topics: $topic,
                data: json_encode($payload, JSON_THROW_ON_ERROR),
                private: false, // Public pour permettre monitoring/dashboards
                type: $event,   // Type SSE pour filtrage cote client
            );

            $this->hub->publish($update);

            $this->logger->info('MercureNotificationPublisher: Task event published', [
                'task_uuid' => $taskUuid,
                'event' => $event,
                'topic' => $topic,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('MercureNotificationPublisher: Failed to publish task event', [
                'task_uuid' => $taskUuid,
                'event' => $event,
                'error' => $e->getMessage(),
            ]);

            throw new \RuntimeException(sprintf('Failed to publish task event %s for task %s: %s', $event, $taskUuid, $e->getMessage()), 0, $e);
        }
    }
}
