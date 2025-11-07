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
     * Centres d'intérêt (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $interests;

    /**
     * Comportements d'achat et habitudes (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $behaviors;

    /**
     * Motivations principales (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $motivations;

    /**
     * Points de douleur / frustrations (JSON généré par l'IA).
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $pains;

    /**
     * Score de qualité du persona (0.0 à 1.0).
     * Calculé par l'agent IA selon pertinence et cohérence.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 3, scale: 2, nullable: true)]
    private ?string $qualityScore = null;

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

    public function getInterests(): string
    {
        return $this->interests;
    }

    public function setInterests(string $interests): self
    {
        $this->interests = $interests;

        return $this;
    }

    public function getBehaviors(): string
    {
        return $this->behaviors;
    }

    public function setBehaviors(string $behaviors): self
    {
        $this->behaviors = $behaviors;

        return $this;
    }

    public function getMotivations(): string
    {
        return $this->motivations;
    }

    public function setMotivations(string $motivations): self
    {
        $this->motivations = $motivations;

        return $this;
    }

    public function getPains(): string
    {
        return $this->pains;
    }

    public function setPains(string $pains): self
    {
        $this->pains = $pains;

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
