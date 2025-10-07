<?php

namespace App\Controller;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/stocks')]
class StockController extends AbstractController
{
    #[Route('', name: 'app_stock_index', methods: ['GET'])]
    public function index(StockRepository $repository): Response
    {
        return $this->render('stock/index.html.twig', [
            'stocks' => $repository->findBy([], ['name' => 'ASC']),
        ]);
    }

    #[Route('/new', name: 'app_stock_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $stock = new Stock();

        return $this->render('stock/new.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_show', methods: ['GET'])]
    public function show(Stock $stock): Response
    {
        return $this->render('stock/show.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_stock_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        return $this->render('stock/edit.html.twig', [
            'stock' => $stock,
        ]);
    }

    #[Route('/{id}', name: 'app_stock_delete', methods: ['POST'])]
    public function delete(Request $request, Stock $stock, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$stock->getId(), (string) $request->request->get('_token'))) {
            $entityManager->remove($stock);
            $entityManager->flush();

            $this->addFlash('success', 'Stock supprimé avec succès.');
        }

        return $this->redirectToRoute('app_stock_index', [], Response::HTTP_SEE_OTHER);
    }
}
