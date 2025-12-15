<?php

declare(strict_types=1);

namespace App\Controller;

use App\Message\ChatStreamMessage;
use App\Security\UserAuthenticationService;
use App\Service\Cfi\CfiSessionService;
use App\Service\Cfi\CfiTenantService;
use App\Service\ChatHistoryService;
use App\Service\ChatPersistenceService;
use App\Service\ChatService;
use App\Service\MercureJwtGenerator;
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
        private readonly MercureJwtGenerator $mercureJwtGenerator,
        private readonly ChatPersistenceService $persistenceService,
        private readonly ChatHistoryService $historyService,
    ) {
    }

    /**
     * Démarrer une nouvelle conversation vide dans un contexte.
     *
     * Force la création d'une nouvelle conversation sans charger l'historique.
     * Utilisé via le bouton "Nouvelle conversation" dans la navigation.
     *
     * @param Request $request Requête HTTP pour accès session
     * @param string  $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return Response Template Twig chat/index.html.twig avec conversation vide
     */
    #[Route('/{context}/new', name: 'new_conversation', methods: ['GET'])]
    public function newConversation(Request $request, string $context): Response
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

        // Créer un nouvel UUID pour la conversation
        $sessionKey = sprintf('chat_conversation_id_%s', $context);
        $conversationId = Uuid::v4()->toString();
        $request->getSession()->set($sessionKey, $conversationId);

        // Générer un JWT Mercure pour autoriser l'abonnement au topic de cette conversation
        $mercureJwt = $this->mercureJwtGenerator->generateSubscriberToken([
            sprintf('chat/%s', $conversationId),
        ]);

        return $this->render('chat/index.html.twig', [
            'context' => $context,
            'conversationId' => $conversationId,
            'mercureUrl' => $this->mercurePublicUrl,
            'mercureJwt' => $mercureJwt,
        ]);
    }

    /**
     * Page principale du chat IA contextuel.
     *
     * Charge automatiquement la dernière conversation du contexte si elle existe,
     * sinon affiche une interface chat vide.
     *
     * @param Request $request Requête HTTP pour accès session
     * @param string  $context Contexte du chat (factures|commandes|stocks|general)
     *
     * @return Response Template Twig chat/index.html.twig ou redirection
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

        // Authentification
        $user = $this->authService->getAuthenticatedUser();
        if (null === $user) {
            $this->addFlash('error', 'Vous devez être connecté pour accéder au chat');

            return $this->redirectToRoute('app_login');
        }

        // Récupération du tenant
        $tenantId = $this->tenantService->getCurrentTenantOrNull();
        if (null === $tenantId) {
            $this->addFlash('error', 'Aucune division sélectionnée');

            return $this->redirectToRoute('division_select');
        }

        // Chercher la dernière conversation de ce contexte
        $latestConversation = $this->historyService->getLatestConversationByContext($user, $tenantId, $context);

        // Si une conversation existe, rediriger vers elle
        if (null !== $latestConversation) {
            return $this->redirectToRoute('chat_load_conversation', [
                'context' => $context,
                'conversationId' => $latestConversation->getId(),
            ]);
        }

        // Aucune conversation existante → afficher chat vide
        // Récupérer ou créer ID conversation depuis session (par contexte)
        $sessionKey = sprintf('chat_conversation_id_%s', $context);
        $conversationId = $request->getSession()->get($sessionKey);
        if (null === $conversationId) {
            $conversationId = Uuid::v4()->toString();
            $request->getSession()->set($sessionKey, $conversationId);
        }

        // Générer un JWT Mercure pour autoriser l'abonnement au topic de cette conversation
        $mercureJwt = $this->mercureJwtGenerator->generateSubscriberToken([
            sprintf('chat/%s', $conversationId),
        ]);

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

    /**
     * Charger une conversation existante depuis la BDD.
     *
     * Récupère une conversation avec tous ses messages et l'affiche dans le chat.
     * Utilisé lorsque l'utilisateur clique sur une conversation dans la sidebar.
     *
     * @param Request $request        Requête HTTP pour accès session
     * @param string  $context        Contexte du chat (factures|commandes|stocks|general)
     * @param int     $conversationId ID de la conversation en BDD
     *
     * @return Response Template Twig avec conversation chargée ou erreur 404
     */
    #[Route('/{context}/conversation/{conversationId}', name: 'load_conversation', methods: ['GET'])]
    public function loadConversation(Request $request, string $context, int $conversationId): Response
    {
        // Validation du contexte
        if (! in_array($context, self::ALLOWED_CONTEXTS, true)) {
            $this->addFlash('error', sprintf('Contexte "%s" invalide. Contextes autorisés : %s', $context, implode(', ', self::ALLOWED_CONTEXTS)));

            return $this->redirectToRoute('chat_index', ['context' => 'general']);
        }

        try {
            // Récupérer la conversation avec messages
            $conversation = $this->historyService->getConversationWithMessages($conversationId);

            if (null === $conversation) {
                $this->addFlash('error', sprintf('Conversation #%d introuvable', $conversationId));

                return $this->redirectToRoute('chat_index', ['context' => $context]);
            }

            // Vérifier que la conversation appartient bien à l'utilisateur authentifié
            $user = $this->authService->getAuthenticatedUser();
            if (null === $user) {
                $this->addFlash('error', 'Vous n\'avez pas accès à cette conversation');

                return $this->redirectToRoute('chat_index', ['context' => $context]);
            }

            // @phpstan-ignore method.nonObject (user est garanti non-null après la vérification early return ligne 355-359)
            if ($conversation->getUser()->getId() !== $user->getId()) {
                $this->addFlash('error', 'Vous n\'avez pas accès à cette conversation');

                return $this->redirectToRoute('chat_index', ['context' => $context]);
            }

            // Vérifier que le contexte correspond bien au contexte de la conversation
            if ($conversation->getContext() !== $context) {
                // Rediriger vers le bon contexte
                return $this->redirectToRoute('chat_load_conversation', [
                    'context' => $conversation->getContext(),
                    'conversationId' => $conversationId,
                ]);
            }

            // Générer un nouvel UUID pour cette session de conversation
            $sessionConversationId = $request->getSession()->get(sprintf('chat_conversation_id_%s', $context));
            if (null === $sessionConversationId) {
                $sessionConversationId = Uuid::v4()->toString();
                $request->getSession()->set(sprintf('chat_conversation_id_%s', $context), $sessionConversationId);
            }

            // Générer JWT Mercure
            $mercureJwt = $this->mercureJwtGenerator->generateSubscriberToken([
                sprintf('chat/%s', $sessionConversationId),
            ]);

            return $this->render('chat/index.html.twig', [
                'context' => $context,
                'conversationId' => $sessionConversationId,
                'mercureUrl' => $this->mercurePublicUrl,
                'mercureJwt' => $mercureJwt,
                'loadedConversation' => $conversation, // Conversation chargée depuis BDD
            ]);
        } catch (\Exception $e) {
            $this->addFlash('error', sprintf('Erreur lors du chargement de la conversation : %s', $e->getMessage()));

            return $this->redirectToRoute('chat_index', ['context' => $context]);
        }
    }

    /**
     * Toggle le statut favori d'une conversation (AJAX).
     *
     * Endpoint AJAX pour marquer/démarquer une conversation comme favorite.
     * Utilisé depuis la sidebar (icône étoile) et la page historique.
     *
     * @param int $id ID de la conversation
     *
     * @return JsonResponse Nouveau statut favori
     */
    #[Route('/conversation/{id}/favorite', name: 'toggle_favorite', methods: ['POST'])]
    public function toggleFavorite(int $id): JsonResponse
    {
        try {
            // Authentification
            $user = $this->authService->getAuthenticatedUser();
            if (null === $user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer la conversation
            $conversation = $this->historyService->getConversationWithMessages($id);
            if (null === $conversation) {
                return $this->json(['error' => 'Conversation introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que la conversation appartient à l'utilisateur
            // @phpstan-ignore method.nonObject (user est garanti non-null après la vérification early return)
            if ($conversation->getUser()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
            }

            // Toggle favori
            $newStatus = $this->persistenceService->toggleFavorite($conversation);

            return $this->json([
                'success' => true,
                'isFavorite' => $newStatus,
                'message' => $newStatus ? 'Conversation ajoutée aux favoris' : 'Conversation retirée des favoris',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la modification du statut favori',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Supprimer une conversation (AJAX).
     *
     * Endpoint AJAX pour supprimer définitivement une conversation et tous ses messages.
     * Utilisé depuis la sidebar et la page historique.
     *
     * @param int $id ID de la conversation
     *
     * @return JsonResponse Confirmation de suppression
     */
    #[Route('/conversation/{id}', name: 'delete_conversation', methods: ['DELETE'])]
    public function deleteConversation(int $id): JsonResponse
    {
        try {
            // Authentification
            $user = $this->authService->getAuthenticatedUser();
            if (null === $user) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Récupérer la conversation
            $conversation = $this->historyService->getConversationWithMessages($id);
            if (null === $conversation) {
                return $this->json(['error' => 'Conversation introuvable'], Response::HTTP_NOT_FOUND);
            }

            // Vérifier que la conversation appartient à l'utilisateur
            // @phpstan-ignore method.nonObject (user est garanti non-null après la vérification early return)
            if ($conversation->getUser()->getId() !== $user->getId()) {
                return $this->json(['error' => 'Accès non autorisé'], Response::HTTP_FORBIDDEN);
            }

            // Supprimer la conversation
            $this->persistenceService->deleteConversation($conversation);

            return $this->json([
                'success' => true,
                'message' => 'Conversation supprimée avec succès',
            ]);
        } catch (\Exception $e) {
            return $this->json([
                'error' => 'Erreur lors de la suppression de la conversation',
                'message' => $e->getMessage(),
            ], Response::HTTP_INTERNAL_SERVER_ERROR);
        }
    }

    /**
     * Endpoint Turbo Frame pour recharger dynamiquement une section de la sidebar.
     *
     * Retourne uniquement le HTML du composant ConversationSidebar entouré d'un turbo-frame.
     * Utilisé après toggle favori ou suppression pour rafraîchir la sidebar sans reload complet.
     *
     * @param string $section Section à recharger ('favorites' ou 'history')
     *
     * @return Response Fragment HTML Turbo Frame
     */
    #[Route('/sidebar/{section}', name: 'sidebar_frame', methods: ['GET'])]
    public function sidebarFrame(string $section): Response
    {
        // Valider la section
        if (! \in_array($section, ['favorites', 'history'], true)) {
            throw $this->createNotFoundException('Section invalide');
        }

        return $this->render('chat/sidebar_frame.html.twig', [
            'section' => $section,
        ]);
    }
}
