<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

try {
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_comments_v2 (
            comment_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            parent_id INT DEFAULT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
    echo "Tabela project_comments_v2 criada/verificada com sucesso.";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage();
}
?>
