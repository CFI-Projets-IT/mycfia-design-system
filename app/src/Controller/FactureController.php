<?php

namespace App\Controller;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/factures')]
class FactureController extends AbstractController
{
    #[Route('', name: 'app_facture_index', methods: ['GET'])]
    public function index(FactureRepository $repository): Response
    {
        return $this->render('facture/index.html.twig', [
            'factures' => $repository->findBy([], ['invoiceDate' => 'DESC']),
        ]);
    }

    #[Route('/new', name: 'app_facture_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $facture = new Facture();

        return $this->render('facture/new.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}', name: 'app_facture_show', methods: ['GET'])]
    public function show(Facture $facture): Response
    {
        return $this->render('facture/show.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_facture_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        return $this->render('facture/edit.html.twig', [
            'facture' => $facture,
        ]);
    }

    #[Route('/{id}', name: 'app_facture_delete', methods: ['POST'])]
    public function delete(Request $request, Facture $facture, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$facture->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($facture);
            $entityManager->flush();

            $this->addFlash('success', 'Facture supprimée avec succès.');
        }

        return $this->redirectToRoute('app_facture_index', [], Response::HTTP_SEE_OTHER);
    }
}
