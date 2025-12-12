<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\StrategyGenerationType;
use App\Service\MercureJwtGenerator;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Gorillias\MarketingBundle\Tool\BudgetOptimizerTool;
use Gorillias\MarketingBundle\Tool\CompetitorIntelligenceTool;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour la génération de stratégie marketing par IA.
 *
 * Workflow :
 * 1. GET /marketing/strategy/new/{id} - Affiche formulaire sélection persona/canaux
 * 2. POST /marketing/strategy/new/{id} - Dispatch vers StrategyAnalystAgent
 * 3. Redirection vers page d'attente avec EventSource Mercure
 * 4. StrategyOptimizedEvent → EventListener stocke stratégie en BDD
 * 5. Notification Mercure → Affichage résultats
 *
 * Agent IA : StrategyAnalystAgent (Marketing AI Bundle)
 * Durée : ~30-60 secondes pour stratégie complète
 */
#[Route('/marketing/strategy', name: 'marketing_strategy_')]
#[IsGranted('ROLE_USER')]
final class StrategyController extends AbstractController
{
    public function __construct(
        private readonly AgentTaskManager $agentTaskManager,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly CompetitorIntelligenceTool $competitorIntelligenceTool,
        private readonly BudgetOptimizerTool $budgetOptimizerTool,
        private readonly LoggerInterface $logger,
        private readonly MercureJwtGenerator $mercureJwtGenerator,
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    /**
     * ÉTAPE 1 : Détection interactive des concurrents (Workflow v3.9.0).
     *
     * Utilise CompetitorIntelligenceTool::detectCompetitorsInteractive() pour :
     * - Scraper le site web du projet
     * - Extraire contexte SEO/GEO (keywords, target audience, geography)
     * - Construire requête Google optimisée
     * - Détecter concurrents pertinents via SerpApi
     *
     * Retourne JSON avec liste concurrents + métadonnées pour validation UI.
     */
    #[Route('/detect-competitors/{id}', name: 'detect_competitors', methods: ['POST'])]
    public function detectCompetitors(Project $project): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $project);

        try {
            // Utiliser les données enrichies déjà stockées en base de données
            // au lieu de re-scraper (données de ProjectEnrichmentAgent avec Firecrawl)
            $projectContext = [
                'scraped_content' => $project->getScrapedContent(),
                // TODO: getProjectContext() n'existe pas encore dans l'entité Project
                'project_context' => null,
            ];

            // Appeler CompetitorIntelligenceTool pour détection enrichie
            $detectionResult = $this->competitorIntelligenceTool->detectCompetitorsInteractive(
                sector: $project->getSector(),
                maxCompetitors: 5,
                projectContext: $projectContext
            );

            return $this->json([
                'success' => true,
                'competitors' => $detectionResult['competitors'],
                'search_query' => $detectionResult['search_query'],
                'context_quality' => $detectionResult['context_quality'],
                'source' => $detectionResult['source'],
            ]);
        } catch (\Throwable $e) {
            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Page récapitulative avant génération de stratégie (Workflow v3.28.0).
     *
     * Affiche un récapitulatif complet du projet :
     * - Informations projet (secteur, budget, objectif)
     * - Personas sélectionnés (avec modal détails)
     * - Concurrents validés (avec modal métadonnées complètes)
     *
     * Permet de lancer la génération de stratégie après validation visuelle.
     */
    #[Route('/recap/{id}', name: 'recap', methods: ['GET', 'POST'])]
    public function recap(Request $request, Project $project): Response
    {
        $startTime = microtime(true);
        $this->logger->info('⏱️ PERF: StrategyController::recap - START', [
            'project_id' => $project->getId(),
            'method' => $request->getMethod(),
        ]);

        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier que les personas ont été générés
        if ($project->getPersonas()->isEmpty()) {
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_personas', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_configure', ['id' => $project->getId()]);
        }

        // Vérifier qu'au moins un persona est sélectionné
        $afterPersonasCheck = microtime(true);
        $selectedPersonas = $project->getPersonas()->filter(fn ($persona) => $persona->isSelected())->toArray();
        $afterPersonasFilter = microtime(true);

        $this->logger->info('⏱️ PERF: StrategyController::recap - PERSONAS FILTERED', [
            'personas_count' => count($selectedPersonas),
            'filter_duration_ms' => round(($afterPersonasFilter - $afterPersonasCheck) * 1000, 2),
            'elapsed_ms' => round(($afterPersonasFilter - $startTime) * 1000, 2),
        ]);

        if (empty($selectedPersonas)) {
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_personas_selected', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_show', ['id' => $project->getId()]);
        }

