<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ProjectEnrichmentDraftRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Table intermédiaire pour stocker les données d'enrichissement en attente de validation.
 *
 * Permet de séparer les données générées par l'IA (non validées) des données projet validées.
 * L'utilisateur peut consulter, valider ou régénérer l'enrichissement via une page de révision.
 */
#[ORM\Entity(repositoryClass: ProjectEnrichmentDraftRepository::class)]
#[ORM\Table(name: 'project_enrichment_draft')]
#[ORM\Index(name: 'idx_enrichment_draft_task_id', columns: ['task_id'])]
#[ORM\Index(name: 'idx_enrichment_draft_status', columns: ['status'])]
#[ORM\Index(name: 'idx_enrichment_draft_created', columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class ProjectEnrichmentDraft
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Projet associé à ce draft d'enrichissement.
     */
    #[ORM\OneToOne(targetEntity: Project::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * ID de la tâche de génération (pour Mercure).
     */
    #[ORM\Column(type: Types::STRING, length: 36)]
    private string $taskId;

    /**
     * Données d'enrichissement générées par l'IA (JSON).
     *
     * Structure :
     * - description: string
     * - objectives: string
     * - productInfo: string
     * - brandVoice: string
     * - brandGuidelines: string
     * - brandKeywords: array
     * - keywords: array
     * - snippets: array
     * - examples: array
     * - caseStudies: array
     * - targetMetrics: array
     * - benchmarks: array
     *
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $enrichmentData = [];

    /**
     * Statut du draft (pending, validated, rejected, regenerated).
     */
    #[ORM\Column(type: Types::STRING, length: 20)]
    private string $status = 'pending';

    /**
     * Date de génération de l'enrichissement.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $enrichedAt;

    /**
     * Date de création du draft.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière mise à jour du draft.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function __construct()
    {
        $now = new \DateTimeImmutable();
        $this->enrichedAt = $now;
        $this->createdAt = $now;
        $this->updatedAt = $now;
    }

    #[ORM\PreUpdate]
    public function preUpdate(): void
    {
        $this->updatedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): static
    {
        $this->project = $project;

        return $this;
    }

    public function getTaskId(): string
    {
        return $this->taskId;
    }

    public function setTaskId(string $taskId): static
    {
        $this->taskId = $taskId;

        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getEnrichmentData(): array
    {
        return $this->enrichmentData;
    }

    /**
     * @param array<string, mixed> $enrichmentData
     */
    public function setEnrichmentData(array $enrichmentData): static
    {
        $this->enrichmentData = $enrichmentData;

        return $this;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): static
    {
        $this->status = $status;

        return $this;
    }

    public function getEnrichedAt(): \DateTimeImmutable
    {
        return $this->enrichedAt;
    }

    public function setEnrichedAt(\DateTimeImmutable $enrichedAt): static
    {
        $this->enrichedAt = $enrichedAt;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): \DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
