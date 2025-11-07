<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\GoalType;
use App\Enum\ProjectStatus;
use App\Repository\ProjectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Projet de campagne marketing multi-canal avec génération IA.
 *
 * Point d'entrée du workflow Marketing AI Bundle.
 * Coordonne la génération de personas, stratégie, analyse concurrentielle et assets.
 */
#[ORM\Entity(repositoryClass: ProjectRepository::class)]
#[ORM\Table(name: 'marketing_project')]
#[ORM\Index(name: 'idx_project_user_tenant', columns: ['user_id', 'tenant_id'])]
#[ORM\Index(name: 'idx_project_status', columns: ['status'])]
#[ORM\Index(name: 'idx_project_created', columns: ['created_at'])]
#[ORM\HasLifecycleCallbacks]
class Project
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    /**
     * Utilisateur créateur du projet.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    /**
     * Tenant (division) associé au projet.
     * Permet l'isolation multi-tenant des données.
     */
    #[ORM\ManyToOne(targetEntity: Division::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private Division $tenant;

    /**
     * Nom du projet marketing.
     */
    #[ORM\Column(length: 255)]
    private string $name;

    /**
     * Description détaillée du projet.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $description;

    /**
     * Informations sur le produit/service à promouvoir.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $productInfo;

    /**
     * Type d'objectif marketing (notoriété, conversion, fidélisation).
     */
    #[ORM\Column(type: 'string', enumType: GoalType::class)]
    private GoalType $goalType;

    /**
     * Budget alloué au projet (en euros).
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 10, scale: 2)]
    private string $budget;

    /**
     * Nom de l'entreprise cliente.
     * Utilisé pour personnaliser les contenus générés et renforcer l'identité de marque.
     */
    #[ORM\Column(length: 255)]
    private string $companyName;

    /**
     * Secteur d'activité de l'entreprise.
     * Permet aux agents IA d'adapter vocabulaire, benchmarks et recommandations.
     * Valeurs : Tech B2B SaaS, E-commerce, Fintech, Healthcare, Retail, Education, Autre.
     */
    #[ORM\Column(length: 255)]
    private string $sector;

    /**
     * Objectifs marketing détaillés du projet.
     * Fournit le contexte précis aux agents IA pour des résultats alignés avec les attentes métier.
     */
    #[ORM\Column(type: Types::TEXT)]
    private string $detailedObjectives;

    /**
     * Date de début de la campagne marketing.
     * Permet de calculer la durée et adapter les stratégies.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $startDate;

    /**
     * Date de fin de la campagne marketing.
     * Permet de calculer la durée et adapter les stratégies.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $endDate;

    /**
     * URL du site web de l'entreprise (optionnel).
     * Permet l'analyse automatique de l'identité visuelle (couleurs, typographie, logo)
     * via FirecrawlClient pour des assets cohérents avec la charte graphique.
     */
    #[ORM\Column(length: 255, nullable: true)]
    private ?string $websiteUrl = null;

    /**
     * Statut actuel du projet dans le workflow de génération.
     */
    #[ORM\Column(type: 'string', enumType: ProjectStatus::class)]
    private ProjectStatus $status = ProjectStatus::DRAFT;

    /**
     * Date de création du projet.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $createdAt;

    /**
     * Date de dernière mise à jour.
     */
    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private \DateTimeImmutable $updatedAt;

    /**
     * Collection des personas générés pour ce projet.
     *
     * @var Collection<int, Persona>
     */
    #[ORM\OneToMany(
        targetEntity: Persona::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $personas;

    /**
     * Stratégies marketing générées pour ce projet.
     * Un projet peut avoir plusieurs stratégies (ex: stratégie Q1, Q2, test A/B).
     *
     * @var Collection<int, Strategy>
     */
    #[ORM\OneToMany(
        targetEntity: Strategy::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $strategies;

    /**
     * Analyse concurrentielle générée pour ce projet.
     */
    #[ORM\OneToOne(
        targetEntity: CompetitorAnalysis::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private ?CompetitorAnalysis $competitorAnalysis = null;

    /**
     * Collection des assets marketing générés.
     *
     * @var Collection<int, Asset>
     */
    #[ORM\OneToMany(
        targetEntity: Asset::class,
        mappedBy: 'project',
        cascade: ['persist', 'remove'],
        orphanRemoval: true
    )]
    private Collection $assets;

    public function __construct()
    {
        $this->personas = new ArrayCollection();
        $this->strategies = new ArrayCollection();
        $this->assets = new ArrayCollection();
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

    public function getUser(): User
    {
        return $this->user;
    }

    public function setUser(User $user): self
    {
        $this->user = $user;

        return $this;
    }

    public function getTenant(): Division
    {
        return $this->tenant;
    }

    public function setTenant(Division $tenant): self
    {
        $this->tenant = $tenant;

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

    public function getDescription(): string
    {
        return $this->description;
    }

    public function setDescription(string $description): self
    {
        $this->description = $description;

        return $this;
    }

    public function getProductInfo(): string
    {
        return $this->productInfo;
    }

    public function setProductInfo(string $productInfo): self
    {
        $this->productInfo = $productInfo;

        return $this;
    }

    public function getGoalType(): GoalType
    {
        return $this->goalType;
    }

    public function setGoalType(GoalType $goalType): self
    {
        $this->goalType = $goalType;

        return $this;
    }

    public function getBudget(): string
    {
        return $this->budget;
    }

    public function setBudget(string $budget): self
    {
        $this->budget = $budget;

        return $this;
    }

    public function getCompanyName(): string
    {
        return $this->companyName;
    }

    public function setCompanyName(string $companyName): self
    {
        $this->companyName = $companyName;

        return $this;
    }

    public function getSector(): string
    {
        return $this->sector;
    }

    public function setSector(string $sector): self
    {
        $this->sector = $sector;

        return $this;
    }

    public function getDetailedObjectives(): string
    {
        return $this->detailedObjectives;
    }

    public function setDetailedObjectives(string $detailedObjectives): self
    {
        $this->detailedObjectives = $detailedObjectives;

        return $this;
    }

    public function getStartDate(): \DateTimeImmutable
    {
        return $this->startDate;
    }

    public function setStartDate(\DateTimeImmutable $startDate): self
    {
        $this->startDate = $startDate;

        return $this;
    }

    public function getEndDate(): \DateTimeImmutable
    {
        return $this->endDate;
    }

    public function setEndDate(\DateTimeImmutable $endDate): self
    {
        $this->endDate = $endDate;

        return $this;
    }

    public function getWebsiteUrl(): ?string
    {
        return $this->websiteUrl;
    }

    public function setWebsiteUrl(?string $websiteUrl): self
    {
        $this->websiteUrl = $websiteUrl;

        return $this;
    }

    public function getStatus(): ProjectStatus
    {
        return $this->status;
    }

    public function setStatus(ProjectStatus $status): self
    {
        $this->status = $status;

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

    /**
     * @return Collection<int, Persona>
     */
    public function getPersonas(): Collection
    {
        return $this->personas;
    }

    public function addPersona(Persona $persona): self
    {
        if (! $this->personas->contains($persona)) {
            $this->personas->add($persona);
            $persona->setProject($this);
        }

        return $this;
    }

    public function removePersona(Persona $persona): self
    {
        if ($this->personas->removeElement($persona)) {
            if ($persona->getProject() === $this) {
                $persona->setProject(null);
            }
        }

        return $this;
    }

    /**
     * @return Collection<int, Strategy>
     */
    public function getStrategies(): Collection
    {
        return $this->strategies;
    }

    public function addStrategy(Strategy $strategy): self
    {
        if (! $this->strategies->contains($strategy)) {
            $this->strategies->add($strategy);
            $strategy->setProject($this);
        }

        return $this;
    }

    public function removeStrategy(Strategy $strategy): self
    {
        if ($this->strategies->removeElement($strategy)) {
            if ($strategy->getProject() === $this) {
                $strategy->setProject(null);
            }
        }

        return $this;
    }

    public function getCompetitorAnalysis(): ?CompetitorAnalysis
    {
        return $this->competitorAnalysis;
    }

    public function setCompetitorAnalysis(?CompetitorAnalysis $competitorAnalysis): self
    {
        // unset the owning side of the relation if necessary
        if (null === $competitorAnalysis && null !== $this->competitorAnalysis) {
            $this->competitorAnalysis->setProject(null);
        }

        // set the owning side of the relation if necessary
        if (null !== $competitorAnalysis && $competitorAnalysis->getProject() !== $this) {
            $competitorAnalysis->setProject($this);
        }

        $this->competitorAnalysis = $competitorAnalysis;

        return $this;
    }

    /**
     * @return Collection<int, Asset>
     */
    public function getAssets(): Collection
    {
        return $this->assets;
    }

    public function addAsset(Asset $asset): self
    {
        if (! $this->assets->contains($asset)) {
            $this->assets->add($asset);
            $asset->setProject($this);
        }

        return $this;
    }

    public function removeAsset(Asset $asset): self
    {
        if ($this->assets->removeElement($asset)) {
            if ($asset->getProject() === $this) {
                $asset->setProject(null);
            }
        }

        return $this;
    }
}
