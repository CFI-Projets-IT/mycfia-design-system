<?php

namespace App\Tests\Repository;

use App\Entity\Facture;
use App\Repository\FactureRepository;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class FactureRepositoryTest extends KernelTestCase
{
    private FactureRepository $repository;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = self::getContainer()->get('doctrine')->getManager();
        $this->repository = $entityManager->getRepository(Facture::class);
    }

    public function testRepositoryExists(): void
    {
        $this->assertInstanceOf(FactureRepository::class, $this->repository);
    }

    public function testFindAllReturnsArray(): void
    {
        $factures = $this->repository->findAll();

        $this->assertGreaterThanOrEqual(0, count($factures));
    }

    public function testFindByStatus(): void
    {
        $paidFactures = $this->repository->findBy(['status' => 'paid']);

        foreach ($paidFactures as $facture) {
            $this->assertInstanceOf(Facture::class, $facture);
            $this->assertSame('paid', $facture->getStatus());
        }
    }

    public function testFindOneByReference(): void
    {
        $allFactures = $this->repository->findAll();

        if (count($allFactures) > 0) {
            $firstFacture = $allFactures[0];
            $reference = $firstFacture->getReference();

            $foundFacture = $this->repository->findOneBy(['reference' => $reference]);

            $this->assertInstanceOf(Facture::class, $foundFacture);
            $this->assertSame($reference, $foundFacture->getReference());
        } else {
            $this->markTestSkipped('Aucune facture en base de données');
        }
    }

    public function testCountFactures(): void
    {
        $count = $this->repository->count();

        $this->assertGreaterThanOrEqual(0, $count);
    }

    public function testFactureHasValidAmount(): void
    {
        $allFactures = $this->repository->findAll();

        foreach ($allFactures as $facture) {
            $this->assertInstanceOf(Facture::class, $facture);
            $this->assertMatchesRegularExpression('/^\d+(\.\d{1,2})?$/', $facture->getTotalAmount());
        }
    }

    public function testFactureStatusIsValid(): void
    {
        $validStatuses = ['paid', 'unpaid', 'pending'];
        $allFactures = $this->repository->findAll();

        foreach ($allFactures as $facture) {
            $this->assertContains($facture->getStatus(), $validStatuses);
        }
    }
}
