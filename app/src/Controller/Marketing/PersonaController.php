<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\PersonaGenerationType;
use App\Message\GeneratePersonasMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la génération de personas marketing par IA.
 *
 * Responsabilités :
 * - Affichage formulaire paramètres génération personas
 * - Dispatch génération asynchrone via Symfony Messenger
 * - Notification utilisateur via Mercure (temps réel)
 * - Mise à jour statut projet (DRAFT → PERSONA_GENERATED)
 *
 * Architecture :
 * - GET /marketing/projects/{id}/personas/generate : Formulaire paramètres
 * - POST /marketing/projects/{id}/personas/generate : Lancement génération async
 *
 * Agent IA utilisé : PersonaGeneratorAgent (Marketing AI Bundle)
 * Stores RAG : PersonaStore (optionnel, Mistral Embed 1024 dimensions)
 * Temps moyen : ~30 secondes pour 3 personas
 */
#[Route('/marketing/projects/{id}/personas', name: 'marketing_persona_')]
#[IsGranted('ROLE_USER')]
final class PersonaController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    /**
     * Affiche le formulaire de génération de personas IA.
     *
     * Permet de paramétrer :
     * - Nombre de personas (1-5, défaut 3)
     * - Contexte additionnel pour affiner les résultats
     *
     * Validation : Projet doit être en statut DRAFT.
     *
     * @param Project $project Projet pour lequel générer les personas
     *
     * @return Response Template avec formulaire PersonaGenerationType
     */
    #[Route('/generate', name: 'generate', methods: ['GET', 'POST'])]
    public function generate(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérification : Ne pas regénérer si déjà des personas
        if (ProjectStatus::DRAFT !== $project->getStatus()) {
            $this->addFlash('warning', 'Les personas ont déjà été générés pour ce projet. Utilisez l\'édition pour les modifier.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $form = $this->createForm(PersonaGenerationType::class);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $data = $form->getData();

            /** @var User $user */
            $user = $this->getUser();
            $tenant = $user->getDivision();

            if (null === $tenant) {
                $this->addFlash('error', 'Aucune division sélectionnée.');

                return $this->redirectToRoute('marketing_project_index');
            }

            // Dispatch message asynchrone vers PersonaGeneratorAgent
            $this->messageBus->dispatch(new GeneratePersonasMessage(
                projectId: $project->getId(),
                userId: (int) $user->getId(),
                tenantId: $tenant->getId(),
                numberOfPersonas: $data['numberOfPersonas'],
                additionalContext: $data['additionalContext'] ?? ''
            ));

            $this->addFlash('success', 'Génération des personas lancée ! Vous serez notifié en temps réel via Mercure.');
            $this->addFlash('info', 'Temps estimé : environ 30 secondes pour '.$data['numberOfPersonas'].' personas.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        return $this->render('marketing/persona/generate.html.twig', [
            'form' => $form,
            'project' => $project,
            'mercureUrl' => $this->mercurePublicUrl,
        ]);
    }
}
