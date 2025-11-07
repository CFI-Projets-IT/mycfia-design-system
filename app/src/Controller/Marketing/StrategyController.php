<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Project;
use App\Entity\User;
use App\Enum\ProjectStatus;
use App\Form\StrategyGenerationType;
use App\Message\GenerateStrategyMessage;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la génération de stratégie marketing par IA.
 *
 * Responsabilités :
 * - Affichage formulaire paramètres génération stratégie
 * - Dispatch génération asynchrone via Symfony Messenger
 * - Notification utilisateur via Mercure (temps réel)
 * - Mise à jour statut projet (PERSONA_GENERATED → STRATEGY_GENERATED)
 *
 * Architecture :
 * - GET /marketing/projects/{id}/strategy/generate : Formulaire paramètres
 * - POST /marketing/projects/{id}/strategy/generate : Lancement génération async
 *
 * Agents IA utilisés :
 * - StrategyAnalystAgent (analyse stratégique marketing)
 * - CompetitorAnalystAgent (analyse concurrentielle optionnelle)
 * Stores RAG : StrategyStore (optionnel, Mistral Embed 1024 dimensions)
 * Temps moyen : ~45 secondes pour stratégie complète avec analyse concurrentielle
 */
#[Route('/marketing/projects/{id}/strategy', name: 'marketing_strategy_')]
#[IsGranted('ROLE_USER')]
final class StrategyController extends AbstractController
{
    public function __construct(
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    /**
     * Affiche le formulaire de génération de stratégie marketing IA.
     *
     * Permet de paramétrer :
     * - Inclusion d'une analyse concurrentielle (booléen, défaut true)
     * - Canaux marketing à privilégier (choix multiple optionnel)
     * - Contexte additionnel pour affiner les résultats
     *
     * Validation : Projet doit être en statut PERSONA_GENERATED.
     *
     * @param Project $project Projet pour lequel générer la stratégie
     *
     * @return Response Template avec formulaire StrategyGenerationType
     */
    #[Route('/generate', name: 'generate', methods: ['GET', 'POST'])]
    public function generate(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérification : Ne pas regénérer si déjà une stratégie
        if (ProjectStatus::PERSONA_GENERATED !== $project->getStatus()) {
            if (ProjectStatus::DRAFT === $project->getStatus()) {
                $this->addFlash('warning', 'Vous devez d\'abord générer les personas avant la stratégie.');

                return $this->redirectToRoute('marketing_persona_generate', ['id' => $project->getId()]);
            }

            $this->addFlash('warning', 'La stratégie a déjà été générée pour ce projet. Utilisez l\'édition pour la modifier.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $form = $this->createForm(StrategyGenerationType::class);
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

            // Dispatch message asynchrone vers StrategyAnalystAgent + CompetitorAnalystAgent
            $this->messageBus->dispatch(new GenerateStrategyMessage(
                projectId: $project->getId(),
                userId: (int) $user->getId(),
                tenantId: $tenant->getId(),
                includeCompetitorAnalysis: $data['includeCompetitorAnalysis'] ?? false,
                focusChannels: $data['focusChannels'] ?? [],
                additionalContext: $data['additionalContext'] ?? ''
            ));

            $estimatedTime = $data['includeCompetitorAnalysis'] ? 45 : 30;

            $this->addFlash('success', 'Génération de la stratégie lancée ! Vous serez notifié en temps réel via Mercure.');
            $this->addFlash('info', 'Temps estimé : environ '.$estimatedTime.' secondes.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        return $this->render('marketing/strategy/generate.html.twig', [
            'form' => $form,
            'project' => $project,
            'mercureUrl' => $this->mercurePublicUrl,
        ]);
    }
}
