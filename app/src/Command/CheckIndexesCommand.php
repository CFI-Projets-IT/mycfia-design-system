<?php

declare(strict_types=1);

namespace App\Command;

use Doctrine\DBAL\Connection;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:check-indexes',
    description: 'Vérifie que tous les index de performance sont bien créés',
)]
class CheckIndexesCommand extends Command
{
    public function __construct(
        private readonly Connection $connection,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('Vérification des index de performance');

        $tables = ['campaign', 'message', 'conversation', 'ai_message', 'ai_log'];

        $expectedIndexes = [
            'campaign' => ['PRIMARY', 'idx_campaign_user_status', 'idx_campaign_dates'],
            'message' => ['PRIMARY', 'idx_message_campaign_status', 'idx_message_sent_at'],
            'conversation' => ['PRIMARY', 'idx_conversation_user_status', 'idx_conversation_updated'],
            'ai_message' => ['PRIMARY', 'idx_ai_message_conversation'],
            'ai_log' => ['PRIMARY', 'idx_ai_log_user_created', 'idx_ai_log_correlation'],
        ];

        $allOk = true;

        foreach ($tables as $table) {
            $io->section("Table : {$table}");

            $stmt = $this->connection->executeQuery("SHOW INDEX FROM {$table}");
            $indexes = $stmt->fetchAllAssociative();

            $indexNames = array_unique(array_column($indexes, 'Key_name'));

            $io->writeln('Index trouvés : '.count($indexNames));

            foreach ($indexNames as $indexName) {
                $expected = \in_array($indexName, $expectedIndexes[$table], true);
                $status = $expected ? '✅' : '⚠️ ';
                $io->writeln("  {$status} {$indexName}");
            }

            // Vérifier index manquants
            $missing = array_diff($expectedIndexes[$table], $indexNames);
            if (! empty($missing)) {
                $io->error('Index manquants : '.implode(', ', $missing));
                $allOk = false;
            } else {
                $io->success('Tous les index sont présents');
            }
        }

        if ($allOk) {
            $io->success('✅ Tous les index de performance sont bien créés');

            return Command::SUCCESS;
        }

        $io->error('❌ Certains index sont manquants');

        return Command::FAILURE;
    }
}
