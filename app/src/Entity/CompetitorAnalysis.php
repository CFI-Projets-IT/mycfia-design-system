<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CompetitorAnalysisRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Analyse concurrentielle générée par IA.
 *
 * Contient l'analyse des concurrents, leurs forces/faiblesses,
 * positionnement marché et opportunités de différenciation.
 */
#[ORM\Entity(repositoryClass: CompetitorAnalysisRepository::class)]
#[ORM\Table(name: 'marketing_competitor_analysis')]
#[ORM\Index(name: 'idx_competitor_project', columns: ['project_id'])]
#[ORM\HasLifecycleCallbacks]
class CompetitorAnalysis
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Projet marketing auquel appartient cette analyse.
     */
    #[ORM\OneToOne(targetEntity: Project::class, inversedBy: 'competitorAnalysis')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Liste des concurrents principaux identifiés (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $competitors;

    /**
     * Forces des concurrents (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $strengths;

    /**
     * Faiblesses des concurrents (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $weaknesses;

    /**
     * Positionnement marché des concurrents (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $marketPositioning;

    /**
     * Opportunités de différenciation identifiées (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $differentiationOpportunities;

    /**
     * Analyse des stratégies marketing concurrentes (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $marketingStrategies;

    /**
     * Date de génération de l'analyse.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    #[ORM\PrePersist]
    public function setCreatedAtValue(): void
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProject(): Project
    {
        return $this->project;
    }

    public function setProject(Project $project): self
    {
        $this->project = $project;

        return $this;
    }

    public function getCompetitors(): string
    {
        return $this->competitors;
    }

    /**
     * Retourne la liste des concurrents sous forme de tableau.
     *
     * Depuis v3.27.0 : retourne les objets complets avec toutes les métadonnées.
     * Pour l'affichage simple, utiliser getCompetitorsNames().
     *
     * @return array<int, array<string, mixed>>
     */
    public function getCompetitorsArray(): array
    {
        try {
            $decoded = json_decode($this->competitors, true, 512, JSON_THROW_ON_ERROR);

            return is_array($decoded) ? $decoded : [];
        } catch (\JsonException $e) {
            return [];
        }
    }

    /**
     * Retourne uniquement les noms des concurrents pour l'affichage.
     *
     * Extrait le titre ou le domaine de chaque concurrent.
     * Utilisé pour la compatibilité d'affichage dans les templates.
     *
     * @return array<int, string>
     */
    public function getCompetitorsNames(): array
    {
        $competitors = $this->getCompetitorsArray();
        $names = [];

        foreach ($competitors as $competitor) {
            // Nouveau format v3.27.0 : objet avec métadonnées
            if (isset($competitor['title'])) {
                $names[] = $competitor['title'] ?: ($competitor['domain'] ?? 'N/A');
            } elseif (isset($competitor['domain'])) {
                $names[] = $competitor['domain'];
            } else {
                // Fallback pour format inconnu
                $names[] = 'N/A';
            }
        }

        return $names;
    }

    public function setCompetitors(string $competitors): self
    {
        $this->competitors = $competitors;

        return $this;
    }

    public function getStrengths(): string
    {
        return $this->strengths;
    }

    public function setStrengths(string $strengths): self
    {
        $this->strengths = $strengths;

        return $this;
    }

    public function getWeaknesses(): string
    {
        return $this->weaknesses;
    }

    public function setWeaknesses(string $weaknesses): self
    {
        $this->weaknesses = $weaknesses;

        return $this;
    }

    public function getMarketPositioning(): string
    {
        return $this->marketPositioning;
    }

    public function setMarketPositioning(string $marketPositioning): self
    {
        $this->marketPositioning = $marketPositioning;

        return $this;
    }

    public function getDifferentiationOpportunities(): string
    {
        return $this->differentiationOpportunities;
    }

    public function setDifferentiationOpportunities(string $differentiationOpportunities): self
    {
        $this->differentiationOpportunities = $differentiationOpportunities;

        return $this;
    }

    public function getMarketingStrategies(): string
    {
        return $this->marketingStrategies;
    }

    public function setMarketingStrategies(string $marketingStrategies): self
    {
        $this->marketingStrategies = $marketingStrategies;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
