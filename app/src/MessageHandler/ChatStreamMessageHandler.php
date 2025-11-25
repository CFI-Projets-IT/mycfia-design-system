<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\ChatConversation;
use App\Entity\Division;
use App\Entity\User;
use App\Exception\ChatException;
use App\Message\ChatStreamMessage;
use App\Service\AiLoggerService;
use App\Service\AiPromptService;
use App\Service\AsyncExecutionContext;
use App\Service\Cfi\CfiTokenContext;
use App\Service\ChatPersistenceService;
use App\Service\ChatStreamPublisher;
use App\Service\Tool;
use App\Service\ToolCallCollector;
use App\Service\ToolResultCollector;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\AI\Agent\AgentInterface;
use Symfony\AI\Platform\Message\Message;
use Symfony\AI\Platform\Message\MessageBag;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\DependencyInjection\Attribute\Target;
use Symfony\Component\Messenger\Attribute\AsMessageHandler;

/**
 * Handler asynchrone pour le streaming de réponses chat IA via Mercure.
 *
 * Responsabilités :
 * - Récupérer l'utilisateur depuis la base de données (via userId)
 * - Exécuter le streaming de l'agent IA en arrière-plan
 * - Publier les chunks progressivement via Mercure
 * - Gérer les erreurs et publier les événements d'erreur
 * - Logger l'exécution pour traçabilité
 *
 * Architecture :
 * - Traite les messages ChatStreamMessage de manière asynchrone
 * - Exécute le code métier identique à ChatService::streamQuestion()
 * - Permet au contrôleur de retourner immédiatement sans bloquer
 *
 * Contextes supportés :
 * - factures : Agent spécialisé factures (chat_factures)
 * - commandes : Agent spécialisé commandes (chat_commandes)
 * - stocks : Agent spécialisé stocks (chat_stocks)
 * - general : Agent généraliste (chat_general)
 */
