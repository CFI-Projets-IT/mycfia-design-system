<?php

namespace App\DataFixtures;

use App\Entity\Stock;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class StockFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $stocks = [
            ['Serveur Dell PowerEdge', 150, '2500.00'],
            ['Ordinateur Portable HP', 85, '899.99'],
            ['Écran Samsung 27"', 200, '349.00'],
            ['Clavier Mécanique Logitech', 120, '129.99'],
            ['Souris Sans Fil Microsoft', 250, '45.50'],
            ['Imprimante Canon Laser', 45, '299.00'],
            ['Switch Réseau Cisco 24 ports', 30, '1850.00'],
            ['Disque Dur Externe 2TB', 180, '89.99'],
            ['Webcam HD Logitech', 95, '79.99'],
            ['Casque Audio Sony', 140, '159.00'],
        ];

        foreach ($stocks as $index => $data) {
            $stock = new Stock();
            $stock->setName($data[0]);
            $stock->setQuantity($data[1]);
            $stock->setUnitPrice($data[2]);
            $stock->setCreatedAt(new \DateTime(sprintf('-%d days', $index * 2)));

            $manager->persist($stock);
        }

        $manager->flush();
    }
}
