<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\StrategyGenerationType;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Gorillias\MarketingBundle\Tool\CompetitorIntelligenceTool;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\HttpClient\HttpClientInterface;
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
        private readonly HttpClientInterface $httpClient,
        private readonly LoggerInterface $logger,
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
                'project_context' => $project->getProjectContext(),
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
     * Affiche le formulaire de génération de stratégie marketing avec validation concurrents (Workflow v3.9.0).
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

        // Vérifier le statut du projet
        if (! in_array($project->getStatus(), [ProjectStatus::PERSONA_GENERATED], true)) {
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

            $competitorsInput = isset($data['competitors']) ? trim($data['competitors']) : '';
            $competitors = ! empty($competitorsInput)
                ? array_map('trim', explode(',', $competitorsInput))
                : []; // Vide = détection automatique par le bundle

            // ÉTAPE 1 : Dispatcher l'analyse concurrentielle (OBLIGATOIRE)
            // Le CompetitorToStrategySubscriber se chargera de :
            // 1. Récupérer les données du projet via project_id
            // 2. Construire le context de stratégie
            // 3. Lancer StrategyAnalystAgent avec le context enrichi

            $this->logger->info('🔍 TRACE: StrategyController - Avant dispatchCompetitorAnalysis', [
                'project_id' => $project->getId(),
                'market' => $project->getSector(),
                'competitors_count' => count($competitors),
            ]);

            $taskId = $this->agentTaskManager->dispatchCompetitorAnalysis(
                market: $project->getSector(),
                competitors: $competitors, // Vide ou fournis - fonctionne dans les 2 cas
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

        return $this->render('marketing/strategy/generating.html.twig', [
            'project' => $project,
            'taskId' => $taskId,
            'mercureUrl' => $this->mercurePublicUrl,
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

    /**
     * Scrape le site web du projet pour extraire le contexte SEO/GEO.
     *
     * Extrait :
     * - Métadonnées : title, description, keywords, language
     * - Contenu textuel pour analyse LLM
     *
     * Utilisé pour enrichir la détection de concurrents avec :
     * - product_keywords : Mots-clés produit/service
     * - target_audience : Audience cible détectée
     * - geography : Localisation géographique
     * - business_model : Modèle économique identifié
     *
     * @return array<string, mixed> Context enrichi pour detectCompetitorsInteractive()
     */
    private function scrapeProjectWebsite(Project $project): array
    {
        $websiteUrl = $project->getWebsiteUrl();

        // Si pas d'URL, retourner contexte vide (détection sans enrichissement)
        if (! $websiteUrl) {
            return [];
        }

        try {
            // Scraper le site web via HttpClient (simple fetch HTML)
            $response = $this->httpClient->request('GET', $websiteUrl, [
                'timeout' => 10,
                'max_redirects' => 3,
                'headers' => [
                    'User-Agent' => 'Mozilla/5.0 (compatible; MyCfiaBot/1.0; +https://mycfia.com)',
                ],
            ]);

            $htmlContent = $response->getContent();

            // Extraire les métadonnées du HTML
            $metadata = $this->extractMetadata($htmlContent);

            // Retourner le contexte au format attendu par CompetitorIntelligenceTool
            return [
                'scraped_content' => [
                    'metadata' => $metadata,
                    'markdown' => $this->extractTextContent($htmlContent), // Contenu textuel simplifié
                ],
            ];
        } catch (\Throwable $e) {
            // En cas d'erreur de scraping, retourner contexte vide
            // La détection fonctionnera avec les infos du projet uniquement
            return [];
        }
    }

    /**
     * Extrait les métadonnées HTML (title, description, keywords, language).
     *
     * @return array<string, string|null>
     */
    private function extractMetadata(string $html): array
    {
        $metadata = [
            'title' => null,
            'description' => null,
            'keywords' => null,
            'language' => null,
        ];

        // Extraire le title
        if (preg_match('/<title[^>]*>(.*?)<\/title>/is', $html, $matches)) {
            $metadata['title'] = html_entity_decode(strip_tags($matches[1]), ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Extraire la description (meta description)
        if (preg_match('/<meta[^>]+name=["\']description["\'][^>]+content=["\'](.*?)["\']/is', $html, $matches)) {
            $metadata['description'] = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Extraire les keywords (meta keywords)
        if (preg_match('/<meta[^>]+name=["\']keywords["\'][^>]+content=["\'](.*?)["\']/is', $html, $matches)) {
            $metadata['keywords'] = html_entity_decode($matches[1], ENT_QUOTES | ENT_HTML5, 'UTF-8');
        }

        // Extraire la langue (html lang attribute ou meta language)
        if (preg_match('/<html[^>]+lang=["\']([a-z]{2}(?:-[A-Z]{2})?)["\']/', $html, $matches)) {
            $metadata['language'] = $matches[1];
        } elseif (preg_match('/<meta[^>]+http-equiv=["\']content-language["\'][^>]+content=["\'](.*?)["\']/is', $html, $matches)) {
            $metadata['language'] = $matches[1];
        }

        return $metadata;
    }

    /**
     * Extrait le contenu textuel du HTML (simplifié, sans balises).
     *
     * Utilisé pour analyse LLM du contexte business.
     */
    private function extractTextContent(string $html): string
    {
        // Supprimer les scripts, styles, commentaires
        $cleaned = preg_replace('/<script[^>]*>.*?<\/script>/is', '', $html);
        $cleaned = preg_replace('/<style[^>]*>.*?<\/style>/is', '', (string) $cleaned);
        $cleaned = preg_replace('/<!--.*?-->/s', '', (string) $cleaned);

        // Convertir en texte brut
        $text = strip_tags((string) $cleaned);

        // Nettoyer les espaces multiples
        $text = preg_replace('/\s+/', ' ', $text) ?? '';

        // Limiter à 2000 caractères (contexte suffisant pour LLM)
        return trim(substr($text, 0, 2000));
    }
}
