<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\ChatService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Uid\Uuid;

/**
 * Contrôleur pour l'interface de chat IA conversationnel.
 *
 * Responsabilités :
 * - Affichage de l'interface chat (template Twig)
 * - Traitement des questions utilisateur via ChatService
 * - Support streaming Mercure (SSE) pour réponses en temps réel
 * - Gestion des conversations (session-based)
 *
 * Architecture :
 * - Route GET /chat : Affichage interface
 * - Route POST /chat/message : Question simple (sync)
 * - Route POST /chat/stream : Question streaming (async Mercure)
 */
#[Route('/chat', name: 'chat_')]
final class ChatController extends AbstractController
{
    /**
     * Page principale du chat IA.
     *
     * Initialise une conversation (UUID v4) si non existante dans la session.
     * Affiche l'interface chat avec historique éventuel.
     *
     * @param Request $request Requête HTTP pour accès session
     *
     * @return Response Template Twig chat/index.html.twig
     */
    #[Route('/', name: 'index', methods: ['GET'])]
    public function index(Request $request): Response
    {
        // Récupérer ou créer ID conversation depuis session
        $conversationId = $request->getSession()->get('chat_conversation_id');
        if (null === $conversationId) {
            $conversationId = Uuid::v4()->toString();
            $request->getSession()->set('chat_conversation_id', $conversationId);
        }

        return $this->render('chat/index.html.twig', [
            'conversationId' => $conversationId,
            // TODO Sprint S1+: Ajouter URL Mercure depuis configuration
            'mercureUrl' => $this->getParameter('mercure.default_hub') ?? '',
        ]);
    }

    /**
     * Traiter une question utilisateur (mode synchrone).
     *
     * Endpoint pour questions simples sans streaming.
     * Retourne la réponse complète de l'agent IA.
     *
     * @param Request     $request     Requête HTTP contenant la question (JSON)
     * @param ChatService $chatService Service orchestrateur chat
     *
     * @return JsonResponse Réponse structurée de l'agent
     */
    #[Route('/message', name: 'message', methods: ['POST'])]
    public function message(Request $request, ChatService $chatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';

        if (empty($question)) {
            return $this->json(['error' => 'La question ne peut pas être vide'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->getUser();
            if (null === $user || ! $user instanceof User) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Traiter la question via ChatService
            $response = $chatService->processQuestion($question, $user);

            return $this->json([
                'success' => true,
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
     * Traiter une question en mode streaming (Mercure SSE).
     *
     * TODO Sprint S1+: Implémenter streaming complet avec :
     * - Génération de chunks progressifs
     * - Publication via Mercure Hub
     * - Support des erreurs en cours de stream
     *
     * @param Request     $request     Requête HTTP contenant la question (JSON)
     * @param ChatService $chatService Service orchestrateur chat
     *
     * @return JsonResponse Status de démarrage du streaming
     */
    #[Route('/stream', name: 'stream', methods: ['POST'])]
    public function streamMessage(Request $request, ChatService $chatService): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $question = $data['question'] ?? '';
        $conversationId = $data['conversationId'] ?? '';

        if (empty($question) || empty($conversationId)) {
            return $this->json(['error' => 'Question et conversationId requis'], Response::HTTP_BAD_REQUEST);
        }

        try {
            $user = $this->getUser();
            if (null === $user || ! $user instanceof User) {
                return $this->json(['error' => 'Utilisateur non authentifié'], Response::HTTP_UNAUTHORIZED);
            }

            // Démarrer le streaming (retourne message_id pour suivi)
            $messageId = $chatService->streamQuestion($question, $user);

            return $this->json([
                'success' => true,
                'status' => 'streaming_started',
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
