<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Mercure\HubInterface;
use Symfony\Component\Mercure\Update;

/**
 * Service pour publication d'événements de génération marketing via Mercure SSE.
 *
 * Responsabilités :
 * - Publier des événements de progression (start, progress, complete, error)
 * - Notifier l'utilisateur en temps réel de l'avancement des générations IA
 * - Gérer les erreurs de publication Mercure
 * - Logger les événements pour debug et monitoring
 *
 * Format des messages :
 * - type: "start" | "progress" | "complete" | "error"
 * - projectId: ID du projet marketing
 * - stage: "personas" | "strategy" | "assets"
 * - message: Message descriptif pour l'utilisateur
 * - data: Données supplémentaires (optionnel)
 *
 * Topics Mercure :
 * - marketing/project/{projectId} : Canal dédié par projet
 */
final readonly class MarketingGenerationPublisher
{
    public function __construct(
        private HubInterface $hub,
        #[Autowire(service: 'monolog.logger.marketing.general')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Publier le début d'une génération marketing.
     *
     * @param int    $projectId ID du projet marketing
     * @param string $stage     Étape de génération (personas|strategy|assets)
     * @param string $message   Message descriptif pour l'utilisateur
     */
    public function publishStart(int $projectId, string $stage, string $message): void
    {
        $this->publish($projectId, [
            'type' => 'start',
            'projectId' => $projectId,
            'stage' => $stage,
            'message' => $message,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('MarketingGenerationPublisher: Start event published', [
            'project_id' => $projectId,
            'stage' => $stage,
            'message' => $message,
        ]);
    }

    /**
     * Publier une progression de la génération.
     *
     * @param int                 $projectId ID du projet marketing
     * @param string              $stage     Étape de génération (personas|strategy|assets)
     * @param string              $message   Message de progression
     * @param array<string,mixed> $data      Données supplémentaires (optionnel)
     */
    public function publishProgress(int $projectId, string $stage, string $message, array $data = []): void
    {
        $this->publish($projectId, [
            'type' => 'progress',
            'projectId' => $projectId,
            'stage' => $stage,
            'message' => $message,
            'data' => $data,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('MarketingGenerationPublisher: Progress event published', [
            'project_id' => $projectId,
            'stage' => $stage,
            'message' => $message,
        ]);
    }

    /**
     * Publier la fin de la génération avec métadonnées.
     *
     * @param int                 $projectId ID du projet marketing
     * @param string              $stage     Étape de génération (personas|strategy|assets)
     * @param string              $message   Message de succès
     * @param array<string,mixed> $metadata  Métadonnées finales (duration, items_count, etc.)
     */
    public function publishComplete(int $projectId, string $stage, string $message, array $metadata = []): void
    {
        $this->publish($projectId, [
            'type' => 'complete',
            'projectId' => $projectId,
            'stage' => $stage,
            'message' => $message,
            'metadata' => $metadata,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->info('MarketingGenerationPublisher: Complete event published', [
            'project_id' => $projectId,
            'stage' => $stage,
            'message' => $message,
            'metadata' => $metadata,
        ]);
    }

    /**
     * Publier une erreur lors de la génération.
     *
     * @param int    $projectId      ID du projet marketing
     * @param string $stage          Étape de génération (personas|strategy|assets)
     * @param string $errorMessage   Message d'erreur descriptif
     * @param string $technicalError Détails techniques (optionnel)
     */
    public function publishError(int $projectId, string $stage, string $errorMessage, string $technicalError = ''): void
    {
        $this->publish($projectId, [
            'type' => 'error',
            'projectId' => $projectId,
            'stage' => $stage,
            'message' => $errorMessage,
            'technical' => $technicalError,
            'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
        ]);

        $this->logger->error('MarketingGenerationPublisher: Error event published', [
            'project_id' => $projectId,
            'stage' => $stage,
            'error_message' => $errorMessage,
            'technical_error' => $technicalError,
        ]);
    }

    /**
     * Publier un message JSON sur le topic Mercure du projet.
     *
     * @param int                 $projectId ID du projet marketing
     * @param array<string,mixed> $data      Données à publier en JSON
     */
    private function publish(int $projectId, array $data): void
    {
        try {
            $topic = sprintf('marketing/project/%d', $projectId);

            $update = new Update(
                topics: [$topic],
                data: json_encode($data, JSON_THROW_ON_ERROR),
            );

            $this->hub->publish($update);

            $this->logger->debug('MarketingGenerationPublisher: Message published', [
                'topic' => $topic,
                'data' => $data,
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('MarketingGenerationPublisher: Failed to publish message', [
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Ne pas propager l'erreur pour éviter de bloquer la génération
            // La notification est un bonus, pas une dépendance critique
        }
    }
}
