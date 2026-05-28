<?php
require_once __DIR__ . '/configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();

    // 1. Adicionar content_hash na tabela projects
    $db->exec("ALTER TABLE projects ADD COLUMN IF NOT EXISTS content_hash VARCHAR(255) DEFAULT NULL;");

    // 2. Tabela de Log de Visualizações (Views Log)
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_views_log (
            view_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            viewer_id INT NOT NULL,
            ip_address VARCHAR(45) NULL,
            viewed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (project_id, viewer_id)
        );
    ");

    // 3. Tabela de NDAs Aceites
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_nda_logs (
            nda_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NULL,
            accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE (project_id, user_id)
        );
    ");

    echo "Tabelas de segurança criadas/verificadas com sucesso!\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
