<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();
    
    // Tabela 1: As salas VIP (grupos fechados)
    $db->exec("
        CREATE TABLE IF NOT EXISTS vip_chats (
            id SERIAL PRIMARY KEY,
            admin_creator_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            title VARCHAR(150) NOT NULL,
            description TEXT,
            status VARCHAR(20) DEFAULT 'active',
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    // Tabela 2: Participantes das salas
    $db->exec("
        CREATE TABLE IF NOT EXISTS vip_chat_participants (
            chat_id INTEGER NOT NULL REFERENCES vip_chats(id) ON DELETE CASCADE,
            user_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            role_added_as VARCHAR(50),
            added_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (chat_id, user_id)
        );
    ");

    // Tabela 3: Mensagens das salas VIP
    $db->exec("
        CREATE TABLE IF NOT EXISTS vip_chat_messages (
            id SERIAL PRIMARY KEY,
            chat_id INTEGER NOT NULL REFERENCES vip_chats(id) ON DELETE CASCADE,
            sender_id INTEGER NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
            message_text TEXT NOT NULL,
            sent_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");

    echo "Tabelas do VIP Chat criadas com sucesso!\n";
} catch (PDOException $e) {
    echo "Erro na criacao das tabelas: " . $e->getMessage() . "\n";
}
?>
