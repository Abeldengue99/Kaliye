<?php
// migrate_elite_features.php
require_once 'configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. PROJECT MILESTONES
    $db->exec("CREATE TABLE IF NOT EXISTS project_milestones (
        milestone_id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        status ENUM('pending', 'in_progress', 'completed') DEFAULT 'pending',
        target_date DATE,
        completed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. ELITE STATUS & BADGES
    // Adding columns to users table to track elite status if not exists
    $db->exec("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS is_elite_mentor TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS is_visionary_investor TINYINT(1) DEFAULT 0;");

    echo "Elite ecosystem tables and columns created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
