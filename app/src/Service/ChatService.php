<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\ChatResponse;
use App\Entity\User;
use App\Exception\ChatException;
use App\Service\Cfi\CfiTenantService;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Service orchestrateur pour le chat IA conversationnel.
 *
 * Responsabilités :
 * - Injection du prompt dynamique via AiPromptService
 * - Appel de l'agent IA avec contexte utilisateur
 * - Gestion des erreurs et timeouts
 * - Support du streaming Mercure (méthode streamQuestion)
 * - Logging complet pour traçabilité
 *
 * Architecture :
 * - Injection AgentInterface ciblée (#[Target('chat_operations')])
 * - AiPromptService pour rendu du prompt système
 * - CfiTenantService pour contexte Division
 * - AiLoggerService pour traçabilité des appels
 */
final readonly class ChatService
{
    public function __construct(
        #[Target('chatOperationsAgent')]
        private AgentInterface $chatAgent,
        private AiPromptService $promptService,
        private CfiTenantService $tenantService,
        private AiLoggerService $aiLogger,
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Traiter une question utilisateur via l'agent IA.
     *
     * Workflow :
     * 1. Valider contexte utilisateur (tenant actif)
     * 2. Rendre le prompt système dynamique avec contexte
     * 3. Appeler l'agent IA avec prompt + question
     * 4. Parser la réponse et extraire métadonnées
     * 5. Logger l'appel pour traçabilité
     *
     * @param string $question Question textuelle de l'utilisateur
     * @param User   $user     Utilisateur authentifié
     *
     * @return ChatResponse Réponse structurée de l'agent
     *
     * @throws ChatException Si contexte invalide, agent échoue ou timeout
     */
    public function processQuestion(string $question, User $user): ChatResponse
    {
        $startTime = microtime(true);

        try {
            // 1. Valider contexte utilisateur
            $tenantId = $this->tenantService->getCurrentTenantOrNull();
            if (null === $tenantId) {
                throw ChatException::invalidContext('Aucune division active pour cet utilisateur');
            }

            // 2. Rendre le prompt système dynamique
            $systemPrompt = $this->promptService->renderPrompt(
                template: 'ai/prompts/chat_operations.md.twig',
                user: $user,
                tools: $this->getRegisteredTools(),
            );

            $this->logger->info('ChatService: Prompt système rendu', [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'prompt_length' => strlen($systemPrompt),
            ]);

            // 3. Appeler l'agent IA avec MessageBag
            $messages = new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($question),
            );

            $result = $this->chatAgent->call($messages);

            // 4. Parser la réponse
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            $chatResponse = ChatResponse::fromAgentResponse(
                agentResponse: $result->getContent(),
                metadata: [
                    'user_id' => $user->getId(),
                    'tenant_id' => $tenantId,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'model' => $result->getMetadata()->get('model'),
                    'token_usage' => $result->getMetadata()->get('token_usage'),
                ],
                durationMs: $durationMs,
            );

            // 5. Logger l'appel
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: 'chat_operations',
                params: ['question' => $question],
                result: ['answer_length' => strlen($chatResponse->answer)],
                durationMs: $durationMs,
            );

            $this->logger->info('ChatService: Question traitée avec succès', [
                'user_id' => $user->getId(),
                'duration_ms' => $durationMs,
                'tools_used' => count($chatResponse->toolsUsed),
            ]);

            return $chatResponse;
        } catch (ChatException $e) {
            // Relancer les exceptions métier
            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('ChatService: Erreur lors du traitement de la question', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw ChatException::agentFailed($e->getMessage(), $e);
        }
    }

    /**
     * Traiter une question en mode streaming Mercure (SSE).
     *
     * TODO Sprint S1+: Implémenter support streaming complet avec :
     * - Génération UUID v4 pour message_id
     * - Publication progressive via Mercure Hub
     * - Gestion des chunks de réponse
     * - Gestion des erreurs en cours de stream
     *
     * @param string $question Question textuelle de l'utilisateur
     * @param User   $user     Utilisateur authentifié
     *
     * @return string Message ID (UUID v4) pour suivi du stream
     *
     * @throws ChatException Si streaming échoue
     */
    public function streamQuestion(string $question, User $user): string
    {
        throw ChatException::streamingFailed('Support streaming non implémenté - Sprint S1+');
    }

    /**
     * Récupérer la liste des tools enregistrés pour l'agent.
     *
     * @return array<int, class-string>
     */
    private function getRegisteredTools(): array
    {
        // TODO Sprint S1+: Récupérer dynamiquement depuis configuration ai.yaml
        return [
            Tool\GetOperationsTool::class,
            Tool\GetStocksTool::class,
            Tool\GetOperationStatsTool::class,
            Tool\GetStockAlertsTool::class,
        ];
    }
}
