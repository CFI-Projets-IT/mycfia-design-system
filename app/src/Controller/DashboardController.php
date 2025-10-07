<?php

namespace App\Controller;

use App\Repository\FactureRepository;
use App\Repository\OperationRepository;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        OperationRepository $operationRepo,
        StockRepository $stockRepo,
        FactureRepository $factureRepo
    ): Response {
        $operations = $operationRepo->findBy([], ['createdAt' => 'DESC'], 5);
        $stocks = $stockRepo->findBy([], ['quantity' => 'ASC'], 5);
        $factures = $factureRepo->findBy([], ['invoiceDate' => 'DESC'], 5);

        $stats = [
            'totalOperations' => $operationRepo->count(),
            'totalStock' => $stockRepo->count(),
            'totalFactures' => $factureRepo->count(),
            'pendingOperations' => $operationRepo->count(['status' => 'pending']),
        ];

        return $this->render('dashboard/index.html.twig', [
            'operations' => $operations,
            'stocks' => $stocks,
            'factures' => $factures,
            'stats' => $stats,
        ]);
    }
}
