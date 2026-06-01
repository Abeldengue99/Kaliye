<?php
/**
 * migration_mentor_groups.php
 * AUTO-MIGRATION: Garante que a tabela mentor_chat_groups tem todas as colunas necessárias
 * Executado automaticamente por cabecalho.php
 */

if (!isset($db)) {
    return; // Não executar se $db não estiver disponível
}

try {
    // Usar PL/pgSQL para tudo (mais robusto e consistente)
    $db->exec("
        DO $$
        BEGIN
            -- Criar tabela se não existir
            IF NOT EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_name = 'mentor_chat_groups'
            ) THEN
                CREATE TABLE mentor_chat_groups (
                    id SERIAL PRIMARY KEY,
                    mentor_id INTEGER NOT NULL UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    group_image VARCHAR(500),
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
                );
            END IF;
            
            -- Adicionar coluna group_image se não existir
            IF NOT EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_name = 'mentor_chat_groups' AND column_name = 'group_image'
            ) THEN
                ALTER TABLE mentor_chat_groups ADD COLUMN group_image VARCHAR(500);
            END IF;
        END $$;
    ");
    
} catch (Exception $e) {
    // Silenciar erros de migração
    error_log("Migration warning: " . $e->getMessage());
}
?>
