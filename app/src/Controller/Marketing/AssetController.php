<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Asset;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\AssetStatus;
use App\Enum\ProjectStatus;
use App\Form\AssetGenerationType;
use App\Service\MercureJwtGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour la génération et gestion des assets marketing par IA.
 *
 * Workflow :
 * 1. GET /marketing/asset/new/{id} - Affiche formulaire sélection types/variations
 * 2. POST /marketing/asset/new/{id} - Dispatch vers ContentCreatorAgent
 * 3. Redirection vers page d'attente avec EventSource Mercure
 * 4. AssetsGeneratedEvent → EventListener stocke assets en BDD
 * 5. Notification Mercure → Affichage résultats
 * 6. Validation par utilisateur (approve/reject workflow)
 *
 * Agent IA : ContentCreatorAgent (Marketing AI Bundle)
 * AssetBuilders : GoogleAds, LinkedinPost, FacebookPost, InstagramPost,
 *                 Mail, BingAds, IabAsset, ArticleAsset
 * Durée : ~20 secondes par asset (parallélisation possible)
 */
#[Route('/marketing/asset', name: 'marketing_asset_')]
#[IsGranted('ROLE_USER')]
final class AssetController extends AbstractController
{
    /**
     * Mapping des types d'assets internes vers types bundle.
     *
     * Le bundle attend 'email' mais l'app utilise 'mail' historiquement.
     */
    private const ASSET_TYPE_MAPPING = [
        'mail' => 'email',
    ];

    /**
     * Configuration du retry pour la génération d'assets.
     */
    private const MAX_RETRIES = 3;
    private const RETRY_DELAY_MS = 1000;

    public function __construct(
        private readonly AgentTaskManager $agentTaskManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
        private readonly MercureJwtGenerator $mercureJwtGenerator,
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    /**
     * Affiche le formulaire de génération d'assets marketing IA.
     *
     * Permet de sélectionner :
     * - Types d'assets à générer (1-8 canaux multi-canal)
     * - Nombre de variations par type (1-3 pour A/B testing)
     * - Ton de communication optionnel
     * - Instructions spécifiques additionnelles
     *
     * Validation : Projet doit avoir une stratégie générée.
     */
    #[Route('/new/{id}', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier que la stratégie a été générée
        if ($project->getStrategies()->isEmpty()) {
            $this->addFlash('warning', $this->translator->trans('asset.flash.no_strategy', [], 'marketing'));

            return $this->redirectToRoute('marketing_strategy_new', ['id' => $project->getId()]);
        }

        // Vérifier le statut du projet - permettre la génération si stratégie générée ou assets déjà générés
        $allowedStatuses = [
            ProjectStatus::STRATEGY_GENERATED,
            ProjectStatus::ASSETS_IN_PROGRESS,
            ProjectStatus::ASSETS_GENERATED,
        ];
        if (! in_array($project->getStatus(), $allowedStatuses, true)) {
            $this->addFlash('warning', $this->translator->trans('asset.flash.no_strategy', [], 'marketing'));

            return $this->redirectToRoute('marketing_strategy_new', ['id' => $project->getId()]);
        }

        // Créer le formulaire avec les types d'assets sélectionnés du projet
        $form = $this->createForm(AssetGenerationType::class, null, [
            'selected_asset_types' => $project->getSelectedAssetTypes(),
        ]);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // PHPStan: après isValid(), getData() retourne toujours un array
            /** @var array{assetTypes: array<string>, numberOfVariations: int, toneOfVoice?: string, additionalContext?: string} $data */

            /** @var User $user */
            $user = $this->getUser();

            // Préparer le brief de création enrichi
            $brief = $this->buildEnrichedBrief($project);

            // Options de génération
            // Note: project_id doit être dans options car le bundle extrait le context depuis options
            $options = [
                'user_id' => $user->getId(),
                'project_id' => $project->getId(),
                'variations' => $data['numberOfVariations'],
                'tone_of_voice' => $data['toneOfVoice'] ?? null,
                'additional_context' => $data['additionalContext'] ?? '',
            ];

            // Dispatcher une tâche pour chaque type d'asset avec retry logic
            $taskIds = [];
            foreach ($data['assetTypes'] as $assetType) {
                // Mapper le type d'asset (mail → email)
                $bundleAssetType = self::ASSET_TYPE_MAPPING[$assetType] ?? $assetType;

                $taskId = $this->dispatchWithRetry($bundleAssetType, $brief, $options);
                if (null !== $taskId) {
                    $taskIds[] = $taskId;
                }
            }

            // Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::ASSETS_IN_PROGRESS);
            $this->entityManager->flush();

            $this->addFlash('info', $this->translator->trans('asset.flash.generation_started', [], 'marketing'));

            // Rediriger vers la page d'attente avec EventSource Mercure
            // Note: On passe le premier taskId, mais Mercure écoutera tous les events
            return $this->redirectToRoute('marketing_asset_generating', [
                'id' => $project->getId(),
                'taskId' => $taskIds[0] ?? '',
            ]);
        }

