<?php
require_once __DIR__ . '/../../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Instalando Sistema de Certificados de Mérito ===\n\n";
    
    $sql = "CREATE TABLE IF NOT EXISTS certificates (
        certificate_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        project_id INT,
        institution_id INT,
        certificate_type ENUM('merit', 'innovation', 'completion') DEFAULT 'merit',
        certificate_code VARCHAR(50) UNIQUE,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
        FOREIGN KEY (institution_id) REFERENCES institutions(institution_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql);
    echo "✅ Tabela 'certificates' criada com sucesso.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
