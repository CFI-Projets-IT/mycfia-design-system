<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompetitorRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Concurrent identifié par IA.
 *
 * Représentation d'un concurrent direct ou indirect pour analyse concurrentielle.
 * Chaque concurrent contient ses métadonnées de validation LLM et peut être sélectionné
 * par l'utilisateur pour inclusion dans la stratégie marketing.
 */
#[ORM\Entity(repositoryClass: CompetitorRepository::class)]
#[ORM\Table(name: 'marketing_competitor')]
#[ORM\Index(name: 'idx_competitor_project', columns: ['project_id'])]
#[ORM\Index(name: 'idx_competitor_selected', columns: ['selected'])]
class Competitor
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Projet marketing auquel appartient ce concurrent.
     */
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'competitors')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Domaine du concurrent (ex: "example.com").
     */
    #[ORM\Column(length: 255)]
    private string $domain;

    /**
     * Titre/nom du concurrent (ex: "Example Corp - Leader SaaS B2B").
     */
    #[ORM\Column(length: 255)]
    private string $title;

    /**
     * URL complète du concurrent (optionnel).
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    /**
     * Score d'alignement avec le projet (0-100).
     * Calculé par CompetitorIntelligenceTool via validation LLM.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $alignmentScore;

    /**
     * Raisonnement de la validation LLM (pourquoi ce concurrent est pertinent).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $reasoning = null;

    /**
     * Niveau de chevauchement d'offre (High, Medium, Low).
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $offeringOverlap = null;

    /**
     * Niveau de chevauchement de marché (Direct, Indirect, Adjacent).
     */
    #[ORM\Column(length: 50, nullable: true)]
    private ?string $marketOverlap = null;

    /**
     * Indique si le concurrent fait de la publicité en ligne (détecté via scraping).
     */
    #[ORM\Column]
    private bool $hasAds = false;

    /**
     * Données brutes complètes du concurrent (JSON).
     * Contient : firecrawl_extract, scraping_status, metadata, etc.
     * Structure enrichie du Marketing AI Bundle v3.22.0+.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawData = null;

    /**
     * Indique si ce concurrent est sélectionné pour l'analyse concurrentielle.
     * Permet à l'utilisateur de choisir quels concurrents analyser dans la stratégie et les assets.
     * Par défaut false : tous les concurrents détectés doivent être explicitement sélectionnés.
     */
    #[ORM\Column]
    private bool $selected = false;

    /**
     * Date de détection du concurrent.
     */
    #[Gedmo\Timestampable(on: 'create')]
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

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
        if (null === $project) {
            throw new \InvalidArgumentException('Project cannot be null');
        }

        $this->project = $project;

        return $this;
    }

    public function getDomain(): string
    {
        return $this->domain;
    }

    public function setDomain(string $domain): self
    {
        $this->domain = $domain;

        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): self
    {
        $this->url = $url;

        return $this;
    }

    public function getAlignmentScore(): int
    {
        return $this->alignmentScore;
    }

    public function setAlignmentScore(int $alignmentScore): self
    {
        $this->alignmentScore = $alignmentScore;

        return $this;
    }

    public function getReasoning(): ?string
    {
        return $this->reasoning;
    }

    public function setReasoning(?string $reasoning): self
    {
        $this->reasoning = $reasoning;

        return $this;
    }

    public function getOfferingOverlap(): ?string
    {
        return $this->offeringOverlap;
    }

    public function setOfferingOverlap(?string $offeringOverlap): self
    {
        $this->offeringOverlap = $offeringOverlap;

        return $this;
    }

    public function getMarketOverlap(): ?string
    {
        return $this->marketOverlap;
    }

    public function setMarketOverlap(?string $marketOverlap): self
    {
        $this->marketOverlap = $marketOverlap;

        return $this;
    }

    public function hasAds(): bool
    {
        return $this->hasAds;
    }

    public function setHasAds(bool $hasAds): self
    {
        $this->hasAds = $hasAds;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getRawData(): ?array
    {
        return $this->rawData;
    }

    /**
     * @param array<string, mixed>|null $rawData
     */
    public function setRawData(?array $rawData): self
    {
        $this->rawData = $rawData;

        return $this;
    }

    public function isSelected(): bool
    {
        return $this->selected;
    }

    public function setSelected(bool $selected): self
    {
        $this->selected = $selected;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
