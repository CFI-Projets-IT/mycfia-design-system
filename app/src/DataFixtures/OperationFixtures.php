<?php

namespace App\DataFixtures;

use App\Entity\Operation;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class OperationFixtures extends Fixture
{
    public function load(ObjectManager $manager): void
    {
        $operations = [
            ['Campagne Email Janvier', 'Envoi newsletter mensuelle', 'completed'],
            ['Campagne SMS Promotion', 'SMS promotionnel soldes hiver', 'in_progress'],
            ['Campagne WhatsApp Info', 'Information nouveaux produits', 'pending'],
            ['Campagne Email Relance', 'Relance panier abandonné', 'completed'],
            ['Campagne Push Notification', 'Notification application mobile', 'in_progress'],
            ['Campagne Email Bienvenue', 'Email de bienvenue nouveaux clients', 'completed'],
            ['Campagne SMS Rappel', 'Rappel rendez-vous', 'pending'],
            ['Campagne Email Fidélité', 'Programme de fidélité', 'in_progress'],
            ['Campagne WhatsApp Support', 'Support client via WhatsApp', 'completed'],
            ['Campagne Multi-canal', 'Campagne cross-canal Q1', 'pending'],
        ];

        foreach ($operations as $index => $data) {
            $operation = new Operation();
            $operation->setTitle($data[0]);
            $operation->setDescription($data[1]);
            $operation->setStatus($data[2]);
            $operation->setCreatedAt(new \DateTime(sprintf('-%d days', $index)));

            $manager->persist($operation);
        }

        $manager->flush();
    }
}
