<?php

declare(strict_types=1);

namespace App\EventListener\Marketing;

use App\Entity\Project;
use App\Entity\ProjectEnrichmentDraft;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Écoute l'événement TaskCompletedEvent pour créer un ProjectEnrichmentDraft.
 *
 * Nouveau workflow (v3.21.0+) :
 * 1. Le projet est créé en base AVANT l'enrichissement (status DRAFT)
 * 2. AgentTaskManager dispatche ProjectEnrichmentAgent
 * 3. Worker exécute l'enrichissement (5-15s)
 * 4. TaskCompletedEvent dispatché avec taskId + résultats
 * 5. Ce listener crée un ProjectEnrichmentDraft avec les données enrichies
 * 6. Le projet passe au statut ENRICHED_PENDING
 * 7. L'utilisateur voit la page de révision avec 5 onglets
 * 8. L'utilisateur valide ou régénère l'enrichissement
 */
#[AsEventListener(event: TaskCompletedEvent::class)]
final class ProjectEnrichedEventListener
{
    public function __construct(
        private readonly CacheInterface $cache,
        private readonly LoggerInterface $logger,
        /** @phpstan-ignore-next-line */
        private readonly EntityManagerInterface $entityManager,
        /** @phpstan-ignore-next-line */
        private readonly ProjectRepository $projectRepository,
    ) {
    }

