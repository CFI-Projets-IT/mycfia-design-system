<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PersonaRepository;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;
use Gedmo\Mapping\Annotation as Gedmo;

/**
 * Persona marketing généré par IA.
 *
 * Représentation d'un client type avec caractéristiques démographiques,
 * comportementales et psychographiques pour ciblage marketing.
 */
#[ORM\Entity(repositoryClass: PersonaRepository::class)]
#[ORM\Table(name: 'marketing_persona')]
#[ORM\Index(name: 'idx_persona_project', columns: ['project_id'])]
class Persona
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Projet marketing auquel appartient ce persona.
     */
    #[ORM\ManyToOne(targetEntity: Project::class, inversedBy: 'personas')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Project $project;

    /**
     * Nom du persona (ex: "Sophie la Tech Enthusiast").
     */
    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * Description détaillée du persona (biographie, contexte, expérience).
     */
    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $description = null;

    /**
     * Âge du persona.
     */
    #[ORM\Column(type: Types::INTEGER)]
    private int $age;

    /**
     * Genre du persona.
     */
    #[ORM\Column(length: 50)]
    private string $gender;

    /**
     * Profession du persona.
     */
    #[ORM\Column(length: 255)]
    private string $job;

    /**
     * Données brutes complètes du persona (JSON).
     * Contient : demographics, behaviors, pain_points, goals, etc.
     * Structure enrichie du Marketing AI Bundle v3.3+.
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $rawData = null;

    /**
     * Score de qualité du persona (0 à 100).
     * Calculé par l'agent IA selon pertinence et cohérence.
     * Marketing AI Bundle v3.8.5+ : Score sur échelle 0-100.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 5, scale: 2, nullable: true)]
    private ?string $qualityScore = null;

    /**
     * Indique si ce persona est sélectionné pour la campagne marketing (v3.8.0).
     * Permet à l'utilisateur de choisir quels personas cibler dans la stratégie et les assets.
     * Par défaut false : tous les personas générés doivent être explicitement sélectionnés.
     */
    #[ORM\Column]
    private bool $selected = false;

    /**
     * Date de génération du persona.
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

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;

        return $this;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function setDescription(?string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getAge(): int
    {
        return $this->age;
    }

    public function setAge(int $age): self
    {
        $this->age = $age;

        return $this;
    }

    public function getGender(): string
    {
        return $this->gender;
    }

    public function setGender(string $gender): self
    {
        $this->gender = $gender;

        return $this;
    }

    public function getJob(): string
    {
        return $this->job;
    }

    public function setJob(string $job): self
    {
        $this->job = $job;

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

    public function getQualityScore(): ?string
    {
        return $this->qualityScore;
    }

    public function setQualityScore(?string $qualityScore): self
    {
        $this->qualityScore = $qualityScore;

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
