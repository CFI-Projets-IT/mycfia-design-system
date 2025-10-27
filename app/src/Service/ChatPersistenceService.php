<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ChatConversation;
use App\Entity\ChatMessage;
use App\Entity\Division;
use App\Entity\User;
use App\Repository\ChatConversationRepository;
use App\Repository\ChatMessageRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Service de persistance des conversations et messages chat.
 *
 * Gère l'enregistrement automatique des conversations et messages
 * dans le cadre de l'interaction avec l'IA conversationnelle.
 *
 * Fonctionnalités :
 * - Création de nouvelles conversations avec contexte multi-tenant
 * - Enregistrement des messages (user/assistant) avec données structurées
 * - Mise à jour du titre de conversation (généré depuis le 1er message)
 * - Gestion transactionnelle pour garantir la consistance des données
 */
class ChatPersistenceService
{
    /**
     * Contextes de conversation autorisés.
     *
     * @var array<string>
     */
    private const ALLOWED_CONTEXTS = ['factures', 'commandes', 'stocks', 'general'];

    /**
     * Rôles de message autorisés.
     *
     * @var array<string>
     */
    private const ALLOWED_ROLES = ['user', 'assistant'];

    public function __construct(
        private readonly ChatConversationRepository $conversationRepository,
        private readonly ChatMessageRepository $messageRepository,
        private readonly EntityManagerInterface $entityManager,
    ) {
    }

    /**
     * Crée une nouvelle conversation chat.
     *
     * @param User     $user    Utilisateur propriétaire
     * @param Division $tenant  Division (isolation multi-tenant)
     * @param string   $context Contexte de la conversation (factures|commandes|stocks|general)
     * @param string   $title   Titre de la conversation (généré depuis le 1er message user)
     *
     * @return ChatConversation Conversation créée et persistée
     *
     * @throws \InvalidArgumentException Si le contexte n'est pas valide
     */
    public function createConversation(User $user, Division $tenant, string $context, string $title): ChatConversation
    {
        // Validation du contexte
        if (! in_array($context, self::ALLOWED_CONTEXTS, true)) {
            throw new \InvalidArgumentException(sprintf('Le contexte "%s" n\'est pas valide. Contextes autorisés : %s', $context, implode(', ', self::ALLOWED_CONTEXTS)));
        }

        // Validation du titre
        if (empty(trim($title))) {
            throw new \InvalidArgumentException('Le titre de la conversation ne peut pas être vide');
        }

        // Création de la conversation
        $conversation = new ChatConversation();
        $conversation->setUser($user);
        $conversation->setTenant($tenant);
        $conversation->setContext($context);
        $conversation->setTitle($title);
        $conversation->setIsFavorite(false);

        // Persistance
        $this->conversationRepository->save($conversation, flush: true);

        return $conversation;
    }

    /**
     * Enregistre un message dans une conversation.
     *
     * @param ChatConversation          $conversation Conversation parente
     * @param string                    $role         Rôle de l'émetteur (user|assistant)
     * @param string                    $content      Contenu textuel du message
     * @param string|null               $type         Type de message (text|table|card), défaut: 'text'
     * @param array<string, mixed>|null $data         Données structurées JSON (ex: table_data pour DataTables)
     *
     * @return ChatMessage Message créé et persisté
     *
     * @throws \InvalidArgumentException Si le rôle n'est pas valide ou le contenu vide
     */
    public function saveMessage(
        ChatConversation $conversation,
        string $role,
        string $content,
        ?string $type = 'text',
        ?array $data = null
    ): ChatMessage {
        // Validation du rôle
        if (! in_array($role, self::ALLOWED_ROLES, true)) {
            throw new \InvalidArgumentException(sprintf('Le rôle "%s" n\'est pas valide. Rôles autorisés : %s', $role, implode(', ', self::ALLOWED_ROLES)));
        }

        // Validation du contenu
        if (empty(trim($content))) {
            throw new \InvalidArgumentException('Le contenu du message ne peut pas être vide');
        }

        // Utilisation de transaction pour garantir consistance
        $this->entityManager->beginTransaction();

        try {
            // Création du message
            $message = new ChatMessage();
            $message->setConversation($conversation);
            $message->setRole($role);
            $message->setContent($content);
            $message->setType($type ?? 'text');
            $message->setData($data);

            // Persistance du message
            $this->messageRepository->save($message, flush: false);

            // Mise à jour de la conversation (updatedAt via @PreUpdate)
            $conversation->addMessage($message);
            $this->conversationRepository->save($conversation, flush: false);

            // Flush et commit
            $this->entityManager->flush();
            $this->entityManager->commit();

            return $message;
        } catch (\Throwable $e) {
            $this->entityManager->rollback();

            throw new \RuntimeException(sprintf('Erreur lors de l\'enregistrement du message : %s', $e->getMessage()), 0, $e);
        }
    }

    /**
     * Met à jour le titre d'une conversation.
     *
     * Utilisé pour générer un titre depuis le 1er message utilisateur
     * ou pour permettre à l'utilisateur de renommer la conversation.
     *
     * @param ChatConversation $conversation Conversation à mettre à jour
     * @param string           $newTitle     Nouveau titre
     *
     * @throws \InvalidArgumentException Si le nouveau titre est vide
     */
    public function updateConversationTitle(ChatConversation $conversation, string $newTitle): void
    {
        // Validation du nouveau titre
        if (empty(trim($newTitle))) {
            throw new \InvalidArgumentException('Le nouveau titre ne peut pas être vide');
        }

        // Mise à jour du titre
        $conversation->setTitle($newTitle);

        // Persistance
        $this->conversationRepository->save($conversation, flush: true);
    }

    /**
     * Toggle le statut favori d'une conversation.
     *
     * @param ChatConversation $conversation Conversation à modifier
     *
     * @return bool Nouveau statut favori après toggle
     */
    public function toggleFavorite(ChatConversation $conversation): bool
    {
        $newStatus = ! $conversation->isFavorite();
        $conversation->setIsFavorite($newStatus);

        $this->conversationRepository->save($conversation, flush: true);

        return $newStatus;
    }

    /**
     * Supprime une conversation et tous ses messages.
     *
     * @param ChatConversation $conversation Conversation à supprimer
     */
    public function deleteConversation(ChatConversation $conversation): void
    {
        $this->conversationRepository->remove($conversation, flush: true);
    }
}
