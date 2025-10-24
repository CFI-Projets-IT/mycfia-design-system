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
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;

/**
 * Service orchestrateur pour le chat IA conversationnel multi-contexte.
 *
 * Responsabilités :
 * - Injection du prompt dynamique via AiPromptService
 * - Appel de l'agent IA avec contexte utilisateur
 * - Routage dynamique vers l'agent spécialisé selon le contexte
 * - Gestion des erreurs et timeouts
 * - Support du streaming Mercure (méthode streamQuestion)
 * - Logging complet pour traçabilité
 *
 * Architecture :
 * - 4 AgentInterface injectés via #[Target] (factures, commandes, stocks, general)
 * - Routage dynamique via getAgentByContext()
 * - AiPromptService pour rendu du prompt système
 * - CfiTenantService pour contexte Division
 * - AiLoggerService pour traçabilité des appels
 *
 * Contextes supportés :
 * - factures : Agent spécialisé factures (chat_factures)
 * - commandes : Agent spécialisé commandes (chat_commandes)
 * - stocks : Agent spécialisé stocks (chat_stocks)
 * - general : Agent généraliste (chat_general)
 */
final readonly class ChatService
{
    public function __construct(
        #[Target('chatFacturesAgent')]
        private AgentInterface $facturesAgent,
        #[Target('chatCommandesAgent')]
        private AgentInterface $commandesAgent,
        #[Target('chatStocksAgent')]
        private AgentInterface $stocksAgent,
        #[Target('chatGeneralAgent')]
        private AgentInterface $generalAgent,
        private AiPromptService $promptService,
        private CfiTenantService $tenantService,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        private ToolResultCollector $toolResultCollector,
        private ChatStreamPublisher $streamPublisher,
        #[Autowire(service: 'monolog.logger.chat')]
        private LoggerInterface $logger,
    ) {
    }

    /**
     * Traiter une question utilisateur via l'agent IA spécialisé selon le contexte.
     *
     * Workflow :
     * 1. Valider contexte utilisateur (tenant actif)
     * 2. Sélectionner l'agent spécialisé selon le contexte
     * 3. Rendre le prompt système dynamique avec contexte
     * 4. Appeler l'agent IA avec prompt + question
     * 5. Parser la réponse et extraire métadonnées
     * 6. Logger l'appel pour traçabilité
     *
     * @param string $question Question textuelle de l'utilisateur
     * @param User   $user     Utilisateur authentifié
     * @param string $context  Contexte du chat (factures|commandes|stocks|general), défaut: general
     *
     * @return ChatResponse Réponse structurée de l'agent
     *
     * @throws ChatException Si contexte invalide, agent échoue ou timeout
     */
    public function processQuestion(string $question, User $user, string $context = 'general'): ChatResponse
    {
        $startTime = microtime(true);

        // Réinitialiser les collecteurs pour cette nouvelle question
        $this->toolCallCollector->reset();
        $this->toolResultCollector->reset();

        try {
            // 1. Valider contexte utilisateur
            $tenantId = $this->tenantService->getCurrentTenantOrNull();
            if (null === $tenantId) {
                throw ChatException::invalidContext('Aucune division active pour cet utilisateur');
            }

            // 2. Sélectionner l'agent spécialisé selon le contexte
            $agent = $this->getAgentByContext($context);
            $templateName = $this->getTemplateByContext($context);

            // 3. Rendre le prompt système dynamique
            $systemPrompt = $this->promptService->renderPrompt(
                template: $templateName,
                user: $user,
                tools: $this->getRegisteredTools($context),
            );

            $this->logger->info('ChatService: Prompt système rendu', [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $context,
                'prompt_length' => strlen($systemPrompt),
            ]);

            // 4. Appeler l'agent IA avec MessageBag
            $messages = new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($question),
            );

            // LOG : Avant appel agent
            $this->logger->info('ChatService: AVANT appel agent', [
                'context' => $context,
                'agent' => get_class($agent),
                'registered_tools' => array_map(fn ($tool) => basename(str_replace('\\', '/', $tool)), $this->getRegisteredTools($context)),
            ]);

            $result = $agent->call($messages);

            // LOG : Après appel agent
            $this->logger->info('ChatService: APRÈS appel agent', [
                'context' => $context,
                'result_content_length' => strlen($result->getContent()),
                'result_metadata' => $result->getMetadata()->all(),
            ]);

            // 4. Parser la réponse
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);

            // Récupérer les tools utilisés pendant l'exécution
            $toolsUsed = $this->toolCallCollector->getToolsUsed();

            // LOG : Tools collectés
            $this->logger->info('ChatService: Tools collectés après appel', [
                'context' => $context,
                'tools_used' => $toolsUsed,
                'tools_count' => count($toolsUsed),
            ]);

            // Récupérer les métadonnées agrégées incluant table_data
            $aggregatedMetadata = $this->toolResultCollector->getAggregatedMetadata();

            $chatResponse = ChatResponse::fromAgentResponse(
                agentResponse: $result->getContent(),
                metadata: [
                    'user_id' => $user->getId(),
                    'tenant_id' => $tenantId,
                    'timestamp' => (new \DateTime())->format('Y-m-d H:i:s'),
                    'model' => $result->getMetadata()->get('model'),
                    'token_usage' => $result->getMetadata()->get('token_usage'),
                    'suggested_actions' => $aggregatedMetadata['suggested_actions'] ?? [],
                    'table_data' => $aggregatedMetadata['table_data'] ?? null,
                ],
                durationMs: $durationMs,
                toolsUsed: $toolsUsed,
            );

            // 5. Logger l'appel
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: sprintf('chat_%s', $context),
                params: ['question' => $question, 'context' => $context],
                result: ['answer_length' => strlen($chatResponse->answer)],
                durationMs: $durationMs,
            );

            $this->logger->info('ChatService: Question traitée avec succès', [
                'user_id' => $user->getId(),
                'context' => $context,
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
     * Traiter une question en mode streaming Mercure (SSE) dans un contexte spécifique.
     *
     * Workflow :
     * 1. Valider contexte utilisateur (tenant actif)
     * 2. Générer UUID v4 pour message_id
     * 3. Publier événement "start" via Mercure
     * 4. Sélectionner l'agent spécialisé selon le contexte
     * 5. Rendre le prompt système dynamique avec contexte
     * 6. Appeler l'agent IA en mode streaming
     * 7. Publier chaque chunk progressivement via Mercure
     * 8. Publier événement "complete" avec métadonnées
     * 9. Logger l'appel pour traçabilité
     *
     * @param string $question       Question textuelle de l'utilisateur
     * @param User   $user           Utilisateur authentifié
     * @param string $context        Contexte du chat (factures|commandes|stocks|general)
     * @param string $conversationId UUID v4 de la conversation (topic Mercure)
     *
     * @return string Message ID (UUID v4) pour suivi du stream
     *
     * @throws ChatException Si streaming échoue
     */
    public function streamQuestion(string $question, User $user, string $context, string $conversationId): string
    {
        $startTime = microtime(true);

        // Générer UUID v4 pour le message
        $messageId = \Symfony\Component\Uid\Uuid::v4()->toString();

        // Réinitialiser les collecteurs pour cette nouvelle question
        $this->toolCallCollector->reset();
        $this->toolResultCollector->reset();

        $this->logger->info('[DEBUG] ChatService::streamQuestion: Collecteurs réinitialisés', [
            'message_id' => $messageId,
            'timestamp' => microtime(true),
            'collector_count_after_reset' => $this->toolResultCollector->count(),
        ]);

        try {
            // 1. Valider contexte utilisateur
            $tenantId = $this->tenantService->getCurrentTenantOrNull();
            if (null === $tenantId) {
                throw ChatException::invalidContext('Aucune division active pour cet utilisateur');
            }

            // 2. Publier événement "start"
            $this->streamPublisher->publishStart($conversationId, $messageId, $context);

            // 4. Sélectionner l'agent spécialisé selon le contexte
            $agent = $this->getAgentByContext($context);
            $templateName = $this->getTemplateByContext($context);

            // 5. Rendre le prompt système dynamique
            $systemPrompt = $this->promptService->renderPrompt(
                template: $templateName,
                user: $user,
                tools: $this->getRegisteredTools($context),
            );

            $this->logger->info('ChatService: Streaming started', [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $context,
                'message_id' => $messageId,
                'conversation_id' => $conversationId,
            ]);

            // 6. Appeler l'agent IA avec MessageBag
            $messages = new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($question),
            );

            // 7. Stream agent IA chunk par chunk (option 'stream' => true)
            $result = $agent->call($messages, ['stream' => true]);

            $fullResponse = '';
            foreach ($result->getContent() as $chunk) {
                $fullResponse .= $chunk;

                // Publier chaque chunk via Mercure
                $this->streamPublisher->publishChunk($conversationId, $messageId, $chunk);
            }

            // IMPORTANT : Les métadonnées ne sont disponibles qu'APRÈS avoir consommé tous les chunks
            // Récupérer les métadonnées après la fin du streaming
            $resultMetadata = $result->getMetadata();

            // DEBUG : Logger les métadonnées pour diagnostic
            $this->logger->info('ChatService: Métadonnées streaming récupérées', [
                'model' => $resultMetadata->get('model'),
                'token_usage' => $resultMetadata->get('token_usage'),
                'all_metadata' => $resultMetadata->all(),
            ]);

            // 8. Calculer durée et métadonnées
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $toolsUsed = $this->toolCallCollector->getToolsUsed();

            // Récupérer les métadonnées agrégées incluant table_data
            $this->logger->info('[DEBUG] ChatService::streamQuestion: AVANT getAggregatedMetadata()', [
                'message_id' => $messageId,
                'collector_count' => $this->toolResultCollector->count(),
                'tools_used' => $toolsUsed,
            ]);

            $aggregatedMetadata = $this->toolResultCollector->getAggregatedMetadata();

            // DEBUG : Logger les métadonnées agrégées
            $this->logger->info('[DEBUG] ChatService::streamQuestion: APRÈS getAggregatedMetadata()', [
                'message_id' => $messageId,
                'has_table_data' => isset($aggregatedMetadata['table_data']),
                'has_suggested_actions' => isset($aggregatedMetadata['suggested_actions']),
                'table_data_keys' => isset($aggregatedMetadata['table_data']) ? array_keys($aggregatedMetadata['table_data']) : [],
                'aggregated_metadata_keys' => array_keys($aggregatedMetadata),
            ]);

            // 9. Publier événement "complete" avec métadonnées (incluant model, token_usage et table_data)
            $this->streamPublisher->publishComplete($conversationId, $messageId, [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $context,
                'duration_ms' => $durationMs,
                'tools_used' => $toolsUsed,
                'answer_length' => strlen($fullResponse),
                'model' => $resultMetadata->get('model'),
                'token_usage' => $resultMetadata->get('token_usage'),
                'suggested_actions' => $aggregatedMetadata['suggested_actions'] ?? [],
                'table_data' => $aggregatedMetadata['table_data'] ?? null,
            ]);

            // 10. Logger l'appel
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: sprintf('chat_%s_stream', $context),
                params: ['question' => $question, 'context' => $context, 'conversation_id' => $conversationId],
                result: ['answer_length' => strlen($fullResponse), 'tools_used' => $toolsUsed],
                durationMs: $durationMs,
            );

            $this->logger->info('ChatService: Streaming completed successfully', [
                'user_id' => $user->getId(),
                'context' => $context,
                'message_id' => $messageId,
                'duration_ms' => $durationMs,
                'tools_used' => count($toolsUsed),
            ]);

            return $messageId;
        } catch (ChatException $e) {
            // Publier erreur via Mercure
            $this->streamPublisher->publishError($conversationId, $messageId, $e->getMessage());

            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('ChatService: Streaming error', [
                'message_id' => $messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Publier erreur via Mercure
            $this->streamPublisher->publishError(
                $conversationId,
                $messageId,
                'Désolé, une erreur est survenue pendant la génération de la réponse.'
            );

            throw ChatException::streamingFailed($e->getMessage(), $e);
        }
    }

    /**
     * Sélectionner l'agent IA spécialisé selon le contexte.
     *
     * @param string $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return AgentInterface Agent IA injecté correspondant au contexte
     *
     * @throws ChatException Si contexte invalide
     */
    private function getAgentByContext(string $context): AgentInterface
    {
        return match ($context) {
            'factures' => $this->facturesAgent,
            'commandes' => $this->commandesAgent,
            'stocks' => $this->stocksAgent,
            'general' => $this->generalAgent,
            default => throw ChatException::invalidContext(sprintf('Contexte "%s" invalide. Contextes autorisés : factures, commandes, stocks, general', $context)),
        };
    }

    /**
     * Récupérer le template Twig du prompt selon le contexte.
     *
     * @param string $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return string Nom du template Twig (ex: ai/prompts/chat_factures.md.twig)
     */
    private function getTemplateByContext(string $context): string
    {
        return match ($context) {
            'factures' => 'ai/prompts/chat_factures.md.twig',
            'commandes' => 'ai/prompts/chat_commandes.md.twig',
            'stocks' => 'ai/prompts/chat_stocks.md.twig',
            'general' => 'ai/prompts/chat_general.md.twig',
            default => 'ai/prompts/chat_general.md.twig', // Fallback sécurisé
        };
    }

    /**
     * Récupérer la liste des tools enregistrés pour le contexte spécifié.
     *
     * @param string $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return array<int, class-string>
     */
    private function getRegisteredTools(string $context): array
    {
        return match ($context) {
            'factures' => [
                Tool\GetFacturesTool::class, // Tool spécialisé factures (type="mail" forcé)
            ],
            'commandes' => [
                Tool\GetCommandesTool::class, // Tool spécialisé commandes (type!=mail)
            ],
            'stocks' => [
                Tool\GetStocksTool::class,
                Tool\GetStockAlertsTool::class,
            ],
            'general' => [
                Tool\GetOperationsTool::class, // Tool générique pour tous types
                Tool\GetStocksTool::class,
                Tool\GetOperationStatsTool::class,
                Tool\GetStockAlertsTool::class,
            ],
            default => [], // Aucun tool pour contexte invalide
        };
    }
}
