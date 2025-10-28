<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ChatConversationRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Entité pour la persistance des conversations chat.
 *
 * Stocke les conversations de l'utilisateur avec l'IA conversationnelle
 * pour permettre l'historique, les favoris et la reprise de conversation.
 */
#[ORM\Entity(repositoryClass: ChatConversationRepository::class)]
#[ORM\Table(name: 'chat_conversation')]
#[ORM\Index(name: 'idx_chat_conv_user_tenant', columns: ['user_id', 'tenant_id'])]
#[ORM\Index(name: 'idx_chat_conv_created', columns: ['created_at'])]
#[ORM\Index(name: 'idx_chat_conv_favorite', columns: ['is_favorite'])]
#[ORM\Index(name: 'idx_chat_conv_context', columns: ['context'])]
#[ORM\HasLifecycleCallbacks]
class ChatConversation
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Utilisateur propriétaire de la conversation.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Tenant (division) associé à la conversation.
     * Permet l'isolation multi-tenant des données.
     */
    #[ORM\ManyToOne(targetEntity: Division::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Division $tenant;

    /**
     * Contexte de la conversation (factures|commandes|stocks|general).
     * Utilisé pour rediriger vers le bon chat lors de la reprise.
     */
    #[ORM\Column(length: 20)]
    private string $context;

    /**
     * Titre de la conversation (généré automatiquement depuis le 1er message user).
     */
    #[ORM\Column(length: 255)]
    private string $title;

    /**
     * Indicateur favori (extension hors CDC S3).
     * Permet à l'utilisateur de marquer ses conversations importantes.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isFavorite = false;

    /**
     * Date de création de la conversation.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière mise à jour (changée à chaque nouveau message).
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /**
     * Collection des messages de la conversation.
     *
     * @var Collection<int, ChatMessage>
     */
    #[ORM\OneToMany(
        targetEntity: ChatMessage::class,
        mappedBy: 'conversation',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    #[ORM\OrderBy(['createdAt' => 'ASC'])]
    private Collection $messages;

    public function __construct()
    {
        $this->messages = new ArrayCollection();
    }

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->updatedAt = new \DateTimeImmutable();
    }

    #[ORM\PreUpdate]
    public function setUpdatedAtValue(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): ?User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTenant(): ?Division
    {
        return $this->tenant;
    }

    public function setTenant(Division $tenant): self
    {
        $this->tenant = $tenant;

        return $this;
    }

    public function getContext(): ?string
    {
        return $this->context;
    }

    public function setContext(string $context): self
    {
        $this->context = $context;

        return $this;
    }

    public function getTitle(): ?string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function isFavorite(): bool
    {
        return $this->isFavorite;
    }

    public function setIsFavorite(bool $isFavorite): self
    {
        $this->isFavorite = $isFavorite;

        return $this;
    }

    public function getCreatedAt(): ?\DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?\DateTimeImmutable
    {
        return $this->updatedAt;
    }

    /**
     * @return Collection<int, ChatMessage>
     */
    public function getMessages(): Collection
    {
        return $this->messages;
    }

    public function addMessage(ChatMessage $message): self
    {
        if (! $this->messages->contains($message)) {
            $this->messages->add($message);
            $message->setConversation($this);
        }

        return $this;
    }

    public function removeMessage(ChatMessage $message): self
    {
        if ($this->messages->removeElement($message)) {
            if ($message->getConversation() === $this) {
                $message->setConversation(null);
            }
        }

        return $this;
    }
}
