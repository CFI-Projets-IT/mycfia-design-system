<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\ChatStreamMessage;
use App\Security\UserAuthenticationService;
use App\Service\Cfi\CfiSessionService;
use App\Service\Cfi\CfiTenantService;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Contrôleur pour l'interface de chat IA conversationnel multi-contexte.
 *
 * Responsabilités :
 * - Affichage de l'interface chat (template Twig)
 * - Traitement des questions utilisateur via ChatService
 * - Support streaming Mercure (SSE) pour réponses en temps réel
 * - Gestion des conversations (session-based)
 * - Routage dynamique par contexte (factures, commandes, stocks, general)
 *
 * Architecture :
 * - Route GET /chat/{context} : Affichage interface contextuelle
 * - Route POST /chat/{context}/message : Question simple (sync)
 * - Route POST /chat/{context}/stream : Question streaming (async Mercure)
 *
 * Contextes disponibles :
 * - factures : Agent spécialisé factures (get_operations type courrier/mail)
 * - commandes : Agent spécialisé commandes (get_operations type all)
 * - stocks : Agent spécialisé stocks (get_stocks + get_stock_alerts)
 * - general : Agent généraliste (tous les tools)
 */
#[Route('/chat', name: 'chat_')]
final class ChatController extends AbstractController
{
    /**
     * Contextes de chat autorisés.
     *
     * Chaque contexte correspond à un agent IA spécialisé configuré dans ai.yaml.
     */
    private const ALLOWED_CONTEXTS = ['factures', 'commandes', 'stocks', 'general'];

    public function __construct(
        private readonly UserAuthenticationService $authService,
        private readonly CfiTenantService $tenantService,
        private readonly CfiSessionService $cfiSession,
        private readonly string $mercurePublicUrl,
        private readonly MessageBusInterface $messageBus,
        private readonly \Lcobucci\JWT\Configuration $jwtConfiguration,
    ) {
    }

    /**
     * Page principale du chat IA contextuel.
     *
     * Initialise une conversation (UUID v4) si non existante dans la session.
     * Affiche l'interface chat avec historique éventuel pour le contexte spécifié.
     *
     * @param Request $request Requête HTTP pour accès session
     * @param string  $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return Response Template Twig chat/index.html.twig ou redirection si contexte invalide
     */
    #[Route('/{context}', name: 'index', methods: ['GET'])]
    public function index(Request $request, string $context): Response
    {
        // Validation du contexte
        if (! in_array($context, self::ALLOWED_CONTEXTS, true)) {
            $this->addFlash('error', sprintf(
                'Contexte "%s" invalide. Contextes autorisés : %s',
                $context,
                implode(', ', self::ALLOWED_CONTEXTS)
            ));

            return $this->redirectToRoute('chat_index', ['context' => 'general']);
        }

        // Récupérer ou créer ID conversation depuis session (par contexte)
        $sessionKey = sprintf('chat_conversation_id_%s', $context);
        $conversationId = $request->getSession()->get($sessionKey);
        if (null === $conversationId) {
            $conversationId = Uuid::v4()->toString();
            $request->getSession()->set($sessionKey, $conversationId);
        }

        // Générer un JWT Mercure pour autoriser l'abonnement au topic de cette conversation
        $mercureJwt = $this->jwtConfiguration->builder()
            ->withClaim('mercure', [
                'subscribe' => [sprintf('chat/%s', $conversationId)],
            ])
            ->getToken($this->jwtConfiguration->signer(), $this->jwtConfiguration->signingKey())
            ->toString();

        return $this->render('chat/index.html.twig', [
            'context' => $context,
            'conversationId' => $conversationId,
            'mercureUrl' => $this->mercurePublicUrl,
            'mercureJwt' => $mercureJwt,
        ]);
    }

    /**
     * Traiter une question utilisateur (mode synchrone) dans un contexte spécifique.
     *
     * Endpoint pour questions simples sans streaming.
     * Retourne la réponse complète de l'agent IA spécialisé selon le contexte.
     *
     * @param Request     $request     Requête HTTP contenant la question (JSON)
     * @param string      $context     Contexte du chat (factures|commandes|stocks|general)
     * @param ChatService $chatService Service orchestrateur chat
     *
     * @return JsonResponse Réponse structurée de l'agent
     */
    #[Route('/{context}/message', name: 'message', methods: ['POST'])]
    public function message(Request $request, string $context, ChatService $chatService): JsonResponse
    {
        // Validation du contexte
        if (! in_array($context, self::ALLOWED_CONTEXTS, true)) {
            return $this->json([
                'error' => 'Contexte invalide',
                'allowedContexts' => self::ALLOWED_CONTEXTS,
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';

        if (empty($question)) {
            return $this->json(['error' => 'La question ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Authentification via service centralisé
            $user = $this->authService->getAuthenticatedUser();
            if (null === $user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Traiter la question via ChatService avec contexte
            $response = $chatService->processQuestion($question, $user, $context);

            return $this->json([
                'success' => true,
                'context' => $context,
                'answer' => $response->answer,
                'metadata' => $response->metadata,
                'toolsUsed' => $response->toolsUsed,
                'durationMs' => $response->durationMs,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du traitement de la question',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Traiter une question en mode streaming (Mercure SSE) dans un contexte spécifique.
     *
     * TODO Sprint S1+: Implémenter streaming complet avec :
     * - Génération de chunks progressifs
     * - Publication via Mercure Hub
     * - Support des erreurs en cours de stream
     *
     * @param Request $request Requête HTTP contenant la question (JSON)
     * @param string  $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return JsonResponse Status de démarrage du streaming
     */
    #[Route('/{context}/stream', name: 'stream', methods: ['POST'])]
    public function streamMessage(Request $request, string $context): JsonResponse
    {
        // Validation du contexte
        if (! in_array($context, self::ALLOWED_CONTEXTS, true)) {
            return $this->json([
                'error' => 'Contexte invalide',
                'allowedContexts' => self::ALLOWED_CONTEXTS,
            ], Response::HTTP_BAD_REQUEST);
        }

        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';
        $conversationId = $data['conversationId'] ?? '';

        if (empty($question) || empty($conversationId)) {
            return $this->json(['error' => 'Question et conversationId requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            // Authentification via service centralisé
            $user = $this->authService->getAuthenticatedUser();
            if (null === $user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer le tenantId depuis le service CFI (contexte synchrone)
            $tenantId = $this->tenantService->getCurrentTenantOrNull();
            if (null === $tenantId) {
                return $this->json(['error' => 'Aucune division active'], Response::HTTP_FORBIDDEN);
            }

            // Récupérer le token CFI depuis la session (nécessaire pour authentification API en async)
            $cfiToken = $this->cfiSession->getToken();
            if (null === $cfiToken) {
                return $this->json(['error' => 'Session CFI expirée'], Response::HTTP_UNAUTHORIZED);
            }

            // Générer un messageId unique pour ce streaming
            $messageId = Uuid::v4()->toString();

            // Dispatcher le message de streaming de manière asynchrone
            // Le handler ChatStreamMessageHandler exécutera le streaming en arrière-plan
            $this->messageBus->dispatch(new ChatStreamMessage(
                question: $question,
                userId: (int) $user->getId(),
                tenantId: $tenantId,
                context: $context,
                conversationId: $conversationId,
                messageId: $messageId,
                cfiToken: $cfiToken,
            ));

            // Retourner immédiatement la réponse sans attendre le streaming
            return $this->json([
                'success' => true,
                'status' => 'streaming_started',
                'context' => $context,
                'messageId' => $messageId,
                'conversationId' => $conversationId,
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors du démarrage du streaming',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }
}
