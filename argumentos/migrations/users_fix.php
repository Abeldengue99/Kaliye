<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $required = ['profile_pic', 'academic_info'];
    foreach ($required as $col) {
        if (!in_array($col, $columns)) {
            $db->exec("ALTER TABLE users ADD COLUMN $col TEXT");
            echo "Added $col to users. ";
        } else {
            echo "$col already exists. ";
        }
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
