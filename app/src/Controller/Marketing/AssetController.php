<?php

declare(strict_types=1);

namespace App\Controller\Marketing;

use App\Entity\Asset;
use App\Entity\Project;
use App\Entity\User;
use App\Enum\AssetStatus;
use App\Enum\ProjectStatus;
use App\Form\AssetGenerationType;
use App\Message\GenerateAssetsMessage;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur pour la génération et gestion des assets marketing par IA.
 *
 * Responsabilités :
 * - Affichage formulaire paramètres génération assets multi-canal
 * - Dispatch génération asynchrone via Symfony Messenger
 * - Validation assets par l'utilisateur (approve/reject workflow)
 * - Notification utilisateur via Mercure (temps réel)
 * - Mise à jour statut projet (STRATEGY_GENERATED → ASSETS_GENERATING → ASSETS_GENERATED)
 *
 * Architecture :
 * - GET /marketing/projects/{id}/assets/generate : Formulaire paramètres
 * - POST /marketing/projects/{id}/assets/generate : Lancement génération async
 * - POST /marketing/projects/{id}/assets/{assetId}/approve : Approuver un asset
 * - POST /marketing/projects/{id}/assets/{assetId}/reject : Rejeter un asset
 *
 * Agent IA utilisé : ContentCreatorAgent (Marketing AI Bundle)
 * AssetBuilders disponibles : GoogleAds, LinkedinPost, FacebookPost, InstagramPost,
 *                              Mail, BingAds, IabAsset, ArticleAsset
 * Stores RAG : AssetStore (optionnel, Mistral Embed 1024 dimensions)
 * Temps moyen : ~20 secondes par asset (parallélisation possible)
 */
#[Route('/marketing/projects/{id}/assets', name: 'marketing_asset_')]
#[IsGranted('ROLE_USER')]
final class AssetController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MessageBusInterface $messageBus,
        #[Autowire('%env(MERCURE_PUBLIC_URL)%')]
        private readonly string $mercurePublicUrl,
    ) {
    }

    /**
     * Affiche le formulaire de génération d'assets marketing IA.
     *
     * Permet de paramétrer :
     * - Types d'assets à générer (1-8 types multi-canal)
     * - Nombre de variations par asset (1-3, A/B testing)
     * - Ton de communication (6 styles disponibles)
     * - Instructions spécifiques pour personnalisation
     *
     * Validation : Projet doit être en statut STRATEGY_GENERATED.
     *
     * @param Project $project Projet pour lequel générer les assets
     *
     * @return Response Template avec formulaire AssetGenerationType
     */
    #[Route('/generate', name: 'generate', methods: ['GET', 'POST'])]
    public function generate(Request $request, Project $project): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        // Vérification : Ne pas regénérer si déjà des assets
        if (ProjectStatus::STRATEGY_GENERATED !== $project->getStatus()) {
            if (ProjectStatus::DRAFT === $project->getStatus()) {
                $this->addFlash('warning', 'Vous devez d\'abord générer les personas et la stratégie.');

                return $this->redirectToRoute('marketing_persona_generate', ['id' => $project->getId()]);
            }

            if (ProjectStatus::PERSONA_GENERATED === $project->getStatus()) {
                $this->addFlash('warning', 'Vous devez d\'abord générer la stratégie avant les assets.');

                return $this->redirectToRoute('marketing_strategy_generate', ['id' => $project->getId()]);
            }

            $this->addFlash('warning', 'Les assets ont déjà été générés pour ce projet. Utilisez l\'édition pour les modifier.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $form = $this->createForm(AssetGenerationType::class);
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

            // Dispatch message asynchrone vers ContentCreatorAgent
            $this->messageBus->dispatch(new GenerateAssetsMessage(
                projectId: $project->getId(),
                userId: (int) $user->getId(),
                tenantId: $tenant->getId(),
                assetTypes: $data['assetTypes'],
                numberOfVariations: $data['numberOfVariations'],
                toneOfVoice: $data['toneOfVoice'] ?? null,
                additionalContext: $data['additionalContext'] ?? ''
            ));

            $totalAssets = count($data['assetTypes']) * $data['numberOfVariations'];
            $estimatedTime = $totalAssets * 20;

            $this->addFlash('success', 'Génération des assets lancée ! Vous serez notifié en temps réel via Mercure.');
            $this->addFlash('info', 'Temps estimé : environ '.$estimatedTime.' secondes pour '.$totalAssets.' assets.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        return $this->render('marketing/asset/generate.html.twig', [
            'form' => $form,
            'project' => $project,
            'mercureUrl' => $this->mercurePublicUrl,
        ]);
    }

    /**
     * Approuve un asset marketing généré par IA.
     *
     * Workflow : DRAFT → APPROVED
     * - Marque l'asset comme validé par l'utilisateur
     * - Vérifie si tous les assets du projet sont approuvés
     * - Si oui : Met à jour le statut du projet vers ASSETS_GENERATED
     *
     * @param Project $project Projet parent
     * @param Asset   $asset   Asset à approuver
     *
     * @return Response Redirection vers page projet avec message confirmation
     */
    #[Route('/{assetId}/approve', name: 'approve', methods: ['POST'])]
    public function approve(Request $request, Project $project, Asset $asset): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        if ($asset->getProject() !== $project) {
            throw $this->createNotFoundException('Cet asset n\'appartient pas à ce projet.');
        }

        if (! $this->isCsrfTokenValid('approve'.$asset->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $asset->setStatus(AssetStatus::APPROVED);
        $this->entityManager->flush();

        $this->addFlash('success', 'Asset approuvé avec succès !');

        // Vérifier si tous les assets sont approuvés pour finaliser le projet
        $allApproved = true;
        foreach ($project->getAssets() as $projectAsset) {
            if (AssetStatus::DRAFT === $projectAsset->getStatus()) {
                $allApproved = false;

                break;
            }
        }

        if ($allApproved && ProjectStatus::ASSETS_GENERATING === $project->getStatus()) {
            $project->setStatus(ProjectStatus::ASSETS_GENERATED);
            $this->entityManager->flush();
            $this->addFlash('success', 'Tous les assets sont approuvés ! Votre campagne est prête à être publiée.');
        }

        return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
    }

    /**
     * Rejette un asset marketing généré par IA.
     *
     * Workflow : DRAFT → REJECTED
     * - Marque l'asset comme non conforme aux attentes
     * - L'asset reste visible mais n'est pas utilisé dans la campagne
     * - Possibilité de régénérer un asset de remplacement
     *
     * @param Project $project Projet parent
     * @param Asset   $asset   Asset à rejeter
     *
     * @return Response Redirection vers page projet avec message confirmation
     */
    #[Route('/{assetId}/reject', name: 'reject', methods: ['POST'])]
    public function reject(Request $request, Project $project, Asset $asset): Response
    {
        $this->denyAccessUnlessGranted('edit', $project);

        if ($asset->getProject() !== $project) {
            throw $this->createNotFoundException('Cet asset n\'appartient pas à ce projet.');
        }

        if (! $this->isCsrfTokenValid('reject'.$asset->getId(), $request->request->get('_token'))) {
            $this->addFlash('error', 'Token CSRF invalide.');

            return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
        }

        $asset->setStatus(AssetStatus::REJECTED);
        $this->entityManager->flush();

        $this->addFlash('warning', 'Asset rejeté. Vous pouvez regénérer un asset de remplacement si nécessaire.');

        return $this->redirectToRoute('marketing_project_show', ['id' => $project->getId()]);
    }
}
