<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS terms TEXT AFTER currency");
    echo "Column 'terms' added successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
