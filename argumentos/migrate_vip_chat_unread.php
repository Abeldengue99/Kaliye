<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();
    
    // Adicionar unread_count para podermos notificar em tempo real
    $db->exec("ALTER TABLE vip_chat_participants ADD COLUMN IF NOT EXISTS unread_count INTEGER DEFAULT 0");

    echo "Tabela vip_chat_participants atualizada com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro na atualizacao da tabela: " . $e->getMessage() . "\n";
}
?>
