<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Check if System user exists
    $stmt = $db->query("SELECT user_id FROM users WHERE user_type = 'admin' AND full_name = 'Aksanti System'");
    $system_user = $stmt->fetch();
    
    if (!$system_user) {
        $db->prepare("INSERT INTO users (full_name, email, password_hash, user_type, is_verified, created_at) VALUES ('Aksanti System', 'system@aksanti.xyz', 'no-password', 'admin', 1, NOW())")
           ->execute();
        echo "System user created successfully.\n";
    } else {
        echo "System user already exists.\n";
    }
} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
