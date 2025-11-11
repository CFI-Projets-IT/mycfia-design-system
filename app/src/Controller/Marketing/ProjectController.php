<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Division;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\ProjectType;
use App\Repository\ProjectRepository;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Psr\Cache\InvalidArgumentException;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Cache\CacheInterface;
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
            // Détecter si c'est une requête AJAX d'enrichissement
            $isAjaxEnrichment = $request->isXmlHttpRequest() && $request->request->has('analyze');

            if ($isAjaxEnrichment) {

                // Validation du formulaire
                if (! $form->isValid()) {
                    // Retourner les erreurs de validation en JSON pour AJAX
                    $errors = [];
                    foreach ($form->getErrors(true) as $error) {
                        $errors[] = $error->getMessage();
                    }

                    return new JsonResponse([
                        'success' => false,
                        'error' => 'Le formulaire contient des erreurs : '.implode(', ', $errors),
                        'validation_errors' => $errors,
                    ], 422);
                }

                // Calculer la durée de campagne en jours
                $durationDays = $project->getStartDate()->diff($project->getEndDate())->days;

                /** @var User $user */
                $user = $this->getUser();

                // Dispatcher la tâche asynchrone d'enrichissement IA via AgentTaskManager
                $taskId = $this->agentTaskManager->dispatchProjectEnrichment(
                    projectName: $project->getName(),
                    companyName: $project->getCompanyName(),
                    sector: $project->getSector(),
                    budget: (int) ((float) $project->getBudget() * 100), // Convertir en centimes
                    goalType: $project->getGoalType()->value,
                    detailedObjectives: $project->getDetailedObjectives(),
                    durationDays: $durationDays,
                    websiteUrl: $project->getWebsiteUrl(),
                    options: [
                        'user_id' => $user->getId(),
                    ]
                );

                // Stocker les données du projet en session pour utilisation après enrichissement
                $request->getSession()->set('project_data_for_enrichment_'.$taskId, [
                    'name' => $project->getName(),
                    'companyName' => $project->getCompanyName(),
                    'sector' => $project->getSector(),
                    'description' => $project->getDescription(),
                    'productInfo' => $project->getProductInfo(),
                    'goalType' => $project->getGoalType(),
                    'detailedObjectives' => $project->getDetailedObjectives(),
                    'budget' => $project->getBudget(),
                    'startDate' => $project->getStartDate(),
                    'endDate' => $project->getEndDate(),
                    'websiteUrl' => $project->getWebsiteUrl(),
                ]);

                // Retourner une réponse JSON avec le taskId pour abonnement Mercure
                return new JsonResponse([
                    'success' => true,
                    'taskId' => $taskId,
                    'message' => $this->translator->trans('project.flash.enrichment_started', [], 'marketing'),
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

                return $this->redirectToRoute('marketing_persona_generate', ['id' => $project->getId()]);
            }
        }

        return $this->render('marketing/project/new.html.twig', [
            'form' => $form,
            'mercure_public_url' => $_ENV['MERCURE_PUBLIC_URL'] ?? 'http://localhost:82/.well-known/mercure',
        ]);
    }

    /**
     * Récupère les résultats d'enrichissement IA depuis le cache.
     *
     * Route AJAX appelée par JavaScript après réception de l'événement Mercure ProjectEnrichedEvent.
     *
     * @return JsonResponse Résultats enrichissement ou erreur
     *
     * @throws InvalidArgumentException
     */
    #[Route('/enrichment/{taskId}/results', name: 'enrichment_results', methods: ['GET'])]
    public function getEnrichmentResults(string $taskId): JsonResponse
    {
        // Récupérer les résultats depuis le cache (stockés par ProjectEnrichedEventListener)
        $cacheKey = 'enrichment_results_'.$taskId;

        try {
            $enrichmentResults = $this->cache->get($cacheKey, function () {
                throw new \RuntimeException('Cache miss');
            });
        } catch (\RuntimeException) {
            return new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans('project.flash.enrichment_not_found', [], 'marketing'),
            ], 404);
        }

        return new JsonResponse([
            'success' => true,
            'results' => $enrichmentResults,
        ]);
    }

    /**
     * Accepte les suggestions d'enrichissement IA et crée le projet.
     *
     * @return JsonResponse Succès avec redirect URL ou erreur
     */
    #[Route('/enrichment/{taskId}/accept', name: 'enrichment_accept', methods: ['POST'])]
    public function acceptEnrichment(Request $request, string $taskId): JsonResponse
    {
        // Récupérer les données originales du projet
        $projectData = $request->getSession()->get('project_data_for_enrichment_'.$taskId);

        if (! $projectData) {
            return new JsonResponse([
                'success' => false,
                'error' => $this->translator->trans('project.flash.no_data_to_analyze', [], 'marketing'),
            ], 404);
        }

        // Récupérer les données soumises depuis la modal (nom et objectifs modifiés)
        $data = json_decode($request->getContent(), true);

        // Créer le projet avec les données enrichies
        $project = new Project();
        $project->setName($data['name'] ?? $projectData['name']);
        $project->setDetailedObjectives($data['detailedObjectives'] ?? $projectData['detailedObjectives']);

        // Champs non modifiés (valeurs originales)
        $project->setCompanyName($projectData['companyName']);
        $project->setSector($projectData['sector']);
        $project->setDescription($projectData['description']);
        $project->setProductInfo($projectData['productInfo']);
        $project->setGoalType($projectData['goalType']);
        $project->setBudget($projectData['budget']);
        $project->setStartDate($projectData['startDate']);
        $project->setEndDate($projectData['endDate']);
        $project->setWebsiteUrl($projectData['websiteUrl']);

        /** @var User $user */
        $user = $this->getUser();

        /** @var Division $tenant */
        $tenant = $user->getDivision();

        $project->setUser($user);
        $project->setTenant($tenant);
        $project->setStatus(ProjectStatus::ENRICHED);

        $this->entityManager->persist($project);
        $this->entityManager->flush();

        // Nettoyer la session
        $request->getSession()->remove('project_data_for_enrichment_'.$taskId);
        $request->getSession()->remove('enrichment_results_'.$taskId);

        return new JsonResponse([
            'success' => true,
            'message' => $this->translator->trans('project.flash.created_with_ai', [], 'marketing'),
            'redirectUrl' => $this->generateUrl('marketing_persona_generate', ['id' => $project->getId()]),
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

        if ($this->isCsrfTokenValid('delete'.$project->getId(), $request->request->get('_token'))) {
            $this->entityManager->remove($project);
            $this->entityManager->flush();

            $this->addFlash('success', $this->translator->trans('project.flash.deleted', [], 'marketing'));
        }

        return $this->redirectToRoute('marketing_project_index');
    }
}
