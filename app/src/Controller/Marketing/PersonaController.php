<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Persona;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\Marketing\PersonaGenerationConfigType;
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
     * Affiche le formulaire de configuration pour la génération de personas.
     *
     * Permet à l'utilisateur de choisir :
     * - Le nombre de personas à générer (1-10)
     * - Le seuil de qualité minimum (60-80%)
     *
     * @param Project $project Projet pour lequel configurer la génération
     */
    #[Route('/configure/{id}', name: 'configure', methods: ['GET'])]
    public function configure(Project $project): Response
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

        // Créer le formulaire de configuration
        $form = $this->createForm(PersonaGenerationConfigType::class);

        return $this->render('marketing/persona/configure.html.twig', [
            'project' => $project,
            'form' => $form,
        ]);
    }

    /**
     * Démarre la génération asynchrone des personas marketing.
     *
     * Workflow :
     * - Vérifie que le projet est en statut DRAFT ou ENRICHED
     * - Récupère la configuration (count, minQualityScore)
     * - Dispatche la génération via AgentTaskManager
     * - Redirige vers page d'attente avec Mercure EventSource
     *
     * @param Request $request Requête contenant la configuration
     * @param Project $project Projet pour lequel générer les personas
     */
    #[Route('/generate/{id}', name: 'generate', methods: ['POST'])]
    public function generate(Request $request, Project $project): Response
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

        // Récupérer la configuration depuis le formulaire
        $form = $this->createForm(PersonaGenerationConfigType::class);
        $form->handleRequest($request);

        // Valeurs par défaut si formulaire invalide
        $count = $form->isSubmitted() && $form->isValid()
            ? $form->get('count')->getData()
            : 3;
        $minQualityScore = $form->isSubmitted() && $form->isValid()
            ? $form->get('minQualityScore')->getData()
            : 70;

        // Construire description cible pour PersonaGeneratorAgent
        $targetDescription = $this->buildTargetDescription($project);

        /** @var User $user */
        $user = $this->getUser();

        // FIX: Récupérer selectedAssetTypes depuis la session car $project->getSelectedAssetTypes()
        // contient les indices (0,1,2) au lieu des vraies valeurs enum
        // Les vraies valeurs ont été stockées dans project_data_for_enrichment lors de l'enrichissement
        $selectedAssetTypes = [];
        $sessionKeys = array_keys($request->getSession()->all());
        foreach ($sessionKeys as $key) {
            if (str_starts_with($key, 'project_data_for_enrichment_')) {
                $projectData = $request->getSession()->get($key);
                if (isset($projectData['selectedAssetTypes'])) {
                    $selectedAssetTypes = $projectData['selectedAssetTypes'];

                    break;
                }
            }
        }

        // Dispatcher la tâche asynchrone de génération via AgentTaskManager
        $taskId = $this->agentTaskManager->dispatchPersonaGeneration(
            sector: $project->getSector(),
            target: $targetDescription,
            options: [
                'project_id' => $project->getId(),
                'user_id' => $user->getId(),
                'count' => $count,                              // 🆕 Nombre de personas (v3.4.0)
                'min_quality_score' => (float) $minQualityScore, // 🆕 Seuil qualité (v3.4.0)
                'detail_level' => 'standard',
                'brand_context' => $project->getWebsiteUrl() ? 'url:'.$project->getWebsiteUrl() : null,
                'objectives' => $project->getDetailedObjectives(),
                'budget' => (int) ((float) $project->getBudget() * 100), // Centimes
                'selected_asset_types' => $selectedAssetTypes, // ✅ Vraies valeurs enum depuis session
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
     * Affiche le détail d'un persona spécifique.
     *
     * @param Persona $persona Persona à afficher
     */
    #[Route('/detail/{id}', name: 'detail', methods: ['GET'])]
    public function detail(Persona $persona): Response
    {
        $project = $persona->getProject();
        $this->denyAccessUnlessGranted('view', $project);

        return $this->render('marketing/persona/detail.html.twig', [
            'persona' => $persona,
            'project' => $project,
        ]);
    }

    /**
     * Met à jour la sélection des personas pour la campagne marketing.
     *
     * Workflow v3.8.0 : ÉTAPE 2.5 - Sélection manuelle des personas.
     * Cette méthode permet à l'utilisateur de choisir quels personas
     * cibler dans la stratégie et les assets générés (-60% tokens).
     *
     * @param Request $request Requête contenant selected_personas[]
     * @param Project $project Projet concerné
     */
    #[Route('/update-selection/{id}', name: 'update_selection', methods: ['POST'])]
    public function updateSelection(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Récupérer les IDs des personas sélectionnés depuis le formulaire
        $selectedPersonaIds = $request->request->all('selected_personas');

        // Désélectionner tous les personas du projet
        foreach ($project->getPersonas() as $persona) {
            $persona->setSelected(false);
        }

        // Sélectionner uniquement les personas cochés
        if (! empty($selectedPersonaIds)) {
            foreach ($project->getPersonas() as $persona) {
                if (in_array((string) $persona->getId(), $selectedPersonaIds, true)) {
                    $persona->setSelected(true);
                }
            }
        }

        // Persister les changements
        $this->entityManager->flush();

        $selectedCount = count($selectedPersonaIds);
        $totalCount = $project->getPersonas()->count();

        $this->addFlash('success', $this->translator->trans(
            'persona.flash.selection_updated',
            ['%count%' => $selectedCount, '%total%' => $totalCount],
            'marketing'
        ));

        return $this->redirectToRoute('marketing_competitor_detect', ['id' => $project->getId()]);
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
