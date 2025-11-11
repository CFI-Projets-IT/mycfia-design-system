<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\StrategyGenerationType;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
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
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    /**
     * Affiche le formulaire de génération de stratégie marketing.
     *
     * Permet de sélectionner :
     * - Le persona principal à cibler
     * - Les canaux marketing à utiliser (1 à 8)
     * - Une liste optionnelle de concurrents pour analyse concurrentielle
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

            return $this->redirectToRoute('marketing_persona_generate', ['id' => $project->getId()]);
        }

        // Vérifier le statut du projet
        if (! in_array($project->getStatus(), [ProjectStatus::PERSONA_GENERATED], true)) {
            $this->addFlash('info', $this->translator->trans('strategy.flash.already_generated', [], 'marketing'));

            return $this->redirectToRoute('marketing_strategy_show', ['id' => $project->getId()]);
        }

        // Créer le formulaire avec les personas disponibles
        $form = $this->createForm(StrategyGenerationType::class, null, [
            'personas' => $project->getPersonas()->toArray(),
        ]);

        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            // PHPStan: après isValid(), getData() retourne toujours un array (le PHPDoc de Symfony ne le reflète pas)
            /** @var array{persona: \App\Entity\Persona, channels?: array<string>, competitors?: string} $data */

            /** @var User $user */
            $user = $this->getUser();

            // Extraire les données du formulaire
            $personaId = $data['persona']->getId();
            $channels = $data['channels'] ?? [];
            $competitors = isset($data['competitors']) ? explode(',', $data['competitors']) : [];

            // Dispatcher la tâche asynchrone de génération via AgentTaskManager
            $taskId = $this->agentTaskManager->dispatchStrategyAnalysis(
                sector: $project->getSector(),
                objectives: [$project->getDetailedObjectives()], // Array avec un élément
                context: [
                    'project_id' => $project->getId(),
                    'project_name' => $project->getName(),
                    'company_name' => $project->getCompanyName(),
                    'persona_id' => $personaId,
                    'personas' => $this->serializePersonas($project),
                    'budget' => (int) ((float) $project->getBudget() * 100), // Centimes
                    'duration_days' => $project->getStartDate()->diff($project->getEndDate())->days,
                    'website_url' => $project->getWebsiteUrl(),
                ],
                options: [
                    'user_id' => $user->getId(),
                    'selected_channels' => $channels,
                    'competitors' => array_map('trim', $competitors),
                    'include_competitor_analysis' => ! empty($competitors),
                ]
            );

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
     * Sérialise les personas en tableau pour le contexte de l'agent.
     *
     * @return array<int, array<string, mixed>>
     */
    private function serializePersonas(Project $project): array
    {
        $personas = [];

        foreach ($project->getPersonas() as $persona) {
            $personas[] = [
                'id' => $persona->getId(),
                'name' => $persona->getName(),
                'age' => $persona->getAge(),
                'gender' => $persona->getGender(),
                'job' => $persona->getJob(),
                'interests' => $persona->getInterests(),
                'behaviors' => $persona->getBehaviors(),
                'motivations' => $persona->getMotivations(),
                'pains' => $persona->getPains(),
            ];
        }

        return $personas;
    }
}
