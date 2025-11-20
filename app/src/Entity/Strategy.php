<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\StrategyRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Stratégie marketing générée par IA.
 *
 * Contient la stratégie complète pour le projet marketing :
 * positionnement, messages clés, canaux recommandés, planning.
 */
#[ORM\Entity(repositoryClass: StrategyRepository::class)]
#[ORM\Table(name: 'marketing_strategy')]
#[ORM\Index(name: 'idx_strategy_project', columns: ['project_id'])]
class Strategy
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Projet marketing auquel appartient cette stratégie.
     */
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'strategies')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Positionnement stratégique (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $positioning;

    /**
     * Messages clés de la campagne (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $keyMessages;

    /**
     * Canaux de communication recommandés avec priorité (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $recommendedChannels;

    /**
     * Planning de diffusion recommandé (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $timeline;

    /**
     * Recommandations budgétaires par canal (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $budgetAllocation;

    /**
     * KPIs suggérés pour mesurer le succès (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $kpis;

    /**
     * Score de qualité/confiance de la stratégie (0.0 à 1.0).
     * Calculé par l'agent IA selon pertinence et faisabilité.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $qualityScore = null;

    /**
     * Date de génération de la stratégie.
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
        $this->project = $project;

        return $this;
    }

    public function getPositioning(): string
    {
        return $this->positioning;
    }

    public function setPositioning(string $positioning): self
    {
        $this->positioning = $positioning;

        return $this;
    }

    public function getKeyMessages(): string
    {
        return $this->keyMessages;
    }

    public function setKeyMessages(string $keyMessages): self
    {
        $this->keyMessages = $keyMessages;

        return $this;
    }

    public function getRecommendedChannels(): string
    {
        return $this->recommendedChannels;
    }

    public function setRecommendedChannels(string $recommendedChannels): self
    {
        $this->recommendedChannels = $recommendedChannels;

        return $this;
    }

    public function getTimeline(): string
    {
        return $this->timeline;
    }

    public function setTimeline(string $timeline): self
    {
        $this->timeline = $timeline;

        return $this;
    }

    public function getBudgetAllocation(): string
    {
        return $this->budgetAllocation;
    }

    /**
     * Retourne les données d'allocation budgétaire décodées (v3.29.0).
     *
     * @return array<string, mixed>|null
     */
    public function getBudgetAllocationData(): ?array
    {
        if (empty($this->budgetAllocation)) {
            return null;
        }

        $data = json_decode($this->budgetAllocation, true);

        return is_array($data) ? $data : null;
    }

    public function setBudgetAllocation(string $budgetAllocation): self
    {
        $this->budgetAllocation = $budgetAllocation;

        return $this;
    }

    public function getKpis(): string
    {
        return $this->kpis;
    }

    public function setKpis(string $kpis): self
    {
        $this->kpis = $kpis;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
