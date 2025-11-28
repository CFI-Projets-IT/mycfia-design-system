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
#[ORM\Index(name: 'idx_project_language', columns: ['language'])]
#[ORM\Index(name: 'idx_project_keywords_volume', columns: ['keywords_avg_volume'])]
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
     * Types d'assets marketing sélectionnés par l'utilisateur.
     * Liste des canaux marketing pour lesquels générer des assets.
     * Exemples: ['linkedin_post', 'google_ads', 'facebook_post', 'instagram_post', 'mail', 'bing_ads', 'iab', 'article']
     * Permet d'optimiser le contexte des agents IA dès l'étape 1 (enrichissement).
     *
     * @var array<string>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $selectedAssetTypes = null;

    // ========================================
    // COLONNES REQUÊTABLES - Enrichissement v3.19.0+
    // ========================================

    /**
     * Langue détectée du site web (ISO 639-1).
     * Permet filtrage projets par langue (ex: 'fr', 'en', 'es').
     */
    #[ORM\Column(length: 10, nullable: true)]
    private ?string $language = null;

    /**
     * Couleur principale de la marque (hex).
     * Permet filtrage/recherche par couleur dominante pour cohérence visuelle.
     */
    #[ORM\Column(length: 7, nullable: true)]
    private ?string $brandPrimaryColor = null;

    /**
     * Indique si un screenshot du site est disponible.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasScreenshot = false;

    /**
     * Indique si les données branding Firecrawl sont disponibles.
     */
    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $hasBranding = false;

    /**
     * Volume de recherche mensuel moyen des keywords Google Ads.
     * Permet trier projets par potentiel SEO.
     */
    #[ORM\Column(type: Types::INTEGER, nullable: true)]
    private ?int $keywordsAvgVolume = null;

    /**
     * Coût par clic moyen des keywords Google Ads (en euros).
     * Permet estimer budget Google Ads minimum.
     */
    #[ORM\Column(type: Types::DECIMAL, precision: 6, scale: 2, nullable: true)]
    private ?string $keywordsAvgCpc = null;

    // ========================================
    // CHAMPS JSON STRUCTURÉS - Enrichissement v3.19.0+
    // ========================================

    /**
     * Identité visuelle complète de la marque (Bundle v3.19.0+).
     * Contient couleurs, typographie, personality, assets visuels.
     * Utilisé pour génération assets avec cohérence visuelle.
     *
     * Structure :
     * - brand_name : Nom de la marque
     * - url : URL analysée
     * - colorScheme : 'light'|'dark'
     * - colors : {primary, accent, background, textPrimary, link}
     * - fonts : [{family, role}]
     * - typography : {fontFamilies, fontSizes, fontStack}
     * - personality : {tone, energy, targetAudience}
     * - images : {logo, favicon}
     * - confidence : {colors, overall}
     * - analyzed_at : Date analyse
     * - analysis_quality_score : Score qualité 0-1
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $brandIdentity = null;

    /**
     * Intelligence business extraite du site web (Bundle v3.19.0+).
     * Combine extraction Firecrawl structurée + analyse contexte LLM.
     * Utilisé pour affichage enrichi et pré-remplissage assets.
     *
     * Structure :
     * EXTRACTION FIRECRAWL (8 champs) :
     * - mainOffering : Offre principale (produit/service/programme)
     * - targetMarket : Marché/audience cible
     * - geographicMarket : Zone géographique
     * - keyFeatures : Caractéristiques clés [array]
     * - pricingInformation : Info pricing si disponible
     * - timelineAvailability : Durée/disponibilité si mentionné
     * - valuePropositions : Propositions de valeur [array]
     * - callToAction : CTA ou méthode contact
     *
     * ANALYSE CONTEXTE LLM (5 champs) :
     * - sector : Secteur activité détecté
     * - targetAudience : Audience cible (B2B/B2C/SMB/Enterprise)
     * - businessModel : Modèle économique (SaaS/E-commerce/Services)
     * - geography : Zone géographique principale
     * - confidenceLevel : Niveau confiance analyse (high|medium|low)
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $businessIntelligence = null;

    /**
     * Keywords Google Ads avec métriques (Bundle v3.19.0+).
     * Top 50 keywords + métriques agrégées sur les 100 récupérés.
     * Utilisé pour budget Ads, SEO, analyse concurrence, suggestions contenu.
     *
     * Structure :
     * - keywords : Top 50 [{keyword, volume, competition, cpc}]
     * - metrics : {
     *     total_keywords, avg_volume, avg_cpc,
     *     high_competition_count, total_search_volume,
     *     high_competition_percentage
     *   }
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $keywordsData = null;

    /**
     * Suggestions IA générées lors de l'enrichissement (Bundle v3.19.0+).
     * Contient noms créatifs, objectifs SMART, recommandations stratégiques.
     * Utilisé pour suggestions utilisateur et aide à la décision.
     *
     * Structure :
     * - creative_name_alternatives : [3 noms campagne créatifs]
     * - smart_objectives_detailed : Objectifs reformulés SMART
     * - strategic_recommendations : [3-5 actions stratégiques]
     * - success_factors : [3-5 KPIs à surveiller]
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $aiEnrichment = null;

    /**
     * Contenu brut scrappé du site web (Bundle v3.10.0+).
     * Données source pour debug et ré-analyse si besoin.
     *
     * Structure :
     * - metadata : {title, description, keywords, ogImage, favicon}
     * - markdown : Contenu complet markdown (peut être volumineux)
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $scrapedContent = null;

    /**
     * URL du screenshot du site web (Bundle v3.19.0+).
     * Référence visuelle stockée en filesystem/S3, extraction couleurs fallback.
     * Scalable pour applications commerciales (évite blob en DB).
     */
    #[ORM\Column(type: Types::STRING, length: 500, nullable: true)]
    private ?string $screenshotUrl = null;

    /**
     * Métriques techniques de l'enrichissement IA.
     * Utilisé pour dashboard admin et optimisation coûts LLM.
     *
     * Structure :
     * - tokens_used : {input, output, total}
     * - cost : Coût en euros
     * - duration_ms : Temps exécution
     * - model_used : Modèle LLM utilisé
     * - retry_attempts : Nombre tentatives
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $enrichmentMetrics = null;

    /**
     * Allocation budgétaire optimisée par canal (Bundle v3.29.0).
     * Calculée par BudgetOptimizerTool avec benchmarks sectoriels.
     *
     * Structure :
     * - allocation : {channel => {budget, cpl, expected_leads, category}}
     * - total_budget : Budget total en euros
     * - total_expected_leads : Nombre total de leads attendus
     * - confidence_score : Score de confiance 0-100
     * - regulated : Secteur régulé (bool)
     * - recommendations : Recommandations budgétaires [array]
     *
     * @var array<string, mixed>|null
     */
    #[ORM\Column(type: Types::JSON, nullable: true)]
    private ?array $budgetAllocation = null;

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

    /**
     * Synchronise automatiquement les colonnes SQL dénormalisées avec les données JSON.
     * Évite désynchronisation entre colonnes requêtables et champs JSON source.
     * Appelé automatiquement avant persist/update par Doctrine.
     */
    #[ORM\PrePersist]
    #[ORM\PreUpdate]
    public function syncDenormalizedColumns(): void
    {
        // Sync keywords metrics (keywordsData JSON → colonnes SQL)
        if (null !== $this->keywordsData && isset($this->keywordsData['metrics'])) {
            $metrics = $this->keywordsData['metrics'];
            $this->keywordsAvgVolume = isset($metrics['avg_volume']) ? (int) $metrics['avg_volume'] : null;
            $this->keywordsAvgCpc = isset($metrics['avg_cpc']) ? (string) $metrics['avg_cpc'] : null;
        }

        // Sync brand primary color (brandIdentity JSON → colonne SQL)
        if (null !== $this->brandIdentity && isset($this->brandIdentity['colors']['primary'])) {
            $this->brandPrimaryColor = $this->brandIdentity['colors']['primary'];
        }

        // Sync language (scrapedContent JSON → colonne SQL)
        if (null !== $this->scrapedContent && isset($this->scrapedContent['metadata']['language'])) {
            $this->language = $this->scrapedContent['metadata']['language'];
        }

        // Sync boolean flags
        $this->hasScreenshot = null !== $this->screenshotUrl;
        $this->hasBranding = null !== $this->brandIdentity
            && isset($this->brandIdentity['colors'])
            && isset($this->brandIdentity['fonts']);
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

    /**
     * @return array<string>|null
     */
    public function getSelectedAssetTypes(): ?array
    {
        return $this->selectedAssetTypes;
    }

    /**
     * @param array<string>|null $selectedAssetTypes
     */
    public function setSelectedAssetTypes(?array $selectedAssetTypes): self
    {
        $this->selectedAssetTypes = $selectedAssetTypes;

        return $this;
    }

    // ========================================
    // GETTERS/SETTERS - COLONNES REQUÊTABLES
    // ========================================

    public function getLanguage(): ?string
    {
        return $this->language;
    }

    public function setLanguage(?string $language): self
    {
        $this->language = $language;

        return $this;
    }

    public function getBrandPrimaryColor(): ?string
    {
        return $this->brandPrimaryColor;
    }

    public function setBrandPrimaryColor(?string $brandPrimaryColor): self
    {
        $this->brandPrimaryColor = $brandPrimaryColor;

        return $this;
    }

    public function isHasScreenshot(): bool
    {
        return $this->hasScreenshot;
    }

    public function setHasScreenshot(bool $hasScreenshot): self
    {
        $this->hasScreenshot = $hasScreenshot;

        return $this;
    }

    public function isHasBranding(): bool
    {
        return $this->hasBranding;
    }

    public function setHasBranding(bool $hasBranding): self
    {
        $this->hasBranding = $hasBranding;

        return $this;
    }

    public function getKeywordsAvgVolume(): ?int
    {
        return $this->keywordsAvgVolume;
    }

    public function setKeywordsAvgVolume(?int $keywordsAvgVolume): self
    {
        $this->keywordsAvgVolume = $keywordsAvgVolume;

        return $this;
    }

    public function getKeywordsAvgCpc(): ?string
    {
        return $this->keywordsAvgCpc;
    }

    public function setKeywordsAvgCpc(?string $keywordsAvgCpc): self
    {
        $this->keywordsAvgCpc = $keywordsAvgCpc;

        return $this;
    }

    // ========================================
    // GETTERS/SETTERS - CHAMPS JSON
    // ========================================

    /**
     * @return array<string, mixed>|null
     */
    public function getBrandIdentity(): ?array
    {
        return $this->brandIdentity;
    }

    /**
     * @param array<string, mixed>|null $brandIdentity
     */
    public function setBrandIdentity(?array $brandIdentity): self
    {
        $this->brandIdentity = $brandIdentity;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBusinessIntelligence(): ?array
    {
        return $this->businessIntelligence;
    }

    /**
     * @param array<string, mixed>|null $businessIntelligence
     */
    public function setBusinessIntelligence(?array $businessIntelligence): self
    {
        $this->businessIntelligence = $businessIntelligence;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getKeywordsData(): ?array
    {
        return $this->keywordsData;
    }

    /**
     * @param array<string, mixed>|null $keywordsData
     */
    public function setKeywordsData(?array $keywordsData): self
    {
        $this->keywordsData = $keywordsData;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getAiEnrichment(): ?array
    {
        return $this->aiEnrichment;
    }

    /**
     * @param array<string, mixed>|null $aiEnrichment
     */
    public function setAiEnrichment(?array $aiEnrichment): self
    {
        $this->aiEnrichment = $aiEnrichment;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getScrapedContent(): ?array
    {
        return $this->scrapedContent;
    }

    /**
     * @param array<string, mixed>|null $scrapedContent
     */
    public function setScrapedContent(?array $scrapedContent): self
    {
        $this->scrapedContent = $scrapedContent;

        return $this;
    }

    public function getScreenshotUrl(): ?string
    {
        return $this->screenshotUrl;
    }

    public function setScreenshotUrl(?string $screenshotUrl): self
    {
        $this->screenshotUrl = $screenshotUrl;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getEnrichmentMetrics(): ?array
    {
        return $this->enrichmentMetrics;
    }

    /**
     * @param array<string, mixed>|null $enrichmentMetrics
     */
    public function setEnrichmentMetrics(?array $enrichmentMetrics): self
    {
        $this->enrichmentMetrics = $enrichmentMetrics;

        return $this;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function getBudgetAllocation(): ?array
    {
        return $this->budgetAllocation;
    }

    /**
     * @param array<string, mixed>|null $budgetAllocation
     */
    public function setBudgetAllocation(?array $budgetAllocation): self
    {
        $this->budgetAllocation = $budgetAllocation;

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
        $this->assets->removeElement($asset);

        return $this;
    }

}
