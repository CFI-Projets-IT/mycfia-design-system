<?php

namespace App\Controller;

use App\Entity\User;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'home_')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        /** @var User|null $user */
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'firstName' => $user?->getFirstName() ?? 'Utilisateur',
            'theme' => $user?->getTheme() ?? 'light',
        ]);
    }
}
