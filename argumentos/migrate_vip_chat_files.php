<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();
    
    // Adicionar colunas para ficheiros
    $db->exec("ALTER TABLE vip_chat_messages ADD COLUMN IF NOT EXISTS file_path VARCHAR(500)");
    $db->exec("ALTER TABLE vip_chat_messages ADD COLUMN IF NOT EXISTS file_name VARCHAR(255)");
    $db->exec("ALTER TABLE vip_chat_messages ADD COLUMN IF NOT EXISTS file_type VARCHAR(100)");

    echo "Tabela vip_chat_messages atualizada com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro na atualizacao da tabela: " . $e->getMessage() . "\n";
}
?>
