<?php

declare(strict_types=1);

namespace App\Command;

use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:sync-stocks',
    description: 'Synchronise les stocks depuis l\'API CFI (BDD Commune)',
)]
class SyncStocksCommand extends Command
{
    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->warning('Cette commande est un squelette et sera implémentée dans les Sprints S2/S3.');
        $io->info('Objectif : Synchroniser les stocks depuis la BDD CFI Commune vers MyCFiA.');

        // TODO Sprint S2/S3 : Implémenter la logique de synchronisation
        // 1. Récupérer timestamp dernière sync
        // 2. Appeler API CFI avec filtre updated_at > last_sync
        // 3. Upsert données en BDD MyCFiA
        // 4. Journaliser la sync (success/error counts)

        $io->success('Squelette créé avec succès.');

        return Command::SUCCESS;
    }
}
