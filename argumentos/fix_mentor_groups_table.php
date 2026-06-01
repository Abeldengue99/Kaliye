<?php
/**
 * fix_mentor_groups_table.php
 * Script de reparo para adicionar coluna 'id' à tabela mentor_chat_groups
 * Acesso: http://localhost/path/fix_mentor_groups_table.php
 */

// Verificação de segurança - apenas admin ou desenvolvedores
session_start();
if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] ?? '') !== 'admin') {
    // Permite acesso sem autenticação apenas uma vez para configuração inicial
    // Depois remove o comentário da verificação acima
    // echo "❌ Acesso negado! Apenas admins podem executar este script.\n";
    // exit();
}

require_once __DIR__ . '/configuracoes/base_dados.php';

// Usar header JSON para output estruturado
header('Content-Type: application/json; charset=utf-8');

$response = [
    'success' => false,
    'messages' => [],
    'errors' => []
];

try {
    $db = (new Database())->getConnection();
    
    $response['messages'][] = "🔍 Verificando tabela mentor_chat_groups...";
    
    // 1. Verificar se a tabela existe
    $table_check = $db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' 
        AND table_name = 'mentor_chat_groups'
    )")->fetchColumn();
    
    if (!$table_check) {
        $response['messages'][] = "❌ Tabela não existe. Criando...";
        
        // Criar tabela com coluna id
        $db->exec("CREATE TABLE mentor_chat_groups (
            id SERIAL PRIMARY KEY,
            mentor_id INTEGER NOT NULL UNIQUE,
            name VARCHAR(255) NOT NULL,
            group_image VARCHAR(500),
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");
        
        $response['messages'][] = "✅ Tabela mentor_chat_groups criada com sucesso!";
        
    } else {
        $response['messages'][] = "✓ Tabela existe. Verificando coluna 'id'...";
        
        // 2. Verificar se coluna 'id' existe
        $column_check = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public'
            AND table_name = 'mentor_chat_groups' 
            AND column_name = 'id'
        )")->fetchColumn();
        
        if ($column_check) {
            $response['messages'][] = "✅ Coluna 'id' já existe. Tudo OK!";
        } else {
            $response['messages'][] = "❌ Coluna 'id' não existe. Adicionando...";
            
            // Adicionar coluna id como PRIMARY KEY
            // Primeiro adicionar como coluna SERIAL
            $db->exec("ALTER TABLE mentor_chat_groups 
                ADD COLUMN id SERIAL PRIMARY KEY");
            
            $response['messages'][] = "✅ Coluna 'id' adicionada com sucesso!";
        }
        
        // Verificar se coluna 'group_image' existe
        $response['messages'][] = "🔍 Verificando coluna 'group_image'...";
        $image_check = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public'
            AND table_name = 'mentor_chat_groups' 
            AND column_name = 'group_image'
        )")->fetchColumn();
        
        if ($image_check) {
            $response['messages'][] = "✅ Coluna 'group_image' já existe.";
        } else {
            $response['messages'][] = "❌ Coluna 'group_image' não existe. Adicionando...";
            
            $db->exec("ALTER TABLE mentor_chat_groups 
                ADD COLUMN group_image VARCHAR(500)");
            
            $response['messages'][] = "✅ Coluna 'group_image' adicionada com sucesso!";
        }
    }
    
    // 3. Verificar e criar tabelas dependentes
    $response['messages'][] = "";
    $response['messages'][] = "🔍 Verificando tabelas dependentes...";
    
    // mentor_group_members
    if (!$db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'mentor_group_members'
    )")->fetchColumn()) {
        $db->exec("CREATE TABLE mentor_group_members (
            id SERIAL PRIMARY KEY,
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role VARCHAR(20) DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE(group_id, user_id)
        )");
        $response['messages'][] = "✅ Tabela mentor_group_members criada";
    } else {
        $response['messages'][] = "✓ Tabela mentor_group_members existe";
    }
    
    // mentor_group_messages
    if (!$db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'mentor_group_messages'
    )")->fetchColumn()) {
        $db->exec("CREATE TABLE mentor_group_messages (
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
        $response['messages'][] = "✅ Tabela mentor_group_messages criada";
    } else {
        $response['messages'][] = "✓ Tabela mentor_group_messages existe";
    }
    
    // mentor_group_meetings
    if (!$db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'mentor_group_meetings'
    )")->fetchColumn()) {
        $db->exec("CREATE TABLE mentor_group_meetings (
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
        $response['messages'][] = "✅ Tabela mentor_group_meetings criada";
    } else {
        $response['messages'][] = "✓ Tabela mentor_group_meetings existe";
    }
    
    // mentor_group_resources
    if (!$db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_schema = 'public' AND table_name = 'mentor_group_resources'
    )")->fetchColumn()) {
        $db->exec("CREATE TABLE mentor_group_resources (
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
        $response['messages'][] = "✅ Tabela mentor_group_resources criada";
    } else {
        $response['messages'][] = "✓ Tabela mentor_group_resources existe";
    }
    
    $response['success'] = true;
    $response['messages'][] = "";
    $response['messages'][] = "✅ TODAS AS VERIFICAÇÕES CONCLUÍDAS COM SUCESSO!";
    $response['messages'][] = "💡 Agora você pode criar turmas/salas VIP sem erros.";
    
} catch (Exception $e) {
    $response['success'] = false;
    $response['errors'][] = "❌ Erro: " . $e->getMessage();
    $response['errors'][] = "Classe: " . get_class($e);
    if (method_exists($e, 'errorInfo')) {
        $response['errors'][] = "Info: " . json_encode($e->errorInfo());
    }
}

// Output JSON
echo json_encode($response, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
?>
