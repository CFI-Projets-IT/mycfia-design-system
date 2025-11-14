<?php

declare(strict_types=1);

namespace App\EventListener\Marketing;

use Gorillias\MarketingBundle\Event\TaskCompletedEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;

/**
 * Écoute l'événement TaskCompletedEvent pour stocker les résultats d'enrichissement en cache.
 *
 * Après enrichissement IA d'un projet via ProjectEnrichmentAgent (Mistral Large Latest),
 * les résultats sont stockés en cache (TTL 1h) pour récupération via AJAX et affichage dans la modal.
 *
 * Workflow :
 * 1. AgentTaskManager dispatche ProjectEnrichmentAgent
 * 2. Worker exécute l'enrichissement (5-15s)
 * 3. TaskCompletedEvent dispatché avec taskId + résultats
 * 4. Ce listener stocke en cache : 'enrichment_results_{taskId}' (TTL 3600s)
 * 5. JavaScript Mercure reçoit notification → AJAX GET /enrichment/{taskId}/results
 * 6. Modal affiche les 3 noms + recommendations + facteurs clés de succès
 */
#[AsEventListener(event: TaskCompletedEvent::class)]
final readonly class ProjectEnrichedEventListener
{
    public function __construct(
        private CacheInterface $cache,
        private LoggerInterface $logger,
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