        return $this->render('marketing/asset/new.html.twig', [
            'form' => $form,
            'project' => $project,
        ]);
    }

    /**
     * Page d'attente de la génération avec EventSource Mercure.
     *
     * Affiche un loader animé et se connecte à Mercure pour recevoir
     * les notifications de génération en temps réel.
     */
    #[Route('/generating/{id}/{taskId}', name: 'generating', methods: ['GET'])]
    public function generating(Project $project, string $taskId): Response
    {
        $this->denyAccessUnlessGranted('view', $project);

        // Générer un JWT Mercure pour autoriser l'abonnement au topic /tasks/{taskId}
        $mercureJwt = $this->mercureJwtGenerator->generateSubscriberToken([
            sprintf('tasks/%s', $taskId),
        ]);

        return $this->render('marketing/asset/generating.html.twig', [
            'project' => $project,
            'taskId' => $taskId,
            'mercureUrl' => $this->mercurePublicUrl,
            'mercureJwt' => $mercureJwt,
        ]);
    }

    /**
     * Affiche les assets générés pour un projet.
     */
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted('view', $project);

        // Vérifier que des assets existent
        if ($project->getAssets()->isEmpty()) {
            $this->addFlash('warning', $this->translator->trans('asset.flash.no_assets', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        return $this->render('marketing/asset/show.html.twig', [
            'project' => $project,
            'assets' => $project->getAssets(),
        ]);
    }

    /**
     * Construit un brief enrichi avec toutes les données projet pour le bundle.
     *
     * Inclut :
     * - Données de base du projet
     * - Description et informations produit
     * - Identité visuelle (brandIdentity)
     * - Intelligence business
     * - Keywords Google Ads
     * - Stratégie sérialisée
     * - Personas sélectionnés
     * - Analyse concurrentielle
     * - Données d'enrichissement IA
     * - Budget allocation avec CPL calculé par BudgetOptimizer
     *
     * @return array<string, mixed>
     */
    private function buildEnrichedBrief(Project $project): array
    {
        // Données de base obligatoires
        $brief = [
            'project_id' => $project->getId(),
            'project_name' => $project->getName(),
            'company_name' => $project->getCompanyName(),
            'sector' => $project->getSector(),
            'goal_type' => $project->getGoalType()->value,
            'budget' => (int) ((float) $project->getBudget() * 100),
            'description' => $project->getDescription(),
            'product_info' => $project->getProductInfo(),
            'detailed_objectives' => $project->getDetailedObjectives(),
            'start_date' => $project->getStartDate()->format('Y-m-d'),
            'end_date' => $project->getEndDate()->format('Y-m-d'),
        ];

        // URL du site web
        if ($project->getWebsiteUrl()) {
            $brief['website_url'] = $project->getWebsiteUrl();
        }

        // Langue détectée
        if ($project->getLanguage()) {
            $brief['language'] = $project->getLanguage();
        }

        // Identité visuelle complète (couleurs, fonts, personality)
        $brandIdentity = $project->getBrandIdentity();
        if (! empty($brandIdentity)) {
            $brief['brand_identity'] = $brandIdentity;
        }

        // Intelligence business (mainOffering, targetMarket, etc.)
        $businessIntelligence = $project->getBusinessIntelligence();
        if (! empty($businessIntelligence)) {
            $brief['business_intelligence'] = $businessIntelligence;
        }

        // Keywords Google Ads avec métriques
        $keywordsData = $project->getKeywordsData();
        if (! empty($keywordsData)) {
            $brief['keywords_data'] = $keywordsData;
        }

        // Données d'enrichissement IA (suggestions, recommandations)
        $aiEnrichment = $project->getAiEnrichment();
        if (! empty($aiEnrichment)) {
            $brief['ai_enrichment'] = $aiEnrichment;
        }

        // Personas sélectionnés avec rawData complet
        $selectedPersonas = [];
        foreach ($project->getPersonas() as $persona) {
            if ($persona->isSelected()) {
                $selectedPersonas[] = [
                    'name' => $persona->getName(),
                    'job' => $persona->getJob(),
                    'age' => $persona->getAge(),
                    'gender' => $persona->getGender(),
                    'description' => $persona->getDescription(),
                    'quality_score' => $persona->getQualityScore(),
                    'raw_data' => $persona->getRawData(),
                ];
            }
        }
        if (! empty($selectedPersonas)) {
            $brief['personas'] = $selectedPersonas;
        }

        // Analyse concurrentielle
        $competitorAnalysis = $project->getCompetitorAnalysis();
        if (null !== $competitorAnalysis) {
            $brief['competitor_analysis'] = [
                'competitors' => $competitorAnalysis->getCompetitorsArray(),
                'strengths' => $competitorAnalysis->getStrengths(),
                'weaknesses' => $competitorAnalysis->getWeaknesses(),
                'market_positioning' => $competitorAnalysis->getMarketPositioning(),
                'differentiation_opportunities' => $competitorAnalysis->getDifferentiationOpportunities(),
                'marketing_strategies' => $competitorAnalysis->getMarketingStrategies(),
            ];
        }

        // Stratégie avec budget allocation (inclut CPL du BudgetOptimizer)
        $strategyData = $this->serializeStrategy($project);
        $brief['strategy'] = json_encode($strategyData, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) ?: '{}';

        // Budget allocation directement depuis le projet (CPL calculé par BudgetOptimizer)
        $budgetAllocation = $project->getBudgetAllocation();
        if (! empty($budgetAllocation)) {
            $brief['budget_allocation'] = $budgetAllocation;
        }

        return $brief;
    }

    /**
     * Dispatch une tâche de génération avec retry logic.
     *
     * Implémente un backoff exponentiel en cas d'échec.
     *
     * @param array<string, mixed> $brief
     * @param array<string, mixed> $options
     */
    private function dispatchWithRetry(string $assetType, array $brief, array $options): ?string
    {
        $lastException = null;

        for ($attempt = 1; $attempt <= self::MAX_RETRIES; ++$attempt) {
            try {
                $taskId = $this->agentTaskManager->dispatchContentCreation(
                    assetType: $assetType,
                    brief: $brief,
                    options: $options
                );

                $this->logger->info('Asset generation dispatched successfully', [
                    'asset_type' => $assetType,
                    'task_id' => $taskId,
                    'attempt' => $attempt,
                ]);

                return $taskId;
            } catch (\Throwable $e) {
                $lastException = $e;

                $this->logger->warning('Asset generation dispatch failed', [
                    'asset_type' => $assetType,
                    'attempt' => $attempt,
                    'max_retries' => self::MAX_RETRIES,
                    'error' => $e->getMessage(),
                ]);

                if ($attempt < self::MAX_RETRIES) {
                    // Backoff exponentiel
                    usleep(self::RETRY_DELAY_MS * 1000 * $attempt);
                }
            }
        }

        // Toutes les tentatives ont échoué
        // $lastException est garanti non-null ici car on entre dans cette partie
        // seulement si toutes les tentatives ont échoué (au moins une exception catchée)
        /* @var \Throwable $lastException */
        $this->logger->error('Asset generation failed after all retries', [
            'asset_type' => $assetType,
            'max_retries' => self::MAX_RETRIES,
            'error' => $lastException->getMessage(),
        ]);

        $this->addFlash('error', $this->translator->trans('asset.flash.generation_failed', [
            '%type%' => $assetType,
        ], 'marketing'));

        return null;
    }

    /**
     * Sérialise la stratégie en tableau pour le contexte de l'agent.
     *
     * @return array<string, mixed>
     */
    private function serializeStrategy(Project $project): array
    {
        $strategy = $project->getStrategies()->last();

        if (! $strategy) {
            return [];
        }

        return [
            'positioning' => $strategy->getPositioning(),
            'key_messages' => $strategy->getKeyMessages(),
            'recommended_channels' => $strategy->getRecommendedChannels(),
            'timeline' => $strategy->getTimeline(),
            'budget_allocation' => $strategy->getBudgetAllocation(),
            'kpis' => $strategy->getKpis(),
        ];
    }

    /**
     * Approuve un asset marketing généré par IA.
     *
     * Workflow : DRAFT → APPROVED
     * - Marque l'asset comme validé par l'utilisateur
     * - Vérifie si tous les assets du projet sont approuvés
     * - Si oui : Met à jour le statut du projet vers ASSETS_GENERATED
     *
     * @param Project $project Projet parent
     * @param Asset   $asset   Asset à approuver
     *
     * @return Response Redirection vers page projet avec message confirmation
     */
    #[Route('/{id}/{assetId}/approve', name: 'approve', methods: ['POST'])]
    public function approve(Request $request, Project $project, #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(id: 'assetId')] Asset $asset): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        if ($asset->getProject() !== $project) {
            throw $this->createNotFoundException('Cet asset n\'appartient pas à ce projet.');
        }

        /** @var string|null $token */
        $token = $request->request->get('_token');
        if (! $this->isCsrfTokenValid('approve'.$asset->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $asset->setStatus(AssetStatus::APPROVED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Asset approuvé avec succès !');

        // Vérifier si tous les assets sont approuvés pour finaliser le projet
        $allApproved = true;
        foreach ($project->getAssets() as $projectAsset) {
            if (AssetStatus::DRAFT === $projectAsset->getStatus()) {
                $allApproved = false;

                break;
            }
        }

        if ($allApproved && ProjectStatus::ASSETS_IN_PROGRESS === $project->getStatus()) {
            $project->setStatus(ProjectStatus::ASSETS_GENERATED);
            $this->entityManager->flush();
            $this->addFlash('success', 'Tous les assets sont approuvés ! Votre campagne est prête à être publiée.');
        }

        return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
    }

    /**
     * Rejette un asset marketing généré par IA.
     *
     * Workflow : DRAFT → REJECTED
     * - Marque l'asset comme non conforme aux attentes
     * - L'asset reste visible mais n'est pas utilisé dans la campagne
     * - Possibilité de régénérer un asset de remplacement
     *
     * @param Project $project Projet parent
     * @param Asset   $asset   Asset à rejeter
     *
     * @return Response Redirection vers page projet avec message confirmation
     */
    #[Route('/{id}/{assetId}/reject', name: 'reject', methods: ['POST'])]
    public function reject(Request $request, Project $project, #[\Symfony\Bridge\Doctrine\Attribute\MapEntity(id: 'assetId')] Asset $asset): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        if ($asset->getProject() !== $project) {
            throw $this->createNotFoundException('Cet asset n\'appartient pas à ce projet.');
        }

        /** @var string|null $token */
        $token = $request->request->get('_token');
        if (! $this->isCsrfTokenValid('reject'.$asset->getId(), $token)) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $asset->setStatus(AssetStatus::REJECTED);
        $this->entityManager->flush();

        $this->addFlash('warning', 'Asset rejeté. Vous pouvez regénérer un asset de remplacement si nécessaire.');

        return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
    }
}