    public function __invoke(TaskCompletedEvent $event): void
    {
        // Ne traiter que les enrichissements de projet
        if (! str_contains($event->agentName, 'ProjectEnrichmentAgent')) {
            return;
        }

        $taskId = $event->taskId;
        $enrichedData = $event->result;

        // NOUVEAU WORKFLOW : Récupérer le projet depuis le cache et créer/mettre à jour un ProjectEnrichmentDraft
        try {
            $projectId = $this->cache->get('project_id_for_task_'.$taskId, function () {
                throw new \RuntimeException('Project ID not found in cache for this taskId');
            });

            /** @var Project|null $project */
            $project = $this->projectRepository->find($projectId);

            if (! $project) {
                $this->logger->error('ProjectEnrichedEventListener: Projet non trouvé', [
                    'task_id' => $taskId,
                    'project_id' => $projectId,
                ]);

                return;
            }

            // Récupérer le draft existant ou en créer un nouveau (gestion régénération)
            $draftRepository = $this->entityManager->getRepository(ProjectEnrichmentDraft::class);
            $draft = $draftRepository->findOneBy(['project' => $project]);

            if (null === $draft) {
                // Créer un nouveau draft si premier enrichissement
                $draft = new ProjectEnrichmentDraft();
                $draft->setProject($project);
                $this->entityManager->persist($draft);
                $this->logger->info('ProjectEnrichedEventListener: Nouveau draft créé', [
                    'task_id' => $taskId,
                    'project_id' => $project->getId(),
                ]);
            } else {
                // Mettre à jour le draft existant (cas de régénération)
                $this->logger->info('ProjectEnrichedEventListener: Draft existant mis à jour', [
                    'task_id' => $taskId,
                    'project_id' => $project->getId(),
                    'draft_id' => $draft->getId(),
                ]);
            }

            // Mettre à jour les données du draft
            $draft->setTaskId($taskId);
            $draft->setEnrichmentData($enrichedData);
            $draft->setStatus('pending');
            $draft->setEnrichedAt(new \DateTimeImmutable());

            // Changer le statut du projet vers ENRICHED_PENDING
            $project->setStatus(ProjectStatus::ENRICHED_PENDING);

            $this->entityManager->flush();

            $this->logger->info('ProjectEnrichedEventListener: Draft et statut mis à jour', [
                'task_id' => $taskId,
                'project_id' => $project->getId(),
                'draft_id' => $draft->getId(),
                'project_status' => $project->getStatus()->value,
            ]);
        } catch (\Exception $e) {
            $this->logger->error('ProjectEnrichedEventListener: Erreur lors de la création du draft', [
                'task_id' => $taskId,
                'error' => $e->getMessage(),
            ]);
            // Continuer pour maintenir la compatibilité avec l'ancien workflow (cache)
        }

        // Stocker les résultats en cache avec clé basée sur taskId (TTL 1 heure)
        $cacheKey = 'enrichment_results_'.$taskId;

        // DEBUG : Logger la structure complète des données reçues
        $this->logger->debug('ProjectEnrichedEventListener: Structure données reçues', [
            'enrichedData_keys' => array_keys($enrichedData),
            'has_scraped_content' => isset($enrichedData['scraped_content']),
            'has_dev' => isset($enrichedData['dev']),
            'has_dev_brand_identity' => isset($enrichedData['dev']['brand_identity']),
            'ai_suggestions_keys' => array_keys($enrichedData['ai_suggestions'] ?? []),
        ]);

        // DEBUG : Logger scraped_content si présent
        if (isset($enrichedData['scraped_content'])) {
            $this->logger->debug('ProjectEnrichedEventListener: scraped_content trouvé', [
                'scraped_content_keys' => array_keys($enrichedData['scraped_content']),
                'has_metadata' => isset($enrichedData['scraped_content']['metadata']),
                'has_markdown' => isset($enrichedData['scraped_content']['markdown']),
                'has_project_context' => isset($enrichedData['scraped_content']['project_context']),
            ]);
        } else {
            $this->logger->warning('ProjectEnrichedEventListener: scraped_content MANQUANT', [
                'task_id' => $taskId,
            ]);
        }

        // Gestion des cas d'erreur : si ai_suggestions contient 'error', l'enrichissement a échoué
        $hasError = ! empty($enrichedData['ai_suggestions']['error']);

        if ($hasError) {
            $this->logger->warning('ProjectEnrichedEventListener: Enrichissement échoué, données incomplètes', [
                'task_id' => $taskId,
                'error' => $enrichedData['ai_suggestions']['error'],
            ]);

            // Ne pas stocker en cache si erreur, retourner immédiatement
            return;
        }

        // WORKAROUND : Le ProjectEnrichmentAgent retourne les données dans $baseAnalysis (suggested_name, etc.)
        // au lieu de ai_suggestions car le parsing JSON de Mistral échoue.
        // On mappe donc les données existantes vers le format attendu par la modal.
        $resultsData = [
            'task_id' => $taskId,
            // Si ai_suggestions est vide, utiliser les données de baseAnalysis comme alternatives
            'alternative_names' => ! empty($enrichedData['ai_suggestions']['creative_name_alternatives'])
                ? $enrichedData['ai_suggestions']['creative_name_alternatives']
                : (isset($enrichedData['suggested_name']) && $enrichedData['suggested_name'] ? [$enrichedData['suggested_name']] : []),
            'enhanced_objectives' => ! empty($enrichedData['ai_suggestions']['smart_objectives_detailed'])
                ? $enrichedData['ai_suggestions']['smart_objectives_detailed']
                : ($enrichedData['enhanced_objectives'] ?? ''),
            'strategic_recommendations' => ! empty($enrichedData['ai_suggestions']['strategic_recommendations'])
                ? $enrichedData['ai_suggestions']['strategic_recommendations']
                : ($enrichedData['recommendations'] ?? []),
            'success_factors' => ! empty($enrichedData['ai_suggestions']['success_factors'])
                ? $enrichedData['ai_suggestions']['success_factors']
                : ($enrichedData['warnings'] ?? []), // Utiliser warnings comme facteurs de succès temporairement
            'warnings' => $enrichedData['warnings'] ?? [],
            'budget_analysis' => $enrichedData['budget_analysis'] ?? null,
            'timeline_analysis' => $enrichedData['timeline_analysis'] ?? null,
            // Métriques au niveau racine pour affichage
            'budget_per_day' => $enrichedData['budget_per_day'] ?? null,
            'budget_per_month' => $enrichedData['budget_per_month'] ?? null,
            'campaign_weeks' => $enrichedData['campaign_weeks'] ?? null,
            'campaign_months' => $enrichedData['campaign_months'] ?? null,
            'tokens_used' => $enrichedData['tokens_used'] ?? null,
            'duration_ms' => $enrichedData['duration_ms'] ?? null,
            'model_used' => $enrichedData['model_used'] ?? null,
        ];

        $this->cache->get($cacheKey, function (ItemInterface $item) use ($resultsData) {
            $item->expiresAfter(3600); // TTL 1 heure

            return $resultsData;
        });

        $this->logger->info('Project enrichment results stored in cache', [
            'task_id' => $taskId,
            'cache_key' => $cacheKey,
            'alternative_names_count' => count($resultsData['alternative_names']),
            'recommendations_count' => count($resultsData['strategic_recommendations']),
            'success_factors_count' => count($resultsData['success_factors']),
        ]);

        // WORKAROUND : Stocker les metadata dans les résultats en cache au lieu de la BDD
        // car le projet n'existe pas encore en base (il sera créé après acceptation de la modal)
        // Les metadata seront récupérées et sauvegardées dans acceptEnrichment()

        // 1. Brand Metadata (dev.brand_identity) - Bundle v3.9.0
        if (isset($enrichedData['dev']['brand_identity'])) {
            $resultsData['brand_metadata'] = $enrichedData['dev']['brand_identity'];

            $this->logger->info('ProjectEnrichedEventListener: Brand metadata ajoutées au cache', [
                'task_id' => $taskId,
                'brand_name' => $enrichedData['dev']['brand_identity']['brand_name'] ?? 'N/A',
                'quality_score' => $enrichedData['dev']['brand_identity']['analysis_quality_score'] ?? 0,
            ]);
        }

        // 2. Scraped Content (scraped_content) - Bundle v3.10.0+
        if (isset($enrichedData['scraped_content'])) {
            $resultsData['scraped_content'] = $enrichedData['scraped_content'];

            $this->logger->info('ProjectEnrichedEventListener: Scraped content ajouté au cache', [
                'task_id' => $taskId,
                'has_metadata' => isset($enrichedData['scraped_content']['metadata']),
                'has_markdown' => isset($enrichedData['scraped_content']['markdown']),
                'language' => $enrichedData['scraped_content']['metadata']['language'] ?? 'N/A',
            ]);
        }

        // 3. Project Context (scraped_content.project_context) - Bundle v3.11.0+
        if (isset($enrichedData['scraped_content']['project_context'])) {
            $resultsData['project_context'] = $enrichedData['scraped_content']['project_context'];

            $this->logger->info('ProjectEnrichedEventListener: Project context ajouté au cache', [
                'task_id' => $taskId,
                'target_audience' => $enrichedData['scraped_content']['project_context']['targetAudience'] ?? 'N/A',
                'business_model' => $enrichedData['scraped_content']['project_context']['businessModel'] ?? 'N/A',
                'geography' => $enrichedData['scraped_content']['project_context']['geography'] ?? 'N/A',
                'confidence_level' => $enrichedData['scraped_content']['project_context']['confidenceLevel'] ?? 'N/A',
            ]);
        }

        // Mettre à jour le cache avec toutes les metadata collectées
        if (isset($resultsData['brand_metadata']) || isset($resultsData['scraped_content']) || isset($resultsData['project_context'])) {
            $this->cache->delete($cacheKey);
            $this->cache->get($cacheKey, function (ItemInterface $item) use ($resultsData) {
                $item->expiresAfter(3600); // TTL 1 heure

                return $resultsData;
            });
        }
    }
}
