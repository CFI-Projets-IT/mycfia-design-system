<?php

declare(strict_types=1);

namespace App\MessageHandler;

use App\Entity\User;
use App\Exception\ChatException;
use App\Message\ChatStreamMessage;
use App\Service\AiLoggerService;
use App\Service\AiPromptService;
use App\Service\AsyncExecutionContext;
use App\Service\Cfi\CfiTokenContext;
use App\Service\ChatStreamPublisher;
use App\Service\Tool;
use App\Service\ToolCallCollector;
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
        private ChatStreamPublisher $streamPublisher,
        private EntityManagerInterface $entityManager,
        #[Autowire(service: 'monolog.logger.chat')]
        private LoggerInterface $logger,
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

            // Réinitialiser le collecteur pour cette nouvelle question
            $this->toolCallCollector->reset();

            // 3. Publier événement "start"
            $this->streamPublisher->publishStart($message->conversationId, $message->messageId, $message->context);

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

            // 7. Stream agent IA chunk par chunk (option 'stream' => true)
            $result = $agent->call($messages, ['stream' => true]);

            $fullResponse = '';
            foreach ($result->getContent() as $chunk) {
                $fullResponse .= $chunk;

                // Publier chaque chunk via Mercure
                $this->streamPublisher->publishChunk($message->conversationId, $message->messageId, $chunk);
            }

            // IMPORTANT : Les métadonnées ne sont disponibles qu'APRÈS avoir consommé tous les chunks
            $resultMetadata = $result->getMetadata();

            // DEBUG : Logger TOUTES les métadonnées disponibles
            $this->logger->info('ChatStreamMessageHandler: DEBUG - Métadonnées disponibles', [
                'metadata_keys' => $resultMetadata ? array_keys($resultMetadata->all()) : [],
                'all_metadata' => $resultMetadata?->all(),
                'model_direct' => $resultMetadata?->get('model'),
                'token_usage_direct' => $resultMetadata?->get('token_usage'),
            ]);

            // 8. Calculer durée et métadonnées
            $durationMs = (int) ((microtime(true) - $startTime) * 1000);
            $toolsUsed = $this->toolCallCollector->getToolsUsed();

            // 9. Publier événement "complete" avec métadonnées (incluant model et token_usage)
            $this->streamPublisher->publishComplete($message->conversationId, $message->messageId, [
                'user_id' => $user->getId(),
                'tenant_id' => $tenantId,
                'context' => $message->context,
                'duration_ms' => $durationMs,
                'tools_used' => $toolsUsed,
                'answer_length' => strlen($fullResponse),
                'model' => $resultMetadata->get('model'),
                'token_usage' => $resultMetadata->get('token_usage'),
            ]);

            // 10. Logger l'appel
            $this->aiLogger->logToolCall(
                user: $user,
                toolName: sprintf('chat_%s_stream', $message->context),
                params: ['question' => $message->question, 'context' => $message->context, 'conversation_id' => $message->conversationId],
                result: ['answer_length' => strlen($fullResponse), 'tools_used' => $toolsUsed],
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
