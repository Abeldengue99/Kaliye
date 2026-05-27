<?php
require_once __DIR__ . '/../../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Instalando Sistema de Convites Estratégicos ===\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS institution_invitations (
        invitation_id INT AUTO_INCREMENT PRIMARY KEY,
        institution_id INT,
        email VARCHAR(255) NOT NULL,
        role ENUM('school_admin') DEFAULT 'school_admin',
        token VARCHAR(100) UNIQUE,
        status ENUM('pending', 'accepted', 'expired') DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (institution_id) REFERENCES institutions(institution_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✅ Tabela 'institution_invitations' criada.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