        // Vérifier que les concurrents ont été validés
        $selectedCompetitors = $project->getCompetitors()->filter(fn ($c) => $c->isSelected())->toArray();

        if (empty($selectedCompetitors)) {
            $this->addFlash('warning', 'Vous devez d\'abord détecter et valider les concurrents avant de générer la stratégie.');

            return $this->redirectToRoute('marketing_competitor_detect', ['id' => $project->getId()]);
        }

        // Vérifier le statut du projet
        if (! in_array($project->getStatus(), [ProjectStatus::PERSONA_GENERATED, ProjectStatus::COMPETITOR_VALIDATED], true)) {
            $this->addFlash('info', $this->translator->trans('strategy.flash.already_generated', [], 'marketing'));

            return $this->redirectToRoute('marketing_strategy_show', ['id' => $project->getId()]);
        }

        // Traiter la soumission pour lancer la génération
        if ($request->isMethod('POST')) {
            // Vérifier CSRF
            $token = $request->request->get('_token');
            if (! is_string($token) || ! $this->isCsrfTokenValid('generate_strategy_'.$project->getId(), $token)) {
                $this->addFlash('error', $this->translator->trans('security.invalid_csrf_token', [], 'security'));

                return $this->redirectToRoute('marketing_strategy_recap', ['id' => $project->getId()]);
            }

            /** @var User $user */
            $user = $this->getUser();

            // Construire le contexte de stratégie avec les données du projet
            $personasData = [];
            foreach ($selectedPersonas as $persona) {
                $rawData = $persona->getRawData() ?? [];
                $personasData[] = [
                    'id' => $persona->getId(),
                    'name' => $persona->getName(),
                    'age' => $persona->getAge(),
                    'gender' => $persona->getGender(),
                    'job' => $persona->getJob(),
                    'description' => $persona->getDescription(),
                    'demographics' => $rawData['demographics'] ?? [],
                    'behaviors' => $rawData['behaviors'] ?? [],
                    'pain_points' => $rawData['pain_points'] ?? [],
                    'goals' => $rawData['goals'] ?? [],
                    'selected' => true,
                ];
            }

            $personasIds = array_filter(
                array_map(fn ($p) => $p->getId(), $selectedPersonas),
                fn ($id) => null !== $id
            );

            $selectedAssetTypes = $project->getSelectedAssetTypes() ?? [];
            $scrapedContent = $project->getScrapedContent();

            // v3.29.0 : Mapper les asset types vers les canaux du BudgetOptimizerTool
            $channelMapping = [
                'linkedin_post' => 'linkedin',
                'google_ads' => 'google_ads',
                'facebook_ad' => 'facebook',
                'instagram_post' => 'instagram',
                'email' => 'email',
                'twitter_post' => 'twitter',
                'blog_article' => 'content',
                'landing_page' => 'content',
            ];

            $budgetChannels = [];
            foreach ($selectedAssetTypes as $assetType) {
                if (isset($channelMapping[$assetType])) {
                    $budgetChannels[] = $channelMapping[$assetType];
                }
            }
            $budgetChannels = array_unique($budgetChannels);

            // v3.29.0 : Calculer métriques concurrentielles pour le BudgetOptimizerTool
            $competitorsWithSEA = count(array_filter($selectedCompetitors, fn ($c) => $c->hasAds()));
            $highThreatCompetitors = count(array_filter($selectedCompetitors, fn ($c) => $c->getAlignmentScore() >= 80));

            // v3.29.0 : Calculer l'allocation budgétaire optimisée
            $budgetResult = $this->budgetOptimizerTool->optimizeBudgetWithBenchmarks(
                sector: $project->getSector(),
                channels: $budgetChannels,
                budget: (int) $project->getBudget(), // Budget en euros
                competitorsWithSEA: $competitorsWithSEA,
                highThreatCompetitors: $highThreatCompetitors
            );

            $this->logger->info('🔍 TRACE: StrategyController::recap - Budget allocation calculated (v3.29.0)', [
                'total_budget' => $budgetResult['total_budget'],
                'total_expected_leads' => $budgetResult['total_expected_leads'],
                'confidence_score' => $budgetResult['confidence_score'],
                'channels_count' => count($budgetChannels),
            ]);

            // v3.29.0 : Sauvegarder l'allocation budgétaire dans le Project (persistance)
            $project->setBudgetAllocation([
                'allocation' => $budgetResult['allocation'],
                'total_budget' => $budgetResult['total_budget'],
                'total_expected_leads' => $budgetResult['total_expected_leads'],
                'confidence_score' => $budgetResult['confidence_score'],
                'regulated' => $budgetResult['regulated'],
                'recommendations' => $budgetResult['recommendations'],
            ]);

            // Extraire les domaines pour dispatchCompetitorAnalysis (attend array<string>)
            $competitorDomains = array_map(
                fn ($c) => $c->getDomain(),
                $selectedCompetitors
            );

            $this->logger->info('🔍 TRACE: StrategyController::recap - Avant dispatchCompetitorAnalysis', [
                'project_id' => $project->getId(),
                'sector' => $project->getSector(),
                'competitors_count' => count($competitorDomains),
                'personas_count' => count($personasData),
            ]);

            // Dispatcher l'analyse concurrentielle (chaînera automatiquement vers StrategyAnalystAgent)
            // CompetitorToStrategySubscriber se charge de construire le context et chaîner
            $taskId = $this->agentTaskManager->dispatchCompetitorAnalysis(
                market: $project->getSector(),
                competitors: $competitorDomains,
                dimensions: ['positioning', 'pricing', 'messaging', 'channels'],
                options: [
                    'user_id' => $user->getId(),
                    'project_id' => $project->getId(),
                    'max_competitors' => 5,
                    'include_videos' => false,
                ]
            );

            $this->logger->info('🔍 TRACE: StrategyController::recap - Après dispatchCompetitorAnalysis', [
                'task_id' => $taskId,
            ]);

            // Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::STRATEGY_IN_PROGRESS);
            $this->entityManager->flush();

            $this->addFlash('info', $this->translator->trans('strategy.flash.generation_started', [], 'marketing'));

            // Rediriger vers la page d'attente avec EventSource Mercure
            return $this->redirectToRoute('marketing_strategy_generating', [
                'id' => $project->getId(),
                'taskId' => $taskId,
            ]);
        }

