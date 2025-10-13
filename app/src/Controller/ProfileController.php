<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\User;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

/**
 * Contrôleur de gestion du profil utilisateur.
 *
 * Affiche les informations de l'utilisateur connecté :
 * - Informations personnelles (nom, prénom, email, organisation)
 * - Statistiques d'utilisation (dernière connexion, nombre de connexions)
 * - Dates de compte (créé le, mis à jour le)
 *
 * Les informations personnelles sont readonly car gérées dans CFI.
 * La modification se fait directement sur l'interface CFI.
 */
#[Route('/profile', name: 'profile_')]
#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Page du profil utilisateur.
     */
    #[Route('', name: 'index', methods: ['GET'])]
    public function index(): Response
    {
        /** @var User $user */
        $user = $this->getUser();

        $this->logger->info('Profile: Affichage de la page', [
            'userId' => $user->getId(),
            'idCfi' => $user->getIdCfi(),
        ]);

        return $this->render('profile/index.html.twig', [
            'user' => $user,
        ]);
    }
}
