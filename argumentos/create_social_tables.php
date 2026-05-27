<?php
// processos/create_social_tables.php
// Cria as tabelas social_likes e social_comments

require_once dirname(__DIR__) . '/configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

echo "=== CRIANDO TABELAS SOCIAIS ===\n\n";

try {
    // 1. Criar social_likes
    $sql_likes = "CREATE TABLE IF NOT EXISTS `social_likes` (
        `like_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `project_id` INT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY `unique_user_project_like` (`user_id`, `project_id`),
        KEY `idx_project_likes` (`project_id`),
        CONSTRAINT `fk_likes_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_likes_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql_likes);
    echo "âœ… Tabela 'social_likes' verificada/criada.\n";
    
    // 2. Criar social_comments
    $sql_comments = "CREATE TABLE IF NOT EXISTS `social_comments` (
        `comment_id` INT AUTO_INCREMENT PRIMARY KEY,
        `project_id` INT NOT NULL,
        `user_id` INT NOT NULL,
        `content` TEXT NOT NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        KEY `idx_project_comments` (`project_id`),
        CONSTRAINT `fk_comments_project` FOREIGN KEY (`project_id`) REFERENCES `projects` (`project_id`) ON DELETE CASCADE,
        CONSTRAINT `fk_comments_user` FOREIGN KEY (`user_id`) REFERENCES `users` (`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";
    
    $db->exec($sql_comments);
    echo "âœ… Tabela 'social_comments' verificada/criada.\n";
    
} catch (PDOException $e) {
    echo "âŒ Erro ao criar tabelas: " . $e->getMessage() . "\n";
    exit(1);
}

echo "\nFim da migração.\n";

