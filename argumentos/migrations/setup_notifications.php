<?php
// processos/setup_notifications.php
require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $sqlFile = __DIR__ . '/../base_dados/notifications_migration.sql';
    $sql = file_get_contents($sqlFile);
    
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    foreach ($statements as $statement) {
        if (!empty($statement)) {
            $db->exec($statement);
            echo "Executed notification migration statement.\n";
        }
    }
    echo "Success: Notifications table created successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>

