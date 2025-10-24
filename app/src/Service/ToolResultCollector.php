<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service collecteur de résultats de tools pour transmettre les métadonnées au frontend.
 *
 * Responsabilités :
 * - Collecter les résultats complets des tools (incluant suggested_actions, etc.)
 * - Fournir un moyen d'agréger les métadonnées pour le streaming
 * - Réinitialisation automatique entre les requêtes
 *
 * Architecture :
 * - Service stateful (stocke temporairement les tool results)
 * - Thread-safe pour un contexte de requête unique
 * - Complément de ToolCallCollector (qui ne collecte que les noms)
 *
 * Usage :
 * 1. reset() au début de chaque question (ChatStreamMessageHandler)
 * 2. addToolResult() dans chaque Tool::__invoke() pour collecter résultat complet
 * 3. getAggregatedMetadata() pour récupérer toutes les métadonnées à transmettre au frontend
 */
final class ToolResultCollector
{
    /**
     * @var array<int, array<string, mixed>>
     */
    private array $toolResults = [];

    private \Psr\Log\LoggerInterface $logger;

    public function __construct(\Psr\Log\LoggerInterface $logger)
    {
        $this->logger = $logger;
    }

    /**
     * Enregistrer le résultat complet d'un tool.
     *
     * @param string               $toolName Nom du tool (ex: get_factures)
     * @param array<string, mixed> $result   Résultat complet du tool
     */
    public function addToolResult(string $toolName, array $result): void
    {
        // DEBUG DIRECT FILE : Ce qui est ajouté au collecteur
        $debugFile = '/tmp/collector_debug.log';
        $timestamp = date('Y-m-d H:i:s');

        $debugData = [
            'timestamp' => $timestamp,
            'action' => 'addToolResult CALLED',
            'tool' => $toolName,
            'has_table_data' => isset($result['table_data']),
            'has_suggested_actions' => isset($result['suggested_actions']),
            'result_keys' => array_keys($result),
        ];

        if (isset($result['table_data'])) {
            $debugData['table_data_keys'] = array_keys($result['table_data']);
            $debugData['table_data_headers_count'] = count($result['table_data']['headers'] ?? []);
            $debugData['table_data_rows_count'] = count($result['table_data']['rows'] ?? []);
        }

        file_put_contents($debugFile, json_encode($debugData, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

        // Logger aussi
        $this->logger->info('[COLLECTOR] addToolResult CALLED', [
            'tool' => $toolName,
            'has_table_data' => isset($result['table_data']),
            'has_suggested_actions' => isset($result['suggested_actions']),
            'result_keys' => array_keys($result),
        ]);

        $this->toolResults[] = [
            'tool_name' => $toolName,
            'result' => $result,
        ];

        $debugData2 = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'addToolResult DONE',
            'total_tool_results' => count($this->toolResults),
        ];
        file_put_contents($debugFile, json_encode($debugData2, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

        $this->logger->info('[COLLECTOR] addToolResult DONE', [
            'total_tool_results' => count($this->toolResults),
        ]);
    }

    /**
     * Récupérer toutes les métadonnées agrégées pour le frontend.
     *
     * Extrait les suggested_actions et table_data de tous les tools qui en retournent.
     *
     * @return array<string, mixed>
     */
    public function getAggregatedMetadata(): array
    {
        $debugFile = '/tmp/collector_debug.log';
        $metadata = [];

        // DEBUG DIRECT FILE : État initial
        $debugData = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'getAggregatedMetadata CALLED',
            'total_tool_results' => count($this->toolResults),
        ];
        file_put_contents($debugFile, json_encode($debugData, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

        // Agréger tous les suggested_actions de tous les tools
        $allSuggestedActions = [];
        foreach ($this->toolResults as $toolResult) {
            $result = $toolResult['result'];
            if (isset($result['suggested_actions']) && is_array($result['suggested_actions'])) {
                $allSuggestedActions = array_merge($allSuggestedActions, $result['suggested_actions']);
            }
        }

        // Ajouter au tableau metadata seulement si on a des actions
        if (count($allSuggestedActions) > 0) {
            $metadata['suggested_actions'] = $allSuggestedActions;
        }

        // Récupérer table_data du dernier tool qui en fournit (généralement un seul tool par requête)
        foreach ($this->toolResults as $toolResult) {
            $result = $toolResult['result'];

            // DEBUG DIRECT FILE : Vérification de chaque tool result
            $debugCheck = [
                'timestamp' => date('Y-m-d H:i:s'),
                'action' => 'Checking tool result',
                'tool_name' => $toolResult['tool_name'] ?? 'unknown',
                'has_table_data' => isset($result['table_data']),
                'result_keys' => array_keys($result),
            ];
            file_put_contents($debugFile, json_encode($debugCheck, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

            if (isset($result['table_data']) && is_array($result['table_data'])) {
                $metadata['table_data'] = $result['table_data'];

                // DEBUG DIRECT FILE : table_data trouvée
                $debugFound = [
                    'timestamp' => date('Y-m-d H:i:s'),
                    'action' => 'getAggregatedMetadata FOUND table_data',
                    'headers_count' => count($result['table_data']['headers'] ?? []),
                    'rows_count' => count($result['table_data']['rows'] ?? []),
                    'table_data_keys' => array_keys($result['table_data']),
                ];
                file_put_contents($debugFile, json_encode($debugFound, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

                // DEBUG : Logger la récupération
                $this->logger->info('[COLLECTOR] getAggregatedMetadata FOUND table_data', [
                    'headers_count' => count($result['table_data']['headers'] ?? []),
                    'rows_count' => count($result['table_data']['rows'] ?? []),
                ]);

                break; // On prend le premier trouvé (normalement il n'y en a qu'un)
            }
        }

        // DEBUG DIRECT FILE : Résultat final
        $debugFinal = [
            'timestamp' => date('Y-m-d H:i:s'),
            'action' => 'getAggregatedMetadata RETURNING',
            'has_table_data' => isset($metadata['table_data']),
            'has_suggested_actions' => isset($metadata['suggested_actions']),
            'metadata_keys' => array_keys($metadata),
            'total_tool_results_checked' => count($this->toolResults),
        ];
        file_put_contents($debugFile, json_encode($debugFinal, JSON_PRETTY_PRINT)."\n\n", FILE_APPEND);

        // DEBUG : Logger le résultat final
        $this->logger->info('[COLLECTOR] getAggregatedMetadata RETURNING', [
            'has_table_data' => isset($metadata['table_data']),
            'has_suggested_actions' => isset($metadata['suggested_actions']),
            'metadata_keys' => array_keys($metadata),
            'total_tool_results_checked' => count($this->toolResults),
        ]);

        return $metadata;
    }

    /**
     * Réinitialiser le collecteur (à appeler au début de chaque question).
     */
    public function reset(): void
    {
        $this->toolResults = [];
    }

    /**
     * Obtenir le nombre de tool results collectés.
     */
    public function count(): int
    {
        return count($this->toolResults);
    }
}
