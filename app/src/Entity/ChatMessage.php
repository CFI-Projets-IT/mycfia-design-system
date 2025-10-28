<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChatMessageRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Entité pour la persistance des messages chat.
 *
 * Stocke chaque message échangé entre l'utilisateur et l'IA conversationnelle,
 * incluant les données structurées (table_data) pour les réponses avec tableaux.
 */
#[ORM\Entity(repositoryClass: ChatMessageRepository::class)]
#[ORM\Table(name: 'chat_message')]
#[ORM\Index(name: 'idx_chat_msg_conversation', columns: ['conversation_id'])]
#[ORM\Index(name: 'idx_chat_msg_created', columns: ['created_at'])]
class ChatMessage
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Conversation parente.
     */
    #[ORM\ManyToOne(targetEntity: ChatConversation::class, inversedBy: 'messages')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ChatConversation $conversation;

    /**
     * Rôle de l'émetteur du message (user ou assistant).
     */
    #[ORM\Column(length: 20)]
    #[Assert\NotBlank]
    #[Assert\Choice(choices: ['user', 'assistant'], message: 'Le rôle doit être "user" ou "assistant"')]
    private string $role;

    /**
     * Contenu textuel du message.
     */
    #[ORM\Column(type: Types::TEXT)]
    #[Assert\NotBlank]
    private string $content;

    /**
     * Type de message (text, table, card).
     * Permet de différencier les messages textuels des réponses structurées.
     */
    #[ORM\Column(length: 20, nullable: true)]
    private ?string $type = 'text';

    /**
     * Données structurées JSON pour les réponses complexes.
     *
     * Stocke notamment :
     * - table_data : Pour les DataTables (headers, rows, totalRow, linkColumns)
     * - metadata : Métadonnées diverses de la réponse IA
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $data = null;

    /**
     * Date de création du message.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getConversation(): ?ChatConversation
    {
        return $this->conversation;
    }

    public function setConversation(?ChatConversation $conversation): self
    {
        $this->conversation = $conversation;

        return $this;
    }

    public function getRole(): ?string
    {
        return $this->role;
    }

    public function setRole(string $role): self
    {
        $this->role = $role;

        return $this;
    }

    public function getContent(): ?string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getType(): ?string
    {
        return $this->type;
    }

    public function setType(?string $type): self
    {
        $this->type = $type;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getData(): ?array
    {
        return $this->data;
    }

    /**
     * @param array<string, mixed>|null $data
     */
    public function setData(?array $data): self
    {
        $this->data = $data;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }
}
