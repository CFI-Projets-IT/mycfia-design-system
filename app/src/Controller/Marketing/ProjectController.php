<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Division;
use App\Entity\Project;
use App\Entity\ProjectEnrichmentDraft;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\ProjectType;
use App\Repository\ProjectEnrichmentDraftRepository;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour la gestion des projets marketing IA.
 *
 * Responsabilités :
 * - CRUD complet des projets marketing
 * - Liste des projets par tenant
 * - Workflow de création : projet → personas → stratégie → assets
 * - Gestion des statuts de projet (draft → published)
 *
 * Architecture :
 * - GET /marketing/projects : Liste des projets
 * - GET /marketing/projects/new : Formulaire création
 * - POST /marketing/projects/new : Enregistrement nouveau projet
 * - GET /marketing/projects/{id} : Vue détail projet
 * - GET /marketing/projects/{id}/edit : Formulaire édition
 * - POST /marketing/projects/{id}/edit : Enregistrement modifications
 * - POST /marketing/projects/{id}/delete : Suppression projet
 */
#[Route('/marketing/projects', name: 'marketing_project_')]
#[IsGranted('ROLE_USER')]
final class ProjectController extends AbstractController
{
    public function __construct(
        private readonly ProjectRepository $projectRepository,
        private readonly ProjectEnrichmentDraftRepository $enrichmentDraftRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly AgentTaskManager $agentTaskManager,
        private readonly TranslatorInterface $translator,
        private readonly CacheInterface $cache,
    ) {
    }

    /**
     * Liste tous les projets marketing du tenant actuel.
     *
     * @return Response Template liste avec projets filtrés par tenant
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        /** @var Division $tenant */
        $tenant = $user->getDivision();

        $projects = $this->projectRepository->findByUserAndTenant($user, $tenant);

