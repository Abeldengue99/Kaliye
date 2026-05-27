<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    $db->exec("ALTER TABLE project_investments MODIFY COLUMN status ENUM('awaiting_payment', 'pending', 'approved', 'rejected', 'paid', 'cancelled') DEFAULT 'awaiting_payment'");
    echo "Status enum updated successfully.\n";
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
