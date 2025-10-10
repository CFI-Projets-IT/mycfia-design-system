<?php

declare(strict_types=1);

namespace App\Controller;

use App\Security\CfiUser;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/', name: 'home_')]
class HomeController extends AbstractController
{
    #[Route('/', name: 'index')]
    public function index(): Response
    {
        /** @var CfiUser|null $user */
        $user = $this->getUser();

        return $this->render('home/index.html.twig', [
            'firstName' => $user?->getPrenom() ?? 'Utilisateur',
            'theme' => 'light', // TODO Sprint S0+ : Stocker le thème utilisateur (local storage ou préférence CFI)
        ]);
    }
}
