<?php

require_once dirname(__DIR__).'/vendor/autoload.php';

use Symfony\Component\Dotenv\Dotenv;

// Charger les variables d'environnement
(new Dotenv())->bootEnv(dirname(__DIR__).'/.env');

// Tables à vérifier
$tables = ['campaign', 'message', 'conversation', 'ai_message', 'ai_log'];

// Index attendus par table
$expectedIndexes = [
    'campaign' => ['PRIMARY', 'idx_campaign_user_status', 'idx_campaign_dates', 'IDX_1F1512DDA76ED395', 'IDX_1F1512DD41859289'],
    'message' => ['PRIMARY', 'idx_message_campaign_status', 'idx_message_sent_at', 'IDX_B6BD307FF639F774'],
    'conversation' => ['PRIMARY', 'idx_conversation_user_status', 'idx_conversation_updated', 'IDX_8A8E26E9A76ED395'],
    'ai_message' => ['PRIMARY', 'idx_ai_message_conversation', 'IDX_8AB83EAC9AC0396'],
    'ai_log' => ['PRIMARY', 'idx_ai_log_user_created', 'idx_ai_log_correlation', 'IDX_558C643A76ED395'],
];

echo "=== Vérification des index de performance ===\n\n";

$databaseUrl = $_ENV['DATABASE_URL'] ?? '';
if (empty($databaseUrl)) {
    echo "❌ DATABASE_URL non définie\n";
    exit(1);
}

// Extraire les credentials
preg_match('/mysql:\/\/([^:]+):([^@]+)@([^:]+):(\d+)\/(.+)/', $databaseUrl, $matches);
if (count($matches) < 6) {
    echo "❌ Format DATABASE_URL invalide\n";
    exit(1);
}

[$full, $user, $password, $host, $port, $database] = $matches;

// Connexion PDO
try {
    $pdo = new PDO("mysql:host={$host};port={$port};dbname={$database}", $user, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "✅ Connexion BDD réussie : {$database}\n\n";

    foreach ($tables as $table) {
        echo "📊 Table : {$table}\n";
        $stmt = $pdo->query("SHOW INDEX FROM {$table}");
        $indexes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $indexNames = array_unique(array_column($indexes, 'Key_name'));

        echo '  Index trouvés : '.count($indexNames)."\n";
        foreach ($indexNames as $indexName) {
            $expected = in_array($indexName, $expectedIndexes[$table] ?? [], true);
            $status = $expected ? '✅' : '⚠️ ';
            echo "    {$status} {$indexName}\n";
        }

        // Vérifier les index manquants
        $missing = array_diff($expectedIndexes[$table] ?? [], $indexNames);
        if (! empty($missing)) {
            echo '  ❌ Index manquants : '.implode(', ', $missing)."\n";
        }

        echo "\n";
    }

    echo "=== Vérification terminée ===\n";

} catch (PDOException $e) {
    echo '❌ Erreur de connexion : '.$e->getMessage()."\n";
    exit(1);
}
