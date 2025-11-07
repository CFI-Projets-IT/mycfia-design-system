<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\AssetStatus;
use App\Repository\AssetRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Asset marketing généré par IA (Google Ads, Facebook Post, Email, etc.).
 *
 * Représente un contenu marketing prêt à l'emploi généré par un AssetBuilder.
 * Support de 8 types : GoogleAds, LinkedinPost, FacebookPost, InstagramPost,
 * Mail, BingAds, IAB Banner, Article SEO.
 */
#[ORM\Entity(repositoryClass: AssetRepository::class)]
#[ORM\Table(name: 'marketing_asset')]
#[ORM\Index(name: 'idx_asset_project', columns: ['project_id'])]
#[ORM\Index(name: 'idx_asset_status', columns: ['status'])]
#[ORM\Index(name: 'idx_asset_type', columns: ['asset_type'])]
class Asset
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Projet marketing auquel appartient cet asset.
     */
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'assets')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Type d'asset (google_ads, linkedin_post, facebook_post, etc.).
     */
    #[ORM\Column(length: 50)]
    private string $assetType;

    /**
     * Canal de diffusion (social, search, display, email).
     */
    #[ORM\Column(length: 50)]
    private string $channel;

    /**
     * Contenu principal de l'asset (JSON avec structure spécifique au type).
     *
     * Exemples de structures :
     * - GoogleAds : {headline, description, keywords, callToAction}
     * - FacebookPost : {text, hashtags, callToAction, imageDescription}
     * - Mail : {subject, preheader, body, callToAction}
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $content;

    /**
     * Variations générées de l'asset (JSON array).
     * Permet de proposer plusieurs versions pour A/B testing.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $variations = null;

    /**
     * Statut de validation de l'asset.
     */
    #[ORM\Column(type: 'string', enumType: AssetStatus::class)]
    private AssetStatus $status = AssetStatus::DRAFT;

    /**
     * Score de qualité de l'asset (0.0 à 1.0).
     * Calculé par l'agent IA selon pertinence, clarté, impact.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $qualityScore = null;

    /**
     * Notes et commentaires de l'utilisateur.
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    /**
     * Date de génération de l'asset.
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière modification.
     */
    #[Gedmo\Timestampable(on: 'update')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(?Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getAssetType(): string
    {
        return $this->assetType;
    }

    public function setAssetType(string $assetType): self
    {
        $this->assetType = $assetType;

        return $this;
    }

    public function getChannel(): string
    {
        return $this->channel;
    }

    public function setChannel(string $channel): self
    {
        $this->channel = $channel;

        return $this;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function setContent(string $content): self
    {
        $this->content = $content;

        return $this;
    }

    public function getVariations(): ?string
    {
        return $this->variations;
    }

    public function setVariations(?string $variations): self
    {
        $this->variations = $variations;

        return $this;
    }

    public function getStatus(): AssetStatus
    {
        return $this->status;
    }

    public function setStatus(AssetStatus $status): self
    {
        $this->status = $status;

        return $this;
    }

    public function getQualityScore(): ?string
    {
        return $this->qualityScore;
    }

    public function setQualityScore(?string $qualityScore): self
    {
        $this->qualityScore = $qualityScore;

        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;

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