        // Affichage GET : page récapitulative
        $beforeRender = microtime(true);
        $this->logger->info('⏱️ PERF: StrategyController::recap - BEFORE RENDER', [
            'elapsed_ms' => round(($beforeRender - $startTime) * 1000, 2),
        ]);

        $response = $this->render('marketing/strategy/recap.html.twig', [
            'project' => $project,
            'selectedPersonas' => $selectedPersonas,
            'selectedCompetitors' => $selectedCompetitors,
        ]);

        $afterRender = microtime(true);
        $this->logger->info('⏱️ PERF: StrategyController::recap - AFTER RENDER', [
            'render_duration_ms' => round(($afterRender - $beforeRender) * 1000, 2),
            'total_duration_ms' => round(($afterRender - $startTime) * 1000, 2),
        ]);

        return $response;
    }

    /**
     * Affiche le formulaire de génération de stratégie marketing avec validation concurrents (Workflow v3.9.0).
     *
     * @deprecated Remplacé par recap() dans le nouveau workflow v3.28.0
     *
     * Workflow 2 étapes :
     * 1. Détection interactive (AJAX /detect-competitors)
     * 2. Validation utilisateur + génération stratégie
     *
     * Permet de sélectionner :
     * - Un ou plusieurs personas à cibler (sélection multiple)
     * - Liste concurrents détectés automatiquement (validation/modification)
     *
     * Les canaux marketing sont récupérés depuis le projet (sélectionnés à la création).
     *
     * Validation : Projet doit avoir des personas générés.
     */
    #[Route('/new/{id}', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier que les personas ont été générés
        if ($project->getPersonas()->isEmpty()) {
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_personas', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_configure', ['id' => $project->getId()]);
        }

        // Vérifier qu'au moins un persona est sélectionné
        $selectedPersonas = $project->getPersonas()->filter(fn ($persona) => $persona->isSelected())->toArray();

        if (empty($selectedPersonas)) {
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_personas_selected', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_show', ['id' => $project->getId()]);
        }

        // NOUVEAU WORKFLOW : Vérifier que les concurrents ont été validés
        $selectedCompetitorsNew = $project->getCompetitors()->filter(fn ($c) => $c->isSelected())->toArray();

        if (empty($selectedCompetitorsNew)) {
            $this->addFlash('warning', 'Vous devez d\'abord détecter et valider les concurrents avant de générer la stratégie.');

            return $this->redirectToRoute('marketing_competitor_detect', ['id' => $project->getId()]);
        }

        // Vérifier le statut du projet
        if (! in_array($project->getStatus(), [ProjectStatus::PERSONA_GENERATED, ProjectStatus::COMPETITOR_VALIDATED], true)) {
            $this->addFlash('info', $this->translator->trans('strategy.flash.already_generated', [], 'marketing'));

            return $this->redirectToRoute('marketing_strategy_show', ['id' => $project->getId()]);
        }

        $form = $this->createForm(StrategyGenerationType::class, null, [
            'personas' => $selectedPersonas,
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // PHPStan: EntityType avec expanded: true retourne ArrayCollection, pas array
            /** @var array{personas: \Doctrine\Common\Collections\Collection<int, \App\Entity\Persona>|array<\App\Entity\Persona>, competitors?: string} $data */

            /** @var User $user */
            $user = $this->getUser();

            // Extraire les personas sélectionnés (ArrayCollection depuis formulaire avec expanded: true)
            $selectedPersonasEntities = $data['personas'];

            // Convertir ArrayCollection en tableau si nécessaire
            if ($selectedPersonasEntities instanceof \Doctrine\Common\Collections\Collection) {
                $selectedPersonasEntities = $selectedPersonasEntities->toArray();
            }

            /** @var array<\App\Entity\Persona> $selectedPersonasEntities */
            $personasIds = array_filter(
                array_map(fn ($p) => $p->getId(), $selectedPersonasEntities),
                fn ($id) => null !== $id
            );

            // Récupérer les canaux depuis le projet (sélectionnés à la création)
            $channels = $project->getSelectedAssetTypes() ?? [];

            // Récupérer les domaines des concurrents sélectionnés
            $competitorDomains = array_map(
                fn ($c) => $c->getDomain(),
                $selectedCompetitorsNew
            );

            // ÉTAPE 1 : Dispatcher l'analyse concurrentielle (OBLIGATOIRE)
            // Le CompetitorToStrategySubscriber se chargera de :
            // 1. Récupérer les données du projet via project_id
            // 2. Construire le context de stratégie
            // 3. Lancer StrategyAnalystAgent avec le context enrichi

            $this->logger->info('🔍 TRACE: StrategyController - Avant dispatchCompetitorAnalysis', [
                'project_id' => $project->getId(),
                'market' => $project->getSector(),
                'competitors_count' => count($competitorDomains),
            ]);

            $taskId = $this->agentTaskManager->dispatchCompetitorAnalysis(
                market: $project->getSector(),
                competitors: $competitorDomains, // Domaines des concurrents sélectionnés
                dimensions: ['positioning', 'pricing', 'messaging', 'channels'],
                options: [
                    'user_id' => $user->getId(),
                    'project_id' => $project->getId(), // ✅ Copié automatiquement dans context par le bundle
                    'max_competitors' => 5, // Limite détection auto
                    'include_videos' => false, // Optimisation performance
                ]
            );

            $this->logger->info('🔍 TRACE: StrategyController - Après dispatchCompetitorAnalysis', [
                'task_id' => $taskId,
            ]);

            // Mettre à jour le statut du projet
            $project->setStatus(ProjectStatus::STRATEGY_IN_PROGRESS);
            $this->entityManager->flush();

            $this->addFlash('info', $this->translator->trans('strategy.flash.generation_started', [], 'marketing'));

            // Rediriger vers la page d'attente avec EventSource Mercure
            return $this->redirectToRoute('marketing_strategy_generating', [
                'id' => $project->getId(),
                'taskId' => $taskId,
            ]);
        }

        return $this->render('marketing/strategy/new.html.twig', [
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

        // Générer un JWT Mercure pour autoriser l'abonnement aux topics nécessaires
        // - /tasks/{taskId} : pour les événements de la tâche spécifique
        // - marketing/project/{projectId} : pour tous les événements du projet (chaînage automatique)
        $mercureJwt = $this->mercureJwtGenerator->generateSubscriberToken([
            sprintf('tasks/%s', $taskId),
            sprintf('marketing/project/%d', $project->getId()),
        ]);

        return $this->render('marketing/strategy/generating.html.twig', [
            'project' => $project,
            'taskId' => $taskId,
            'mercureUrl' => $this->mercurePublicUrl,
            'mercureJwt' => $mercureJwt,
        ]);
    }

    /**
     * Affiche la stratégie générée pour un projet.
     */
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted('view', $project);

        // Vérifier qu'une stratégie existe
        if ($project->getStrategies()->isEmpty()) {
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_strategy', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        // Récupérer la dernière stratégie générée
        $strategy = $project->getStrategies()->last();

        return $this->render('marketing/strategy/show.html.twig', [
            'project' => $project,
            'strategy' => $strategy,
        ]);
    }
}
