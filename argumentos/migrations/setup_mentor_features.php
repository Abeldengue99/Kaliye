<?php
// processos/setup_mentor_features.php
// Script to run the mentor features migration

require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sqlFile = __DIR__ . '/../base_dados/mentor_features_migration.sql';
    $sql = file_get_contents($sqlFile);
    
    // Split combined SQL into individual statements
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            echo "Executed: " . substr($statement, 0, 50) . "...\n";
        }
    }
    
    echo "\nSuccess: Mentor features database schema updated successfully!\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

