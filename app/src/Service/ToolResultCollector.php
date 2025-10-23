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

    /**
     * Enregistrer le résultat complet d'un tool.
     *
     * @param string               $toolName Nom du tool (ex: get_factures)
     * @param array<string, mixed> $result   Résultat complet du tool
     */
    public function addToolResult(string $toolName, array $result): void
    {
        $this->toolResults[] = [
            'tool_name' => $toolName,
            'result' => $result,
        ];
    }

    /**
     * Récupérer toutes les métadonnées agrégées pour le frontend.
     *
     * Extrait les suggested_actions de tous les tools qui en retournent.
     *
     * @return array<string, mixed>
     */
    public function getAggregatedMetadata(): array
    {
        $metadata = [];

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
