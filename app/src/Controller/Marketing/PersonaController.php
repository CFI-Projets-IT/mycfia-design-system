<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use Doctrine\ORM\EntityManagerInterface;
use Gorillias\MarketingBundle\Service\AgentTaskManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur pour la génération de personas marketing par IA.
 *
 * Workflow :
 * 1. GET /marketing/persona/generate/{id} - Démarre génération asynchrone
 * 2. Dispatch vers PersonaGeneratorAgent via AgentTaskManager
 * 3. Redirection vers page d'attente avec EventSource Mercure
 * 4. PersonasGeneratedEvent → EventListener stocke personas en BDD
 * 5. Notification Mercure → Affichage résultats
 *
 * Agent IA : PersonaGeneratorAgent (Marketing AI Bundle)
 * Durée : ~15 secondes pour 3-5 personas
 */
#[Route('/marketing/persona', name: 'marketing_persona_')]
#[IsGranted('ROLE_USER')]
final class PersonaController extends AbstractController
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
     * Démarre la génération asynchrone des personas marketing.
     *
     * Workflow :
     * - Vérifie que le projet est en statut DRAFT ou ENRICHED
     * - Dispatche la génération via AgentTaskManager
     * - Redirige vers page d'attente avec Mercure EventSource
     *
     * @param Project $project Projet pour lequel générer les personas
     */
    #[Route('/generate/{id}', name: 'generate', methods: ['GET'])]
    public function generate(Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérifier statut projet : doit être DRAFT ou ENRICHED
        if (! in_array($project->getStatus(), [ProjectStatus::DRAFT, ProjectStatus::ENRICHED], true)) {
            $this->addFlash('warning', $this->translator->trans('persona.flash.already_generated', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        // Vérifier qu'il n'y a pas déjà des personas
        if ($project->getPersonas()->count() > 0) {
            $this->addFlash('info', $this->translator->trans('persona.flash.already_exists', [], 'marketing'));

            return $this->redirectToRoute('marketing_persona_show', ['id' => $project->getId()]);
        }

        // Construire description cible pour PersonaGeneratorAgent
        $targetDescription = $this->buildTargetDescription($project);

        /** @var User $user */
        $user = $this->getUser();

        // Dispatcher la tâche asynchrone de génération via AgentTaskManager
        $taskId = $this->agentTaskManager->dispatchPersonaGeneration(
            sector: $project->getSector(),
            target: $targetDescription,
            options: [
                'project_id' => $project->getId(),
                'user_id' => $user->getId(),
                'detail_level' => 'standard',
                'brand_context' => $project->getWebsiteUrl() ? 'url:'.$project->getWebsiteUrl() : null,
                'objectives' => $project->getDetailedObjectives(),
                'budget' => (int) ((float) $project->getBudget() * 100), // Centimes
            ]
        );

        // Mettre à jour le statut du projet
        $project->setStatus(ProjectStatus::PERSONA_IN_PROGRESS);
        $this->entityManager->flush();

        $this->addFlash('info', $this->translator->trans('persona.flash.generation_started', [], 'marketing'));

        // Rediriger vers la page d'attente avec EventSource Mercure
        return $this->redirectToRoute('marketing_persona_generating', [
            'id' => $project->getId(),
            'taskId' => $taskId,
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

        return $this->render('marketing/persona/generating.html.twig', [
            'project' => $project,
            'taskId' => $taskId,
            'mercureUrl' => $this->mercurePublicUrl,
        ]);
    }

    /**
     * Affiche les personas générés pour un projet.
     */
    #[Route('/show/{id}', name: 'show', methods: ['GET'])]
    public function show(Project $project): Response
    {
        $this->denyAccessUnlessGranted('view', $project);

        if ($project->getPersonas()->isEmpty()) {
            $this->addFlash('warning', $this->translator->trans('persona.flash.no_personas', [], 'marketing'));

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        return $this->render('marketing/persona/show.html.twig', [
            'project' => $project,
            'personas' => $project->getPersonas(),
        ]);
    }

    /**
     * Construit une description cible pour la génération de personas.
     *
     * Combine les informations du projet (secteur, objectifs, produit)
     * en une description narrative pour l'agent IA.
     */
    private function buildTargetDescription(Project $project): string
    {
        $parts = [];

        // Contexte entreprise
        $parts[] = sprintf('Entreprise : %s', $project->getCompanyName());
        $parts[] = sprintf('Secteur : %s', $project->getSector());

        // Produit/Service
        if ($project->getProductInfo()) {
            $parts[] = sprintf('Produit/Service : %s', $project->getProductInfo());
        }

        // Description générale
        if ($project->getDescription()) {
            $parts[] = sprintf('Description : %s', $project->getDescription());
        }

        // Objectifs marketing
        $parts[] = sprintf('Objectif marketing : %s', $project->getGoalType()->getLabel());
        $parts[] = sprintf('Objectifs détaillés : %s', $project->getDetailedObjectives());

        // Contexte campagne
        $durationDays = $project->getStartDate()->diff($project->getEndDate())->days;
        $parts[] = sprintf('Durée campagne : %d jours', $durationDays);
        $parts[] = sprintf('Budget : %.2f€', (float) $project->getBudget());

        return implode("\n", $parts);
    }
}
