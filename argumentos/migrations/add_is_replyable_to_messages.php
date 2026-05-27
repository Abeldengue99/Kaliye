<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    $db->exec("ALTER TABLE messages ADD COLUMN is_replyable TINYINT(1) DEFAULT 1");
    echo "Column 'is_replyable' added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
