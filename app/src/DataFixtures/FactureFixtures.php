<?php

namespace App\DataFixtures;

use App\Entity\Facture;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class FactureFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $factures = [
            ['FAC-2025-001', '1250.00', 'paid'],
            ['FAC-2025-002', '3450.50', 'paid'],
            ['FAC-2025-003', '890.00', 'unpaid'],
            ['FAC-2025-004', '2100.75', 'paid'],
            ['FAC-2025-005', '5600.00', 'pending'],
            ['FAC-2025-006', '1780.25', 'paid'],
            ['FAC-2025-007', '950.00', 'unpaid'],
            ['FAC-2025-008', '4320.90', 'paid'],
            ['FAC-2025-009', '2850.00', 'pending'],
            ['FAC-2025-010', '1540.50', 'paid'],
        ];

        foreach ($factures as $index => $data) {
            $facture = new Facture();
            $facture->setReference($data[0]);
            $facture->setTotalAmount($data[1]);
            $facture->setStatus($data[2]);
            $facture->setInvoiceDate(new \DateTime(sprintf('-%d days', $index * 3)));
            $facture->setCreatedAt(new \DateTime(sprintf('-%d days', $index * 3)));

            $manager->persist($facture);
        }

        $manager->flush();
    }
}
