<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use App\Service\UserPreferencesService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Contracts\Translation\TranslatorInterface;

/**
 * Contrôleur de gestion des paramètres utilisateur.
 *
 * Permet à l'utilisateur de personnaliser :
 * - Thème de l'interface (light/dark)
 * - Langue de l'interface (fr/en)
 *
 * Les informations personnelles (nom, prénom, email, organisation)
 * sont gérées directement dans CFI et synchronisées automatiquement.
 */
#[Route('/settings', name: 'settings_')]
#[IsGranted('ROLE_USER')]
class SettingsController extends AbstractController
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserPreferencesService $preferencesService,
        private readonly TranslatorInterface $translator,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Page des paramètres utilisateur.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->logger->info('Settings: Affichage de la page', [
            'userId' => $user->getId(),
            'currentTheme' => $user->getTheme(),
            'currentLocale' => $user->getLocale(),
        ]);

        return $this->render('settings/index.html.twig', [
            'user' => $user,
        ]);
    }

    /**
     * Mise à jour des paramètres utilisateur.
     */
    #[Route('/update', name: 'update', methods: ['POST'])]
    public function update(Request $request): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $theme = $request->request->get('theme', 'light');
        $locale = $request->request->get('locale', 'fr');

        try {
            // Mise à jour thème
            $this->preferencesService->updateTheme($user, $theme);

            // Mise à jour locale
            $this->preferencesService->updateLocale($user, $locale);

            // Persist en base
            $this->entityManager->flush();

            // Appliquer immédiatement la nouvelle locale à la requête actuelle
            $request->setLocale($locale);

            $this->logger->info('Settings: Préférences mises à jour', [
                'userId' => $user->getId(),
                'newTheme' => $theme,
                'newLocale' => $locale,
            ]);

            // Message flash de succès
            $this->addFlash('success', $this->translator->trans('settings.update.success', [], 'settings'));

            return $this->redirectToRoute('settings_index');
        } catch (\InvalidArgumentException $e) {
            // Erreur de validation
            $this->logger->warning('Settings: Erreur de validation', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
                'theme' => $theme,
                'locale' => $locale,
            ]);

            $this->addFlash('error', $e->getMessage());

            return $this->redirectToRoute('settings_index');
        } catch (\Exception $e) {
            // Erreur inattendue
            $this->logger->error('Settings: Erreur inattendue', [
                'userId' => $user->getId(),
                'error' => $e->getMessage(),
            ]);

            $this->addFlash('error', $this->translator->trans('settings.update.error', [], 'settings'));

            return $this->redirectToRoute('settings_index');
        }
    }
}
