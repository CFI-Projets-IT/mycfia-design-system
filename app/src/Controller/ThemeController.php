<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/theme', name: 'theme_')]
class ThemeController extends AbstractController
{
    #[Route('/switch/{theme}', name: 'switch')]
    public function switchTheme(
        string $theme,
        Request $request,
        EntityManagerInterface $entityManager
    ): RedirectResponse {
        // Valider le thème
        $allowedThemes = ['light', 'dark-blue', 'dark-red'];
        if (! in_array($theme, $allowedThemes, true)) {
            $this->addFlash('error', 'Thème invalide');

            return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('home_index'));
        }

        // Sauvegarder le thème pour l'utilisateur connecté
        $user = $this->getUser();
        if ($user instanceof User) {
            $user->setTheme($theme);
            $entityManager->flush();
        }

        // Rediriger vers la page précédente
        return $this->redirect($request->headers->get('referer') ?? $this->generateUrl('home_index'));
    }
}
