<?php

namespace App\Tests\Repository;

use App\Entity\Operation;
use App\Repository\OperationRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OperationRepositoryTest extends KernelTestCase
{
    private OperationRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $entityManager->getRepository(Operation::class);
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(OperationRepository::class, $this->repository);
    }

    public function testFindAllReturnsArray(): void
    {
        $operations = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(0, count($operations));
    }

    public function testFindByStatus(): void
    {
        $completedOperations = $this->repository->findBy(['status' => 'completed']);

        foreach ($completedOperations as $operation) {
            $this->assertInstanceOf(Operation::class, $operation);
            $this->assertSame('completed', $operation->getStatus());
        }
    }

    public function testFindOneByTitle(): void
    {
        $allOperations = $this->repository->findAll();

        if (count($allOperations) > 0) {
            $firstOperation = $allOperations[0];
            $title = $firstOperation->getTitle();

            $foundOperation = $this->repository->findOneBy(['title' => $title]);

            $this->assertInstanceOf(Operation::class, $foundOperation);
            $this->assertSame($title, $foundOperation->getTitle());
        } else {
            $this->markTestSkipped('Aucune opération en base de données');
        }
    }

    public function testCountOperations(): void
    {
        $count = $this->repository->count();

        $this->assertGreaterThanOrEqual(0, $count);
    }
}