#[AsMessageHandler]
final readonly class ChatStreamMessageHandler
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
        private CfiTokenContext $cfiTokenContext,
        private AsyncExecutionContext $asyncContext,
        private AiLoggerService $aiLogger,
        private ToolCallCollector $toolCallCollector,
        private ToolResultCollector $toolResultCollector,
        private ChatStreamPublisher $streamPublisher,
        private ChatPersistenceService $persistenceService,
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'monolog.logger.chat')]
        private LoggerInterface $logger,
        #[Autowire(service: 'monolog.logger.llm')]
        private LoggerInterface $llmLogger,
    ) {
    }

    /**
     * Traiter le message de streaming de manière asynchrone.
     *
     * @param ChatStreamMessage $message Message contenant les données du streaming
     */
    public function __invoke(ChatStreamMessage $message): void
    {
        $startTime = microtime(true);

        try {
            // 1. Récupérer l'utilisateur depuis la base de données
            $user = $this->entityManager->getRepository(User::class)->find($message->userId);
            if (null === $user) {
                throw new \RuntimeException(sprintf('User with ID %d not found', $message->userId));
            }

            // 2. Construire le tenant depuis les données du message (contexte async, pas de session)
            $tenant = $this->buildTenantFromMessage($message, $user);

            // 3. Injecter le contexte d'exécution asynchrone (User + Tenant + Token)
            $this->asyncContext->setContext($user, $tenant);
            $this->cfiTokenContext->setToken($message->cfiToken);

            $tenantId = $message->tenantId;

            // Réinitialiser les collecteurs pour cette nouvelle question
            $this->toolCallCollector->reset();
            $this->toolResultCollector->reset();

            // 3. Publier événement "start"
            $this->streamPublisher->publishStart($message->conversationId, $message->messageId, $message->context);

            // 3.1. PERSISTANCE : Créer/récupérer conversation et enregistrer message user
            $conversation = $this->getOrCreateConversation($user, $message);
            $this->persistenceService->saveMessage(
                conversation: $conversation,
                role: 'user',
                content: $message->question,
                type: 'text',
                data: null
            );

            // 4. Sélectionner l'agent spécialisé selon le contexte
            $agent = $this->getAgentByContext($message->context);
            $templateName = $this->getTemplateByContext($message->context);

            // 5. Rendre le prompt système dynamique avec tenantId du message (async context)
            $systemPrompt = $this->promptService->renderPrompt(
                template: $templateName,
                user: $user,
                tools: $this->getRegisteredTools($message->context),
                tenantId: $tenantId,
            );

            $this->logger->info('ChatStreamMessageHandler: Streaming started', [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $message->context,
                'message_id' => $message->messageId,
                'conversation_id' => $message->conversationId,
            ]);

            // 6. Appeler l'agent IA avec MessageBag
            $messages = new MessageBag(
                Message::forSystem($systemPrompt),
                Message::ofUser($message->question),
            );

            // 7. Appel NON-STREAMING pour récupérer métadonnées complètes
            // TokenOutputProcessor (services.yaml) capture automatiquement model + token_usage
            $llmStartTime = microtime(true);
            $result = $agent->call($messages); // Pas d'option ['stream' => true]
            $llmDurationMs = (microtime(true) - $llmStartTime) * 1000;

            // 8. Récupérer réponse complète et métadonnées
            $content = $result->getContent();
            $fullResponse = \is_string($content) ? $content : '';
            $resultMetadata = $result->getMetadata();

            // Extraire token_usage
            $tokenUsage = $resultMetadata->get('token_usage');
            $promptTokens = $tokenUsage->promptTokens ?? 0;
            $completionTokens = $tokenUsage->completionTokens ?? 0;
            $totalTokens = $tokenUsage->totalTokens ?? 0;

            // KPI LLM : Logger temps génération + tokens utilisés
            $this->llmLogger->info('LLM Call', [
                'model' => $resultMetadata->get('model') ?? 'unknown',
                'duration_ms' => $llmDurationMs,
                'prompt_tokens' => $promptTokens,
                'completion_tokens' => $completionTokens,
                'total_tokens' => $totalTokens,
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $message->context,
                'conversation_id' => $message->conversationId,
                'message_id' => $message->messageId,
                'question_length' => mb_strlen($message->question, 'UTF-8'),
                'answer_length' => mb_strlen($fullResponse, 'UTF-8'),
            ]);

            // DEBUG : Logger métadonnées capturées (model + token_usage)
            $this->logger->info('ChatStreamMessageHandler: Métadonnées capturées', [
                'metadata_keys' => array_keys($resultMetadata->all()),
                'model' => $resultMetadata->get('model'),
                'token_usage' => $resultMetadata->get('token_usage'),
            ]);

            // 9. Simuler streaming en découpant la réponse en chunks
            // Publier progressivement via Mercure pour conserver l'UX de streaming
            // ⚠️ Utiliser mb_substr() pour respecter les caractères UTF-8 multi-bytes (©, é, etc.)
            $chunkSize = 50; // Taille des chunks en caractères (pas en octets)
            $responseLength = mb_strlen($fullResponse, 'UTF-8');

            for ($offset = 0; $offset < $responseLength; $offset += $chunkSize) {
                $chunk = mb_substr($fullResponse, $offset, $chunkSize, 'UTF-8');

                // Publier chaque chunk via Mercure
                $this->streamPublisher->publishChunk($message->conversationId, $message->messageId, $chunk);

                // Optionnel : Délai léger pour simuler streaming naturel
                usleep(30000); // 30ms entre chaque chunk
            }

            // 10. Calculer durée et métadonnées
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $toolsUsed = $this->toolCallCollector->getToolsUsed();

            // Récupérer les métadonnées agrégées des tools (suggested_actions, table_data, etc.)
            $toolMetadata = $this->toolResultCollector->getAggregatedMetadata();

            // DEBUG : Logger les métadonnées des tools
            $this->logger->info('[DEBUG HANDLER] getAggregatedMetadata result', [
                'has_table_data' => isset($toolMetadata['table_data']),
                'has_suggested_actions' => isset($toolMetadata['suggested_actions']),
                'metadata_keys' => array_keys($toolMetadata),
                'collector_count' => $this->toolResultCollector->count(),
            ]);

            // 10.1. PERSISTANCE : Enregistrer message assistant avec metadata
            $messageType = isset($toolMetadata['table_data']) ? 'table' : 'text';
            $messageData = ! empty($toolMetadata) ? $toolMetadata : null;

            $this->persistenceService->saveMessage(
                conversation: $conversation,
                role: 'assistant',
                content: $fullResponse,
                type: $messageType,
                data: $messageData
            );

            // 11. Publier événement "complete" avec métadonnées (incluant model, token_usage, suggested_actions, table_data)
            $this->streamPublisher->publishComplete($message->conversationId, $message->messageId, array_merge([
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $message->context,
                'duration_ms' => $durationMs,
                'tools_used' => $toolsUsed,
                'answer_length' => mb_strlen($fullResponse, 'UTF-8'),
                'model' => $resultMetadata->get('model'),
                'token_usage' => $resultMetadata->get('token_usage'),
                // Données de conversation pour le frontend (bouton favori)
                'db_conversation_id' => $conversation->getId(),
                'is_favorite' => $conversation->isFavorite(),
            ], $toolMetadata));

            // 12. Logger l'appel
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: sprintf('chat_%s_stream', $message->context),
                params: ['question' => $message->question, 'context' => $message->context, 'conversation_id' => $message->conversationId],
                result: ['answer_length' => mb_strlen($fullResponse, 'UTF-8'), 'tools_used' => $toolsUsed],
                durationMs: $durationMs,
            );

            $this->logger->info('ChatStreamMessageHandler: Streaming completed successfully', [
                'user_id' => $user->getId(),
                'context' => $message->context,
                'message_id' => $message->messageId,
                'duration_ms' => $durationMs,
                'tools_used' => count($toolsUsed),
            ]);
        } catch (ChatException $e) {
            // Publier erreur via Mercure
            $this->streamPublisher->publishError($message->conversationId, $message->messageId, $e->getMessage());

            $this->logger->error('ChatStreamMessageHandler: Chat exception', [
                'message_id' => $message->messageId,
                'error' => $e->getMessage(),
            ]);

            throw $e;
        } catch (\Exception $e) {
            $this->logger->error('ChatStreamMessageHandler: Streaming error', [
                'message_id' => $message->messageId,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            // Publier erreur via Mercure
            $this->streamPublisher->publishError(
                $message->conversationId,
                $message->messageId,
                'Désolé, une erreur est survenue pendant la génération de la réponse.'
            );

            throw $e;
        } finally {
            // Nettoyer le contexte d'exécution asynchrone après traitement
            $this->asyncContext->clear();
            $this->cfiTokenContext->clearToken();
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

    /**
     * Récupérer ou créer une conversation pour ce streaming.
     *
     * Crée une nouvelle conversation avec titre généré depuis la question utilisateur.
     * Le titre est tronqué à 50 caractères pour respecter les contraintes UX.
     *
     * @param User              $user    Utilisateur propriétaire
     * @param ChatStreamMessage $message Message de streaming contenant le contexte
     *
     * @return ChatConversation Conversation créée ou existante
     *
     * @throws \RuntimeException Si la division n'existe pas
     */
    private function getOrCreateConversation(User $user, ChatStreamMessage $message): ChatConversation
    {
        // Récupérer la Division entity depuis idDivision (CFI externe ID)
        // Note : tenantId dans le message = idDivision CFI, PAS l'id auto-incrémenté local
        $division = $this->entityManager->getRepository(Division::class)->findOneBy([
            'idDivision' => $message->tenantId,
        ]);

        if (null === $division) {
            throw new \RuntimeException(sprintf('Division with idDivision %d not found', $message->tenantId));
        }

        // Générer le titre depuis la question (tronqué à 50 caractères)
        $title = mb_strlen($message->question, 'UTF-8') > 50
            ? mb_substr($message->question, 0, 47, 'UTF-8').'...'
            : $message->question;

        // Créer une nouvelle conversation
        // Note : Pour l'instant, une nouvelle conversation est créée à chaque message.
        // Phase 2 : Améliorer en stockant l'ID BDD dans la session pour récupérer la conversation existante.
        return $this->persistenceService->createConversation(
            user: $user,
            tenant: $division,
            context: $message->context,
            title: $title
        );
    }

    /**
     * Construire un TenantDto depuis les données du message (contexte async).
     *
     * @param ChatStreamMessage $message Message contenant tenantId
     * @param User              $user    Utilisateur authentifié
     *
     * @return \App\DTO\Cfi\TenantDto Tenant construit depuis la division de l'utilisateur
     *
     * @throws \RuntimeException Si la division n'existe pas
     */
    private function buildTenantFromMessage(ChatStreamMessage $message, User $user): \App\DTO\Cfi\TenantDto
    {
        $division = $user->getDivision();

        if (null === $division) {
            throw new \RuntimeException(sprintf('User %d has no division assigned', $user->getId()));
        }

        // Vérifier que tenantId du message correspond à la division de l'utilisateur
        if ($division->getIdDivision() !== $message->tenantId) {
            throw new \RuntimeException(sprintf('Tenant ID mismatch: message has %d but user has %d', $message->tenantId, $division->getIdDivision()));
        }

        return new \App\DTO\Cfi\TenantDto(
            idCfi: $division->getIdDivision(),
            nom: $division->getNomDivision(),
        );
    }
}
