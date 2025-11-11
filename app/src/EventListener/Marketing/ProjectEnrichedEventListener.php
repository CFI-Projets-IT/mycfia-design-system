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
            'ai_suggestions_keys' => array_keys($enrichedData['ai_suggestions'] ?? []),
            'ai_suggestions_dump' => json_encode($enrichedData['ai_suggestions'] ?? [], JSON_PRETTY_PRINT),
        ]);

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
            'consistency_score' => $enrichedData['consistency_score'] ?? 0.0,
            'warnings' => $enrichedData['warnings'] ?? [],
            'budget_analysis' => $enrichedData['budget_analysis'] ?? null,
            'timeline_analysis' => $enrichedData['timeline_analysis'] ?? null,
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
            'consistency_score' => $resultsData['consistency_score'],
        ]);
    }
}
