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
use Gorillias\MarketingBundle\Tool\ProjectContextAnalyzerTool;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Form\ClickableInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
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
        private readonly ProjectContextAnalyzerTool $projectAnalyzer,
        private readonly TranslatorInterface $translator,
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
            // Si le bouton "Analyser avec l'IA" a été cliqué
            $analyzeButton = $form->get('analyze');
            if ($analyzeButton instanceof ClickableInterface && $analyzeButton->isClicked()) {
                // Stocker les données du formulaire en session pour l'analyse
                $request->getSession()->set('project_data_for_analysis', [
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

                return $this->redirectToRoute('marketing_project_analyze');
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
        ]);
    }

    /**
     * Analyse un projet avec l'IA et propose des améliorations.
     *
     * Option B - Enrichissement manuel :
     * - L'utilisateur clique sur "Analyser et améliorer avec l'IA"
     * - L'IA analyse : budget, timeline, nom projet, objectifs SMART
     * - Retour : suggestions, warnings, recommandations, score de cohérence
     * - L'utilisateur peut accepter les suggestions ou revenir au formulaire
     *
     * @return Response Template avec analyse IA et suggestions d'amélioration
     */
    #[Route('/analyze', name: 'analyze', methods: ['GET', 'POST'])]
    public function analyze(Request $request): Response
    {
        // Récupérer les données du formulaire depuis la session
        $projectData = $request->getSession()->get('project_data_for_analysis');

        if (! $projectData) {
            $this->addFlash('error', $this->translator->trans('project.flash.no_data_to_analyze', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_new');
        }

        // Si l'utilisateur a cliqué sur "Accepter les suggestions"
        if ($request->isMethod('POST') && $request->request->get('accept_suggestions')) {
            // Créer le projet avec les données améliorées depuis le formulaire
            $project = new Project();
            // Champs modifiables par l'utilisateur dans analyze.html.twig
            $project->setName($request->request->get('name'));
            $project->setDetailedObjectives($request->request->get('detailedObjectives'));

            // Champs en readonly (on garde les valeurs originales)
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
            $project->setStatus(ProjectStatus::DRAFT);

            $this->entityManager->persist($project);
            $this->entityManager->flush();

            // Nettoyer la session
            $request->getSession()->remove('project_data_for_analysis');

            $this->addFlash('success', $this->translator->trans('project.flash.created_with_ai', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_generate', ['id' => $project->getId()]);
        }

        // Calculer la durée de campagne en jours
        $durationDays = $projectData['startDate']->diff($projectData['endDate'])->days;

        // Appeler l'outil d'analyse IA
        $analysisResults = $this->projectAnalyzer->analyzeProject(
            projectName: $projectData['name'],
            companyName: $projectData['companyName'],
            sector: $projectData['sector'],
            budget: (int) ($projectData['budget'] * 100), // Convertir en centimes
            goalType: $projectData['goalType']->value,
            detailedObjectives: $projectData['detailedObjectives'],
            durationDays: $durationDays,
            websiteUrl: $projectData['websiteUrl']
        );

        return $this->render('marketing/project/analyze.html.twig', [
            'projectData' => $projectData,
            'analysis' => $analysisResults,
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
