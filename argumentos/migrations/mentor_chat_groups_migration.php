<?php
/**
 * MIGRAÇÃO: Tabelas de Salas VIP de Mentoria
 * Executa automaticamente ao incluir o arquivo
 */
require_once __DIR__ . '/../../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();
    
    // 1. Tabela Principal de Grupos
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_chat_groups (
        id SERIAL PRIMARY KEY,
        mentor_id INTEGER NOT NULL,
        name VARCHAR(255) NOT NULL,
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE(mentor_id)
    )");

    // 2. Membros do Grupo
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_group_members (
        id SERIAL PRIMARY KEY,
        group_id INTEGER NOT NULL,
        user_id INTEGER NOT NULL,
        role VARCHAR(20) DEFAULT 'member',
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE(group_id, user_id)
    )");

    // 3. Mensagens do Grupo
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_group_messages (
        id SERIAL PRIMARY KEY,
        group_id INTEGER NOT NULL,
        sender_id INTEGER NOT NULL,
        message TEXT,
        message_type VARCHAR(20) DEFAULT 'text',
        file_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // 4. Reuniões Jitsi
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_group_meetings (
        id SERIAL PRIMARY KEY,
        group_id INTEGER NOT NULL,
        mentor_id INTEGER NOT NULL,
        title VARCHAR(255),
        scheduled_at TIMESTAMP,
        meet_link VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // 5. Recursos/Materiais da Sala
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_group_resources (
        id SERIAL PRIMARY KEY,
        group_id INTEGER NOT NULL,
        mentor_id INTEGER NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        file_path VARCHAR(500),
        file_type VARCHAR(50),
        file_size INTEGER,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // 6. Adicionar índices para melhor performance
    try {
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mentor_chat_groups_mentor ON mentor_chat_groups(mentor_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mentor_group_members_group ON mentor_group_members(group_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mentor_group_messages_group ON mentor_group_messages(group_id)");
        $db->exec("CREATE INDEX IF NOT EXISTS idx_mentor_group_messages_sender ON mentor_group_messages(sender_id)");
    } catch (Exception $e) {
        // Índices podem já existir
    }

    error_log("✓ Migração de salas VIP de mentoria concluída com sucesso");

} catch (Exception $e) {
    error_log("✗ Erro ao executar migração de salas VIP: " . $e->getMessage());
    throw $e;
}

?>
