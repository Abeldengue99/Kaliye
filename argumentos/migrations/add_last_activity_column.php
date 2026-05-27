<?php
// add_last_activity_column.php
require_once 'configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    // Check if column exists
    $check = $db->query("SHOW COLUMNS FROM users LIKE 'last_activity'");
    if ($check->rowCount() == 0) {
        $db->exec("ALTER TABLE users ADD COLUMN last_activity DATETIME DEFAULT NULL");
        echo "Column 'last_activity' added successfully to 'users' table.";
    } else {
        echo "Column 'last_activity' already exists.";
    }
} catch (PDOException $e) {
    if (strpos($e->getMessage(), 'Duplicate column name') !== false) {
        echo "Column already exists (caught exception).";
    } else {
        echo "Error: " . $e->getMessage();
    }
}
?>
