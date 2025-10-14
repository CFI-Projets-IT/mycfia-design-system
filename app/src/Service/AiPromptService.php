<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Service\Cfi\CfiTenantService;
use Psr\Log\LoggerInterface;
use Twig\Environment;

/**
 * Service de rendu dynamique des prompts IA avec contexte utilisateur.
 *
 * Permet d'injecter dynamiquement dans les templates Twig :
 * - Informations utilisateur (nom, rôle)
 * - Division/Tenant actuel
 * - Liste des tools disponibles
 * - Timestamp actuel
 *
 * Architecture composable avec partials réutilisables.
 */
final readonly class AiPromptService
{
    public function __construct(
        private Environment $twig,
        private CfiTenantService $tenantService,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Rendre un prompt IA avec contexte dynamique.
     *
     * @param string $template Chemin du template Twig (ex: 'ai/prompts/chat_operations.md.twig')
     * @param User   $user     Utilisateur courant
     * @param array  $tools    Liste des tools disponibles pour l'agent (optionnel)
     *
     * @return string Prompt rendu avec contexte injecté
     *
     * @throws \Twig\Error\LoaderError
     * @throws \Twig\Error\RuntimeError
     * @throws \Twig\Error\SyntaxError
     */
    public function renderPrompt(string $template, User $user, array $tools = []): string
    {
        $startTime = microtime(true);

        try {
            // Récupérer tenant actuel
            $tenantId = $this->tenantService->getCurrentTenantOrNull();

            // Construire contexte pour le template
            $context = [
                'user' => $user,
                'division' => [
                    'idCfi' => $tenantId,
                    'nom' => $this->getTenantName($tenantId),
                ],
                'tools' => $this->formatToolsForPrompt($tools),
                'timestamp' => new \DateTime(),
            ];

            // Rendre le template
            $prompt = $this->twig->render($template, $context);

            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $this->logger->info('AiPromptService: Prompt rendu avec succès', [
                'template' => $template,
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'duration_ms' => $durationMs,
                'prompt_length' => strlen($prompt),
            ]);

            return $prompt;
        } catch (\Exception $e) {
            $this->logger->error('AiPromptService: Erreur lors du rendu du prompt', [
                'template' => $template,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw $e;
        }
    }

    /**
     * Récupérer le nom du tenant depuis l'ID.
     *
     * TODO Sprint S1+: Implémenter cache ou service de récupération nom Division
     * Pour l'instant, retourne un placeholder.
     */
    private function getTenantName(?int $tenantId): string
    {
        if (null === $tenantId) {
            return 'Non défini';
        }

        // TODO: Récupérer le nom réel depuis Division ou session
        return "Division #{$tenantId}";
    }

    /**
     * Formatter la liste des tools pour le prompt.
     *
     * Transforme la liste de classes en format lisible pour le LLM.
     *
     * @param array $tools Liste de classes de tools
     *
     * @return array{name: string, description: string}[]
     */
    private function formatToolsForPrompt(array $tools): array
    {
        if (empty($tools)) {
            return [];
        }

        $formatted = [];

        foreach ($tools as $toolClass) {
            // Extraire nom depuis la classe
            $shortName = (new \ReflectionClass($toolClass))->getShortName();
            $name = $this->convertClassNameToToolName($shortName);

            // TODO Sprint S1+: Extraire description depuis attribut #[AsTool] via Reflection
            $description = "Tool: {$shortName}";

            $formatted[] = [
                'name' => $name,
                'description' => $description,
            ];
        }

        return $formatted;
    }

    /**
     * Convertir nom de classe en nom de tool.
     *
     * Exemples :
     * - GetOperationsTool → get_operations
     * - GetStockAlertsTool → get_stock_alerts
     */
    private function convertClassNameToToolName(string $className): string
    {
        // Retirer suffixe "Tool"
        $name = preg_replace('/Tool$/', '', $className);

        // Convertir CamelCase en snake_case
        $name = strtolower(preg_replace('/(?<!^)[A-Z]/', '_$0', $name ?? ''));

        return $name ?? '';
    }
}
