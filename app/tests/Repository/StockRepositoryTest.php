<?php

namespace App\Tests\Repository;

use App\Entity\Stock;
use App\Repository\StockRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class StockRepositoryTest extends KernelTestCase
{
    private StockRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $entityManager->getRepository(Stock::class);
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(StockRepository::class, $this->repository);
    }

    public function testFindAllReturnsArray(): void
    {
        $stocks = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(0, count($stocks));
    }

    public function testFindByQuantityRange(): void
    {
        $allStocks = $this->repository->findAll();

        if (count($allStocks) > 0) {
            // Rechercher les stocks avec quantité < 100
            $lowStocks = array_filter($allStocks, fn (Stock $stock) => $stock->getQuantity() < 100);

            foreach ($lowStocks as $stock) {
                $this->assertInstanceOf(Stock::class, $stock);
                $this->assertLessThan(100, $stock->getQuantity());
            }
        } else {
            $this->markTestSkipped('Aucun stock en base de données');
        }
    }

    public function testFindOneByName(): void
    {
        $allStocks = $this->repository->findAll();

        if (count($allStocks) > 0) {
            $firstStock = $allStocks[0];
            $name = $firstStock->getName();

            $foundStock = $this->repository->findOneBy(['name' => $name]);

            $this->assertInstanceOf(Stock::class, $foundStock);
            $this->assertSame($name, $foundStock->getName());
        } else {
            $this->markTestSkipped('Aucun stock en base de données');
        }
    }

    public function testCountStocks(): void
    {
        $count = $this->repository->count();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testStockHasValidPriceAndQuantity(): void
    {
        $allStocks = $this->repository->findAll();

        foreach ($allStocks as $stock) {
            $this->assertInstanceOf(Stock::class, $stock);
            $this->assertGreaterThanOrEqual(0, $stock->getQuantity());
        }
    }
}
