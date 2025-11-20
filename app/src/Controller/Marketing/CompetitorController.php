<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\CompetitorAnalysis;
use App\Entity\Project;
use App\Enum\ProjectStatus;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Tool\CompetitorIntelligenceTool;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour la détection et validation des concurrents.
 *
 * Workflow :
 * 1. Afficher la page de détection avec AJAX auto-loading
 * 2. Détection automatique via CompetitorIntelligenceTool
 * 3. Validation utilisateur et enregistrement dans CompetitorAnalysis
 * 4. Redirection vers génération de stratégie
 */
#[Route('/marketing/competitor', name: 'marketing_competitor_')]
#[IsGranted('ROLE_USER')]
final class CompetitorController extends AbstractController
{
    public function __construct(
        private readonly CompetitorIntelligenceTool $competitorIntelligenceTool,
        private readonly EntityManagerInterface $entityManager,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Affiche la page de détection de concurrents avec auto-loading AJAX.
     *
     * Cette page lance automatiquement la détection des concurrents au chargement
     * via AJAX vers la route marketing_competitor_detect_ajax.
     */
    #[Route('/detect/{id}', name: 'detect', methods: ['GET'])]
    public function detect(Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        $this->logger->info('🔍 TRACE: CompetitorController::detect - Début', [
            'project_id' => $project->getId(),
            'personas_count' => $project->getPersonas()->count(),
        ]);

        // Vérifier que les personas ont été générés
        if ($project->getPersonas()->isEmpty()) {
            $this->logger->warning('🔍 TRACE: Aucun persona généré - redirection');
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_personas', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_show', ['id' => $project->getId()]);
        }

        // Vérifier qu'au moins un persona est sélectionné
        $selectedPersonas = $project->getPersonas()->filter(fn ($persona) => $persona->isSelected())->toArray();

        if (empty($selectedPersonas)) {
            $this->logger->warning('🔍 TRACE: Aucun persona sélectionné - redirection', [
                'total_personas' => $project->getPersonas()->count(),
            ]);
            $this->addFlash('warning', $this->translator->trans('strategy.flash.no_personas_selected', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_show', ['id' => $project->getId()]);
        }

        // Vérifier si une analyse existe déjà
        $existingAnalysis = $project->getCompetitorAnalysis();

        $this->logger->info('🔍 TRACE: CompetitorController::detect - Rendu du template', [
            'selected_personas_count' => count($selectedPersonas),
            'has_existing_analysis' => null !== $existingAnalysis,
        ]);

        return $this->render('marketing/competitor/detect.html.twig', [
            'project' => $project,
            'existingAnalysis' => $existingAnalysis,
        ]);
    }

    /**
     * Détection AJAX des concurrents via CompetitorIntelligenceTool.
     *
     * Endpoint appelé automatiquement au chargement de la page detect.
     * Utilise les données enrichies du projet pour détecter les concurrents.
     */
    #[Route('/detect-ajax/{id}', name: 'detect_ajax', methods: ['POST'])]
    public function detectAjax(Project $project): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // ✅ Augmenter le timeout à 600 secondes (10 minutes) pour Phase 4 validation
        set_time_limit(600);

        $this->logger->info('🔍 TRACE: CompetitorController - Début détection AJAX', [
            'project_id' => $project->getId(),
            'sector' => $project->getSector(),
        ]);

        try {
            // ✅ Utiliser les champs dédiés de l'entité Project (v3.22.0)
            $keywordsData = $project->getKeywordsData();
            $brandIdentity = $project->getBrandIdentity();
            $businessIntelligence = $project->getBusinessIntelligence();
            $scrapedContent = $project->getScrapedContent();

            // ✅ Restructurer selon la structure exacte attendue par le bundle v3.22.0
            $projectContext = [
                // ✅ URL du site web (CRITIQUE pour exclusion du domaine client)
                'website_url' => $project->getWebsiteUrl(),

                // À la racine : brand_analysis pour extraction du contexte client
                'brand_analysis' => [
                    'brand_name' => $brandIdentity['brand_name'] ?? $project->getCompanyName(),
                    'extract' => [
                        'geographicMarket' => $businessIntelligence['geography'] ?? 'France',
                        'mainOffering' => $businessIntelligence['valueProposition'] ?? ($project->getProductInfo() ?: ''),
                        'targetMarket' => $businessIntelligence['targetAudience'] ?? '',
                    ],
                ],

                // À la racine : keywords Google Ads (tableau d'objets avec volume pour tri)
                'google_ads_keywords' => $keywordsData['keywords'] ?? [],

                // Dans scraped_content : données brutes de scraping
                'scraped_content' => array_merge(
                    $scrapedContent ?? [],
                    [
                        // project_context = businessIntelligence (analyse LLM)
                        'project_context' => $businessIntelligence ?? [],
                        // language détectée
                        'language' => $scrapedContent['language'] ?? 'fr',
                    ]
                ),
            ];

            $this->logger->info('🔍 TRACE: CompetitorController - Données enrichies récupérées', [
                'has_keywords_data' => null !== $keywordsData,
                'keywords_count' => isset($keywordsData['keywords']) ? count($keywordsData['keywords']) : 0,
                'has_brand_identity' => null !== $brandIdentity,
                'brand_name' => $brandIdentity['brand_name'] ?? 'N/A',
                'has_business_intelligence' => null !== $businessIntelligence,
                'geography' => $businessIntelligence['geography'] ?? 'N/A',
                'target_audience' => $businessIntelligence['targetAudience'] ?? 'N/A',
            ]);

            // ✅ Vérification des longueurs pour détecter troncature
            $geographicMarket = $businessIntelligence['geography'] ?? 'France';
            $mainOffering = $businessIntelligence['valueProposition'] ?? ($project->getProductInfo() ?: '');
            $targetMarket = $businessIntelligence['targetAudience'] ?? '';
            $googleKeywords = $keywordsData['keywords'] ?? [];

            // Extraire les 10 premiers mots-clés avec leurs métriques pour le log
            $top10Keywords = array_slice($googleKeywords, 0, 10);
            $top10Formatted = array_map(
                fn ($kw) => [
                    'keyword' => $kw['keyword'] ?? 'N/A',
                    'volume' => $kw['volume'] ?? 0,
                ],
                $top10Keywords
            );

            $websiteUrl = $project->getWebsiteUrl();
            $googleKeywordsJson = json_encode($googleKeywords);

            $this->logger->info('🔍 TRACE: CompetitorController - Vérification longueur données critiques', [
                'website_url' => $websiteUrl,
                'website_url_length' => null !== $websiteUrl ? strlen($websiteUrl) : 0,
                'geographicMarket_length' => strlen($geographicMarket),
                'geographicMarket_preview' => substr($geographicMarket, 0, 100),
                'mainOffering_length' => strlen($mainOffering),
                'mainOffering_preview' => substr($mainOffering, 0, 200),
                'targetMarket_length' => strlen($targetMarket),
                'targetMarket_preview' => substr($targetMarket, 0, 100),
                'google_keywords_count' => count($googleKeywords),
                'google_keywords_total_length' => false !== $googleKeywordsJson ? strlen($googleKeywordsJson) : 0,
                'google_keywords_top_10' => $top10Formatted,
            ]);

            $this->logger->info('🔍 TRACE: CompetitorController - Avant appel CompetitorIntelligenceTool', [
                'sector' => $project->getSector(),
                'projectContext_keys' => array_keys($projectContext),
                'brand_name' => $projectContext['brand_analysis']['brand_name'],
                'geographic_market' => $projectContext['brand_analysis']['extract']['geographicMarket'],
                'main_offering' => $projectContext['brand_analysis']['extract']['mainOffering'],
                'target_market' => $projectContext['brand_analysis']['extract']['targetMarket'],
                'google_ads_keywords_count' => count($projectContext['google_ads_keywords']),
                'has_scraped_project_context' => isset($projectContext['scraped_content']['project_context']),
                'scraped_content_keys' => array_keys($projectContext['scraped_content']),
            ]);

            $this->logger->info('🔍 DEBUG: projectContext envoyé au bundle', [
                'has_google_ads_keywords_root' => true, // Toujours défini dans projectContext
                'keywords_root_count' => count($projectContext['google_ads_keywords']),
                'keywords_root_first_5' => array_slice($projectContext['google_ads_keywords'], 0, 5),

                'has_google_ads_keywords_scraped' => isset($projectContext['scraped_content']['google_ads_keywords']),
                'keywords_scraped_count' => count($projectContext['scraped_content']['google_ads_keywords'] ?? []),
                'keywords_scraped_first_5' => array_slice($projectContext['scraped_content']['google_ads_keywords'] ?? [], 0, 5),
            ]);

            // Appeler CompetitorIntelligenceTool pour détection enrichie
            $detectionResult = $this->competitorIntelligenceTool->detectCompetitorsInteractive(
                sector: $project->getSector(),
                maxCompetitors: 10,
                projectContext: $projectContext
            );

            $this->logger->info('🔍 TRACE: CompetitorController - Après appel CompetitorIntelligenceTool', [
                'competitors_count' => count($detectionResult['competitors']),
                'source' => $detectionResult['source'],
            ]);

            return $this->json([
                'success' => true,
                'competitors' => $detectionResult['competitors'],
                'search_query' => $detectionResult['search_query'],
                'context_quality' => $detectionResult['context_quality'],
                'source' => $detectionResult['source'],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CompetitorController: Erreur détection AJAX', [
                'project_id' => $project->getId(),
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valide un concurrent ajouté manuellement via CompetitorIntelligenceTool.
     *
     * Endpoint AJAX pour obtenir les métadonnées complètes (validation LLM, scores)
     * d'un concurrent saisi manuellement par l'utilisateur.
     */
    #[Route('/validate-manual/{id}', name: 'validate_manual', methods: ['POST'])]
    public function validateManual(Request $request, Project $project): JsonResponse
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // ✅ Augmenter le timeout pour la validation LLM
        set_time_limit(60);

        $data = json_decode($request->getContent(), true);
        $competitorName = $data['competitor_name'] ?? '';

        if (empty(trim($competitorName))) {
            return $this->json([
                'success' => false,
                'error' => 'Le nom du concurrent est requis',
            ], 400);
        }

        $this->logger->info('🔍 TRACE: CompetitorController - Validation concurrent manuel', [
            'project_id' => $project->getId(),
            'competitor_name' => $competitorName,
        ]);

        try {
            // ✅ Préparer le même projectContext que detectAjax()
            $keywordsData = $project->getKeywordsData();
            $brandIdentity = $project->getBrandIdentity();
            $businessIntelligence = $project->getBusinessIntelligence();
            $scrapedContent = $project->getScrapedContent();

            $projectContext = [
                'website_url' => $project->getWebsiteUrl(),
                'brand_analysis' => [
                    'brand_name' => $brandIdentity['brand_name'] ?? $project->getCompanyName(),
                    'extract' => [
                        'geographicMarket' => $businessIntelligence['geography'] ?? 'France',
                        'mainOffering' => $businessIntelligence['valueProposition'] ?? ($project->getProductInfo() ?: ''),
                        'targetMarket' => $businessIntelligence['targetAudience'] ?? '',
                    ],
                ],
                'google_ads_keywords' => $keywordsData['keywords'] ?? [],
                'scraped_content' => array_merge(
                    $scrapedContent ?? [],
                    [
                        'project_context' => $businessIntelligence ?? [],
                        'language' => $scrapedContent['language'] ?? 'fr',
                    ]
                ),
            ];

            // ✅ Rechercher le concurrent sur Google et le valider
            $detectionResult = $this->competitorIntelligenceTool->detectCompetitorsInteractive(
                sector: $competitorName, // Utiliser le nom comme secteur de recherche
                maxCompetitors: 1, // Un seul résultat
                projectContext: $projectContext
            );

            $this->logger->info('🔍 TRACE: CompetitorController - Résultat validation concurrent manuel', [
                'competitor_name' => $competitorName,
                'found' => ! empty($detectionResult['competitors']),
                'competitors_count' => count($detectionResult['competitors']),
            ]);

            // ✅ Retourner le premier concurrent trouvé (ou erreur si aucun)
            if (empty($detectionResult['competitors'])) {
                return $this->json([
                    'success' => false,
                    'error' => "Aucune information trouvée pour \"$competitorName\". Veuillez vérifier l'orthographe ou essayer un nom différent.",
                ]);
            }

            return $this->json([
                'success' => true,
                'competitor' => $detectionResult['competitors'][0],
            ]);
        } catch (\Throwable $e) {
            $this->logger->error('CompetitorController: Erreur validation concurrent manuel', [
                'project_id' => $project->getId(),
                'competitor_name' => $competitorName,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return $this->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Valide et enregistre les concurrents sélectionnés par l'utilisateur.
     *
     * Crée ou met à jour l'entité CompetitorAnalysis avec la liste des concurrents.
     * Les données d'analyse détaillée (forces, faiblesses, etc.) seront générées
     * par l'agent IA lors de la génération de stratégie.
     */
    #[Route('/validate/{id}', name: 'validate', methods: ['POST'])]
    public function validate(Request $request, Project $project): Response
    {
        $startTime = microtime(true);
        $this->logger->info('⏱️ PERF: CompetitorController::validate - START', ['project_id' => $project->getId()]);

        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier CSRF
        $token = $request->request->get('_token');
        if (! is_string($token) || ! $this->isCsrfTokenValid('validate_competitors_'.$project->getId(), $token)) {
            $this->addFlash('error', $this->translator->trans('security.invalid_csrf_token', [], 'security'));

            return $this->redirectToRoute('marketing_competitor_detect', ['id' => $project->getId()]);
        }

        // ✅ Récupérer les données complètes des concurrents (objets JSON) OU noms (fallback)
        $competitorsDataJson = $request->request->get('competitors_data', '');
        $competitorsInput = $request->request->get('competitors', '');

        $this->logger->info('🔍 TRACE: CompetitorController::validate - Données reçues', [
            'project_id' => $project->getId(),
            'has_competitors_data' => ! empty($competitorsDataJson),
            'competitors_data_length' => is_string($competitorsDataJson) ? strlen($competitorsDataJson) : 0,
            'has_competitors_names' => ! empty($competitorsInput),
        ]);

        // ✅ Parser les données complètes (prioritaire) ou fallback sur les noms
        $competitorsList = [];

        if (! empty($competitorsDataJson) && is_string($competitorsDataJson)) {
            try {
                // Décoder le JSON des objets complets
                $competitorsList = json_decode($competitorsDataJson, true, 512, JSON_THROW_ON_ERROR);

                $this->logger->info('🔍 TRACE: CompetitorController::validate - Objets complets décodés', [
                    'competitors_count' => count($competitorsList),
                    'first_competitor_keys' => ! empty($competitorsList) ? array_keys($competitorsList[0]) : [],
                ]);
            } catch (\JsonException $e) {
                $this->logger->error('🔍 TRACE: CompetitorController::validate - Erreur décodage JSON', [
                    'error' => $e->getMessage(),
                ]);

                // Fallback sur l'ancienne méthode (noms uniquement)
                if (is_string($competitorsInput) && ! empty(trim($competitorsInput))) {
                    $competitorsList = array_map('trim', explode(',', $competitorsInput));
                    $competitorsList = array_filter($competitorsList);
                }
            }
        } elseif (is_string($competitorsInput) && ! empty(trim($competitorsInput))) {
            // Fallback : parser la liste des noms (format: "concurrent1, concurrent2, concurrent3")
            $competitorsList = array_map('trim', explode(',', $competitorsInput));
            $competitorsList = array_filter($competitorsList);

            $this->logger->warning('🔍 TRACE: CompetitorController::validate - Fallback sur noms uniquement', [
                'competitors_count' => count($competitorsList),
            ]);
        }

        // Validation finale
        if (empty($competitorsList)) {
            $this->addFlash('error', 'Vous devez sélectionner au moins un concurrent.');

            return $this->redirectToRoute('marketing_competitor_detect', ['id' => $project->getId()]);
        }

        // Créer ou récupérer l'analyse existante
        $analysis = $project->getCompetitorAnalysis();

        if (null === $analysis) {
            $analysis = new CompetitorAnalysis();
            $analysis->setProject($project);
            $this->entityManager->persist($analysis);
        }

        // ✅ Stocker les objets complets en JSON (métadonnées enrichies incluses)
        // Format: [{"title": "...", "domain": "...", "validation": {...}, "has_ads": true, ...}, ...]
        $analysis->setCompetitors(json_encode($competitorsList, JSON_THROW_ON_ERROR));

        // Initialiser les autres champs avec des tableaux vides en attendant l'analyse IA
        $analysis->setStrengths(json_encode([], JSON_THROW_ON_ERROR));
        $analysis->setWeaknesses(json_encode([], JSON_THROW_ON_ERROR));
        $analysis->setMarketPositioning(json_encode([], JSON_THROW_ON_ERROR));
        $analysis->setDifferentiationOpportunities(json_encode([], JSON_THROW_ON_ERROR));
        $analysis->setMarketingStrategies(json_encode([], JSON_THROW_ON_ERROR));

        // Mettre à jour le statut du projet
        $project->setStatus(ProjectStatus::COMPETITOR_VALIDATED);

        $beforeFlush = microtime(true);
        $this->logger->info('⏱️ PERF: CompetitorController::validate - BEFORE FLUSH', [
            'elapsed_ms' => round(($beforeFlush - $startTime) * 1000, 2),
        ]);

        $this->entityManager->flush();

        $afterFlush = microtime(true);
        $this->logger->info('⏱️ PERF: CompetitorController::validate - AFTER FLUSH', [
            'flush_duration_ms' => round(($afterFlush - $beforeFlush) * 1000, 2),
            'total_elapsed_ms' => round(($afterFlush - $startTime) * 1000, 2),
        ]);

        $this->addFlash('success', sprintf(
            '%d concurrent(s) validé(s) avec succès !',
            count($competitorsList)
        ));

        $beforeRedirect = microtime(true);
        $this->logger->info('⏱️ PERF: CompetitorController::validate - BEFORE REDIRECT', [
            'total_elapsed_ms' => round(($beforeRedirect - $startTime) * 1000, 2),
        ]);

        // Rediriger vers la page récapitulative (nouveau workflow v3.28.0)
        return $this->redirectToRoute('marketing_strategy_recap', ['id' => $project->getId()]);
    }
}
