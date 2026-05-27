<?php
// processos/setup_group_chat.php
// Run this script once to set up group chat tables

require_once __DIR__ . '/../configuracoes/base_dados.php';

echo "Setting up Group Chat tables...\n\n";

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Read and execute migration file
    $sql = file_get_contents(__DIR__ . '/../base_dados/group_chat_migration.sql');
    
    // Split by semicolon and execute each statement
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            echo "âœ“ Executed: " . substr($statement, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Ignore "already exists" errors
            if (strpos($e->getMessage(), 'already exists') === false && 
                strpos($e->getMessage(), 'Duplicate column') === false) {
                throw $e;
            } else {
                echo "âš  Skipped (already exists): " . substr($statement, 0, 50) . "...\n";
            }
        }
    }
    
    echo "\nâœ… Group Chat setup completed successfully!\n";
    echo "\nYou can now:\n";
    echo "1. Create mentor groups\n";
    echo "2. Send group messages with media\n";
    echo "3. Share images and videos in chats\n";
    
} catch (Exception $e) {
    echo "\nâŒ Error: " . $e->getMessage() . "\n";
    exit(1);
}
?>

