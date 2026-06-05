<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();

    $db->exec("
        CREATE TABLE IF NOT EXISTS project_terms_signatures (
            signature_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            ip_address VARCHAR(45) NULL,
            terms_version VARCHAR(50) DEFAULT 'v1.0',
            accepted_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    echo "Tabela de assinaturas de termos criada com sucesso!\n";
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
