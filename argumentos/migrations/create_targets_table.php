<?php
require_once __DIR__ . '/../../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_item_targets (
        target_id INT AUTO_INCREMENT PRIMARY KEY, 
        item_type ENUM('notice', 'resource') NOT NULL, 
        item_id INT NOT NULL, 
        student_id INT NOT NULL, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        INDEX (item_type, item_id), 
        INDEX (student_id)
    )");
    echo "✅ Table mentorship_item_targets created successfully.\n";
} catch (PDOException $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
}
?>