        return $this->render('marketing/project/index.html.twig', [
            'projects' => $projects,
        ]);
    }

    /**
     * Affiche le formulaire de création d'un nouveau projet.
     *
     * @return Response Template avec formulaire ProjectType vide
     */
    #[Route('/new', name: 'new', methods: ['GET', 'POST'])]
    public function new(Request $request): Response
    {
        $project = new Project();

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted()) {
            // Détecter si c'est le bouton "Analyser et améliorer avec l'IA"
            $isEnrichmentRequest = $form->has('analyze') && $form->get('analyze')->isSubmitted();

            if ($isEnrichmentRequest) {
                // Validation du formulaire
                if (! $form->isValid()) {
                    $this->addFlash('error', 'Le formulaire contient des erreurs. Veuillez les corriger avant de continuer.');

                    return $this->render('marketing/project/new.html.twig', [
                        'form' => $form,
                    ]);
                }

                // Calculer la durée de campagne en jours
                $durationDays = $project->getStartDate()->diff($project->getEndDate())->days;

                // PHPStan : diff()->days peut être int|false, garantir int
                if (false === $durationDays) {
                    $durationDays = 30; // Valeur par défaut si calcul échoue
                }

                /** @var User $user */
                $user = $this->getUser();

                /** @var Division $tenant */
                $tenant = $user->getDivision();

                // FIX: Récupérer selectedAssetTypes depuis les données RAW de la requête
                // car Symfony Form ChoiceType avec expanded:true stocke les indices (0,1,2) dans l'entité
                // au lieu des vraies valeurs enum (linkedin_post, google_ads)
                $rawData = $request->request->all();
                $selectedAssetTypes = $rawData['project']['selectedAssetTypes'] ?? [];

                // S'assurer que c'est un tableau de strings
                if (is_array($selectedAssetTypes)) {
                    $selectedAssetTypes = array_values(array_filter($selectedAssetTypes, 'is_string'));
                } else {
                    $selectedAssetTypes = [];
                }

                // NOUVEAU WORKFLOW : Créer le projet en base AVANT l'enrichissement
                // Cela permet à l'EventListener de créer un ProjectEnrichmentDraft lié au projet
                $project->setUser($user);
                $project->setTenant($tenant);
                $project->setStatus(ProjectStatus::DRAFT);
                $project->setSelectedAssetTypes($selectedAssetTypes);

                $this->entityManager->persist($project);
                $this->entityManager->flush();

                // Dispatcher la tâche asynchrone d'enrichissement IA via AgentTaskManager
                // Bundle v3.9.1 : Correction du bug de désalignement des paramètres
                // Le paramètre $selectedAssetTypes est maintenant correctement géré
                // Le bundle gère automatiquement le scraping du websiteUrl si fourni
                $taskId = $this->agentTaskManager->dispatchProjectEnrichment(
                    projectName: $project->getName(),
                    companyName: $project->getCompanyName(),
                    sector: $project->getSector(),
                    budget: (int) ((float) $project->getBudget() * 100), // Convertir en centimes
                    goalType: $project->getGoalType()->value,
                    detailedObjectives: $project->getDetailedObjectives(),
                    durationDays: $durationDays,
                    websiteUrl: $project->getWebsiteUrl(), // Bundle scrape automatiquement
                    selectedAssetTypes: $selectedAssetTypes, // ✅ v3.9.1 : paramètre ajouté
                    options: [
                        'user_id' => $user->getId(),
                    ]
                );

                // Stocker l'ID du projet en cache pour l'EventListener (nouveau workflow)
                // L'EventListener récupérera ce mapping pour créer le ProjectEnrichmentDraft
                $this->cache->get('project_id_for_task_'.$taskId, function (ItemInterface $item) use ($project) {
                    $item->expiresAfter(3600); // TTL 1 heure

                    return $project->getId();
                });

                // Rediriger vers la page de génération avec Mercure
                return $this->redirectToRoute('marketing_project_enrichment_generating', [
                    'id' => $project->getId(),
                    'taskId' => $taskId,
                ]);
            }

            // Sinon, traitement normal (bouton "Enregistrer")
            if ($form->isValid()) {
                /** @var User $user */
                $user = $this->getUser();

                /** @var Division $tenant */
                $tenant = $user->getDivision();

                $project->setUser($user);
                $project->setTenant($tenant);
                $project->setStatus(ProjectStatus::DRAFT);

                $this->entityManager->persist($project);
                $this->entityManager->flush();

                $this->addFlash('success', $this->translator->trans('project.flash.created', [], 'marketing'));

                return $this->redirectToRoute('marketing_persona_configure', ['id' => $project->getId()]);
            }
        }

        return $this->render('marketing/project/new.html.twig', [
            'form' => $form,
        ]);
    }

    /**
     * Lance l'enrichissement IA sur un projet existant.
     *
     * Route POST pour démarrer l'analyse IA d'un projet déjà créé.
     * Utilisé notamment depuis la page show.html.twig pour les projets sans personas.
     *
     * Workflow :
     * 1. Récupération du projet existant
     * 2. Dispatch de la tâche d'enrichissement
     * 3. Redirection vers la page de génération avec Mercure SSE
     *
     * @return Response Redirection vers enrichment_generating
     */
    #[Route('/{id}/enrichment/start', name: 'enrichment_start', methods: ['POST'])]
    public function startEnrichment(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier CSRF
        $token = $request->request->get('_token');
        if (! is_string($token) || ! $this->isCsrfTokenValid('enrichment_start_'.$project->getId(), $token)) {
            $this->addFlash('error', $this->translator->trans('security.invalid_csrf_token', [], 'security'));

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        // Calculer la durée de campagne en jours
        $durationDays = $project->getStartDate()->diff($project->getEndDate())->days;

        // PHPStan : diff()->days peut être int|false, garantir int
        if (false === $durationDays) {
            $durationDays = 30; // Valeur par défaut si calcul échoue
        }

        /** @var User $user */
        $user = $this->getUser();

        // Récupérer les selectedAssetTypes depuis le projet
        $selectedAssetTypes = $project->getSelectedAssetTypes() ?? [];

        // Dispatcher la tâche asynchrone d'enrichissement IA
        $taskId = $this->agentTaskManager->dispatchProjectEnrichment(
            projectName: $project->getName(),
            companyName: $project->getCompanyName(),
            sector: $project->getSector(),
            budget: (int) ((float) $project->getBudget() * 100), // Convertir en centimes
            goalType: $project->getGoalType()->value,
            detailedObjectives: $project->getDetailedObjectives(),
            durationDays: $durationDays,
            websiteUrl: $project->getWebsiteUrl(),
            selectedAssetTypes: $selectedAssetTypes,
            options: [
                'user_id' => $user->getId(),
            ]
        );

        // Stocker l'ID du projet en cache pour l'EventListener
        $this->cache->get('project_id_for_task_'.$taskId, function (ItemInterface $item) use ($project) {
            $item->expiresAfter(3600); // TTL 1 heure

            return $project->getId();
        });

        $this->addFlash('success', $this->translator->trans('project.flash.enrichment_started', [], 'marketing'));

        // Rediriger vers la page de génération avec Mercure
        return $this->redirectToRoute('marketing_project_enrichment_generating', [
            'id' => $project->getId(),
            'taskId' => $taskId,
        ]);
    }

    /**
     * Affiche la page de génération d'enrichissement avec loader Mercure.
     */
    #[Route('/{id}/enrichment/generating/{taskId}', name: 'enrichment_generating', methods: ['GET'])]
    public function generateEnrichment(Project $project, string $taskId): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        return $this->render('marketing/enrichment/generating.html.twig', [
            'project' => $project,
            'taskId' => $taskId,
            'mercureUrl' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost/.well-known/mercure',
        ]);
    }

    /**
     * Affiche la page de révision de l'enrichissement en attente de validation.
     *
     * Workflow nouveau (v3.21.0+) :
     * 1. ProjectEnrichedEvent crée ProjectEnrichmentDraft avec statut ENRICHED_PENDING
     * 2. L'utilisateur consulte les données enrichies organisées en 5 onglets
     * 3. Actions possibles : Valider ou Régénérer
     *
     * @return Response Template review avec données draft organisées en onglets
     */
    #[Route('/enrichment/{taskId}/review', name: 'enrichment_review', methods: ['GET'])]
    public function reviewEnrichment(string $taskId): Response
    {
        $draft = $this->enrichmentDraftRepository->findOneByTaskId($taskId);

        if (! $draft) {
            $this->addFlash('error', $this->translator->trans('project.flash.enrichment_not_found', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_index');
        }

        $project = $draft->getProject();
        $this->denyAccessUnlessGranted('edit', $project);

        return $this->render('marketing/enrichment/review.html.twig', [
            'project' => $project,
            'draft' => $draft,
            'enrichmentData' => $draft->getEnrichmentData(),
            'taskId' => $taskId,
        ]);
    }

    /**
     * Valide l'enrichissement, copie les données dans le projet et lance la génération de personas.
     *
     * @return Response Redirection vers page de génération personas
     */
    #[Route('/enrichment/{taskId}/validate', name: 'enrichment_validate', methods: ['POST'])]
    public function validateEnrichment(Request $request, string $taskId): Response
    {
        $draft = $this->enrichmentDraftRepository->findOneByTaskId($taskId);

        if (! $draft) {
            $this->addFlash('error', $this->translator->trans('project.flash.enrichment_not_found', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_index');
        }

        $project = $draft->getProject();
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier CSRF
        $token = $request->request->get('_token');
        if (! is_string($token) || ! $this->isCsrfTokenValid('validate_enrichment_'.$taskId, $token)) {
            $this->addFlash('error', $this->translator->trans('security.invalid_csrf_token', [], 'security'));

            return $this->redirectToRoute('marketing_project_enrichment_review', ['taskId' => $taskId]);
        }

        // Récupérer le nom alternatif sélectionné (obligatoire)
        $selectedName = $request->request->get('selectedName');
        if (! $selectedName || ! is_string($selectedName)) {
            $this->addFlash('error', $this->translator->trans('project.flash.name_selection_required', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_enrichment_review', ['taskId' => $taskId]);
        }

        // Mettre à jour le nom du projet avec le nom sélectionné
        $project->setName($selectedName);

        // Copier les données du draft vers le projet
        $enrichmentData = $draft->getEnrichmentData();

        // ✅ Brand Identity depuis dev.brand_identity (Bundle v3.22.0)
        if (isset($enrichmentData['dev']['brand_identity'])) {
            $project->setBrandIdentity($enrichmentData['dev']['brand_identity']);
        }

        // ✅ Business Intelligence depuis dev.project_context (Bundle v3.22.0)
        if (isset($enrichmentData['dev']['project_context'])) {
            $project->setBusinessIntelligence($enrichmentData['dev']['project_context']);
        }

        // ✅ Mots-clés Google Ads depuis scraped_content.google_ads_keywords (Bundle v3.22.0)
        if (isset($enrichmentData['scraped_content']['google_ads_keywords'])) {
            $project->setKeywordsData($enrichmentData['scraped_content']['google_ads_keywords']);
        }

        // ✅ Contenu scrapé complet
        if (isset($enrichmentData['scraped_content'])) {
            $project->setScrapedContent($enrichmentData['scraped_content']);
        }

        // ✅ Suggestions AI depuis ai_suggestions (Bundle v3.22.0)
        if (isset($enrichmentData['ai_suggestions'])) {
            $project->setAiEnrichment($enrichmentData['ai_suggestions']);
        }

        // Mettre à jour le statut du draft et du projet
        $draft->setStatus('validated');
        $project->setStatus(ProjectStatus::ENRICHED);

        $this->entityManager->flush();

        // Dispatcher la génération de personas avec valeurs par défaut
        $targetDescription = $this->buildTargetDescription($project);

        $personaTaskId = $this->agentTaskManager->dispatchPersonaGeneration(
            sector: $project->getSector(),
            target: $targetDescription,
            options: [
                'count' => 5,
                'min_quality_score' => 70,
                'project_id' => $project->getId(),
            ]
        );

        $this->addFlash('success', $this->translator->trans('project.flash.enrichment_validated', [], 'marketing'));

        return $this->redirectToRoute('marketing_persona_generating', [
            'id' => $project->getId(),
            'taskId' => $personaTaskId,
        ]);
    }

    /**
     * Régénère l'enrichissement du projet.
     *
     * @return Response Redirection vers page de génération enrichissement
     */
    #[Route('/enrichment/{taskId}/regenerate', name: 'enrichment_regenerate', methods: ['POST'])]
    public function regenerateEnrichment(Request $request, string $taskId): Response
    {
        $draft = $this->enrichmentDraftRepository->findOneByTaskId($taskId);

        if (! $draft) {
            $this->addFlash('error', $this->translator->trans('project.flash.enrichment_not_found', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_index');
        }

        $project = $draft->getProject();
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier CSRF
        $token = $request->request->get('_token');
        if (! is_string($token) || ! $this->isCsrfTokenValid('regenerate_enrichment_'.$taskId, $token)) {
            $this->addFlash('error', $this->translator->trans('security.invalid_csrf_token', [], 'security'));

            return $this->redirectToRoute('marketing_project_enrichment_review', ['taskId' => $taskId]);
        }

        // Marquer le draft actuel comme régénéré
        $draft->setStatus('regenerated');
        $this->entityManager->flush();

        // Calculer duration en jours
        $diff = $project->getStartDate()->diff($project->getEndDate());
        $durationDays = false !== $diff->days ? $diff->days : 0;

        // Redispatcher l'enrichissement
        $newTaskId = $this->agentTaskManager->dispatchProjectEnrichment(
            projectName: $project->getName(),
            companyName: $project->getCompanyName(),
            sector: $project->getSector(),
            budget: (int) ((float) $project->getBudget() * 100), // Centimes
            goalType: $project->getGoalType()->value,
            detailedObjectives: $project->getDetailedObjectives(),
            durationDays: $durationDays,
            websiteUrl: $project->getWebsiteUrl(),
            selectedAssetTypes: $project->getSelectedAssetTypes() ?? []
        );

        // Stocker l'ID du projet en cache pour l'EventListener (nouveau workflow)
        // L'EventListener récupérera ce mapping pour mettre à jour le draft existant
        $this->cache->get('project_id_for_task_'.$newTaskId, function (ItemInterface $item) use ($project) {
            $item->expiresAfter(3600); // TTL 1 heure

            return $project->getId();
        });

        $this->addFlash('info', $this->translator->trans('project.flash.enrichment_regenerating', [], 'marketing'));

        // Rediriger vers la page de génération avec Mercure
        return $this->redirectToRoute('marketing_project_enrichment_generating', [
            'id' => $project->getId(),
            'taskId' => $newTaskId,
        ]);
    }

    /**
     * Affiche les détails d'un projet avec workflow de génération.
     *
     * @param Project $project Projet à afficher
     *
     * @return Response Template détail avec statut et actions disponibles
     */
    #[Route('/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted('view', $project);

        return $this->render('marketing/project/show.html.twig', [
            'project' => $project,
        ]);
    }

    /**
     * Construit une description cible pour la génération de personas.
     *
     * @param Project $project Projet source
     *
     * @return string Description narrative pour l'agent IA
     */
    private function buildTargetDescription(Project $project): string
    {
        $parts = [];

        if ($project->getSector()) {
            $parts[] = sprintf('Secteur : %s', $project->getSector());
        }

        if ($project->getDetailedObjectives()) {
            $parts[] = sprintf('Objectifs : %s', $project->getDetailedObjectives());
        }

        if ($project->getProductInfo()) {
            $parts[] = sprintf('Produit/Service : %s', $project->getProductInfo());
        }

        return implode('. ', $parts);
    }

    /**
     * Affiche le formulaire d'édition d'un projet existant.
     *
     * @param Project $project Projet à éditer
     *
     * @return Response Template avec formulaire pré-rempli
     */
    #[Route('/{id}/edit', name: 'edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        $form = $this->createForm(ProjectType::class, $project);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('project.flash.updated', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        return $this->render('marketing/project/edit.html.twig', [
            'form' => $form,
            'project' => $project,
        ]);
    }

    /**
     * Supprime un projet et toutes ses données associées.
     *
     * CASCADE : Suppression automatique des personas, stratégie, assets.
     *
     * @param Project $project Projet à supprimer
     *
     * @return Response Redirection vers liste avec message confirmation
     */
    #[Route('/{id}/delete', name: 'delete', methods: ['POST'])]
    public function delete(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('delete', $project);

        $token = $request->request->get('_token');
        if (is_string($token) && $this->isCsrfTokenValid('delete'.$project->getId(), $token)) {
            $this->entityManager->remove($project);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('project.flash.deleted', [], 'marketing'));
        }

        return $this->redirectToRoute('marketing_project_index');
    }
}
