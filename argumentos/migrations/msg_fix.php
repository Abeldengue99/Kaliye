<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Check if is_read column exists in messages table
    $stmt = $db->query("DESCRIBE messages");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (!in_array('is_read', $columns)) {
        $db->exec("ALTER TABLE messages ADD COLUMN is_read TINYINT(1) DEFAULT 0");
        echo "Column is_read added to messages table.";
    } else {
        echo "Column is_read already exists.";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
