<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Service collecteur de tool calls pour traçabilité des appels d'outils IA.
 *
 * Responsabilités :
 * - Collecter les noms des tools appelés durant l'exécution d'un agent
 * - Fournir une liste unique et ordonnée des tools utilisés
 * - Réinitialisation automatique entre les requêtes
 *
 * Architecture :
 * - Service stateful (stocke temporairement les tool calls)
 * - Thread-safe pour un contexte de requête unique
 * - Utilisé par GetOperationsTool, GetStocksTool, etc.
 *
 * Usage :
 * 1. reset() au début de chaque question (ChatService)
 * 2. addToolCall() dans chaque Tool::__invoke()
 * 3. getToolsUsed() pour récupérer la liste finale
 */
final class ToolCallCollector
{
    /**
     * @var array<int, string>
     */
    private array $toolCalls = [];

    /**
     * Enregistrer l'appel d'un tool.
     *
     * @param string $toolName Nom du tool appelé (ex: get_operations, get_stocks)
     */
    public function addToolCall(string $toolName): void
    {
        if (! in_array($toolName, $this->toolCalls, true)) {
            $this->toolCalls[] = $toolName;
        }
    }

    /**
     * Récupérer la liste des tools utilisés (unique et ordonnée).
     *
     * @return array<int, string>
     */
    public function getToolsUsed(): array
    {
        return $this->toolCalls;
    }

    /**
     * Réinitialiser le collecteur (à appeler au début de chaque question).
     */
    public function reset(): void
    {
        $this->toolCalls = [];
    }

    /**
     * Obtenir le nombre de tools appelés.
     */
    public function count(): int
    {
        return count($this->toolCalls);
    }
}
