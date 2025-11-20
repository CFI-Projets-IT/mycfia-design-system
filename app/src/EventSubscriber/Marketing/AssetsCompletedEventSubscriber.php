<?php

declare(strict_types=1);

namespace App\EventSubscriber\Marketing;

use App\Entity\Asset;
use App\Enum\AssetStatus;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

/**
 * Event Subscriber pour persister les assets générés par ContentCreatorAgent.
 *
 * Écoute l'événement TaskCompletedEvent dispatché par ContentCreatorAgent,
 * extrait le contenu généré et crée l'entité Asset correspondante.
 *
 * Workflow :
 * 1. AssetController::new() dispatch via dispatchContentCreation()
 * 2. ContentCreatorAgent génère le contenu marketing
 * 3. TaskCompletedEvent est dispatché avec result contenant l'asset
 * 4. Ce subscriber :
 *    - Vérifie que c'est bien une génération d'asset
 *    - Extrait project_id depuis context (brief)
 *    - Mappe les données vers entité Asset
 *    - Persiste en base de données
 *    - Met à jour le statut du projet si tous les assets sont générés
 */
final readonly class AssetsCompletedEventSubscriber implements EventSubscriberInterface
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
     * Persiste l'asset généré quand TaskCompletedEvent est reçu.
     *
     * Filtre sur l'agent ContentCreatorAgent pour ne traiter que les générations d'assets.
     */
    public function onTaskCompleted(TaskCompletedEvent $event): void
    {
        // Filtrer : seulement si c'est ContentCreatorAgent
        if (! str_contains($event->agentName, 'ContentCreatorAgent')) {
            return;
        }

        $taskId = $event->taskId;
        $result = $event->result;
        $context = $event->context;

        $this->logger->info('AssetsCompletedEventSubscriber received', [
            'task_id' => $taskId,
            'agent_name' => $event->agentName,
            'result_type' => gettype($result),
            'context_keys' => array_keys($context),
        ]);

        // Vérifier qu'on a un résultat valide
        if (! is_array($result) || empty($result)) {
            $this->logger->warning('Asset generation completed but result is empty', [
                'task_id' => $taskId,
            ]);

            return;
        }

        // Extraire project_id depuis context (passé dans brief ou options)
        $projectId = $context['project_id'] ?? $context['brief']['project_id'] ?? null;

        if (! $projectId) {
            $this->logger->error('project_id not found in context for asset generation', [
                'task_id' => $taskId,
                'context' => $context,
            ]);

            return;
        }

        // Récupérer le projet depuis la base de données
        $project = $this->projectRepository->find($projectId);

        if (! $project) {
            $this->logger->error('Project not found for asset generation', [
                'task_id' => $taskId,
                'project_id' => $projectId,
            ]);

            return;
        }

        try {
            // Créer et persister l'entité Asset
            $this->createAssetFromResult($project, $result, $context);

            // Flush en base de données
            $this->entityManager->flush();

            $this->logger->info('Asset persisted successfully', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'asset_type' => $result['asset_type'] ?? $context['asset_type'] ?? 'unknown',
            ]);

            // Vérifier si tous les assets attendus sont générés
            $this->checkProjectAssetsCompletion($project);
        } catch (\Throwable $e) {
            $this->logger->error('Failed to persist asset', [
                'task_id' => $taskId,
                'project_id' => $projectId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Crée l'entité Asset depuis le résultat de l'agent IA.
     *
     * Structure attendue du résultat ContentCreatorAgent :
     * - asset_type : Type d'asset (google_ads, linkedin_post, etc.)
     * - channel : Canal de diffusion (social, search, display, email)
     * - content : Contenu principal (structure JSON spécifique au type)
     * - variations : Variations générées (array)
     * - quality_score : Score de qualité (0-100)
     *
     * @param array<string, mixed> $result
     * @param array<string, mixed> $context
     */
    private function createAssetFromResult(\App\Entity\Project $project, array $result, array $context = []): void
    {
        $asset = new Asset();
        $asset->setProject($project);

        // Type d'asset (depuis result ou context)
        $assetType = $result['asset_type'] ?? $context['asset_type'] ?? 'unknown';
        $asset->setAssetType($assetType);

        // Canal de diffusion (déduit du type si non fourni)
        $channel = $result['channel'] ?? $this->getChannelForAssetType($assetType);
        $asset->setChannel($channel);

        // Contenu principal (JSON)
        $content = $result['content'] ?? $result;
        $asset->setContent($this->normalizeJsonField($content));

        // Variations (JSON array)
        if (isset($result['variations']) && is_array($result['variations'])) {
            $asset->setVariations($this->normalizeJsonField($result['variations']));
        }

        // Score de qualité (0-100 → 0.0-1.0)
        if (isset($result['quality_score'])) {
            $score = is_numeric($result['quality_score']) ? (float) $result['quality_score'] : 0;
            $asset->setQualityScore((string) ($score / 100));
        }

        // Statut par défaut : DRAFT
        $asset->setStatus(AssetStatus::DRAFT);

        $this->entityManager->persist($asset);

        $this->logger->debug('Asset entity created', [
            'project_id' => $project->getId(),
            'asset_type' => $assetType,
            'channel' => $channel,
            'has_variations' => isset($result['variations']),
        ]);
    }

    /**
     * Met à jour le statut du projet après génération d'un asset.
     *
     * Passe de ASSETS_IN_PROGRESS à ASSETS_GENERATED dès qu'un asset est généré.
     * L'utilisateur peut générer des assets supplémentaires à tout moment.
     */
    private function checkProjectAssetsCompletion(\App\Entity\Project $project): void
    {
        // Mettre à jour le statut dès qu'un asset est généré
        if (ProjectStatus::ASSETS_IN_PROGRESS === $project->getStatus()) {
            $project->setStatus(ProjectStatus::ASSETS_GENERATED);
            $this->entityManager->flush();

            $this->logger->info('Asset generated, project status updated to ASSETS_GENERATED', [
                'project_id' => $project->getId(),
                'total_assets' => $project->getAssets()->count(),
            ]);
        }
    }

    /**
     * Déduit le canal de diffusion depuis le type d'asset.
     */
    private function getChannelForAssetType(string $assetType): string
    {
        return match ($assetType) {
            'google_ads', 'bing_ads' => 'search',
            'linkedin_post', 'facebook_post', 'instagram_post' => 'social',
            'iab_banner' => 'display',
            'mail' => 'email',
            'article_seo' => 'content',
            default => 'other',
        };
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
