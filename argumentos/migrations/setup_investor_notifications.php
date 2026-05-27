<?php
// setup_investor_notifications.php
// Run this file once to create the investor_notifications table

require_once '../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

$sql = "CREATE TABLE IF NOT EXISTS investor_notifications (
    notification_id INT AUTO_INCREMENT PRIMARY KEY,
    investor_id INT NOT NULL,
    project_id INT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (investor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    INDEX idx_investor_read (investor_id, is_read),
    INDEX idx_created (created_at DESC)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";

try {
    $db->exec($sql);
    echo "✅ Tabela 'investor_notifications' criada com sucesso!";
} catch(PDOException $e) {
    echo "❌ Erro ao criar tabela: " . $e->getMessage();
}
?>
