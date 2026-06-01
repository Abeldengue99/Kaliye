<?php
/**
 * FIX_MENTOR_GROUPS.php
 * Script de diagnóstico e reparo para mentor_chat_groups
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

echo "<pre style='background: #1e1e1e; color: #00ff00; padding: 20px; font-family: monospace; border-radius: 5px;'>";
echo "🔧 DIAGNÓSTICO E REPARO - MENTOR_CHAT_GROUPS\n";
echo "=".str_repeat("=", 80)."\n\n";

try {
    $db = (new Database())->getConnection();
    
    // 1. Verificar estrutura da tabela
    echo "1️⃣  Verificando estrutura da tabela mentor_chat_groups...\n";
    
    $columns = $db->query("
        SELECT column_name, data_type, is_nullable, column_default 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'mentor_chat_groups'
        ORDER BY ordinal_position
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($columns)) {
        echo "❌ TABELA NÃO EXISTE!\n\n";
        
        echo "2️⃣  Criando tabela mentor_chat_groups...\n";
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
        echo "✅ Tabela criada com sucesso!\n\n";
        
    } else {
        echo "✓ Tabela existe com as seguintes colunas:\n";
        foreach ($columns as $col) {
            $nullable = $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL';
            echo "  - {$col['column_name']}: {$col['data_type']} ({$nullable})\n";
        }
        echo "\n";
        
        // Verificar se coluna id existe
        $has_id = array_filter($columns, fn($c) => $c['column_name'] === 'id');
        
        if (empty($has_id)) {
            echo "❌ COLUNA 'id' NÃO EXISTE! Adicionando...\n";
            
            // Primeiro, vamos tentar adicionar a coluna de forma segura
            // Para PostgreSQL, precisamos fazer isso com cuidado
            
            // Opção 1: Se a tabela estiver vazia, podemos recriar
            $count = $db->query("SELECT COUNT(*) FROM mentor_chat_groups")->fetchColumn();
            
            if ($count == 0) {
                echo "  (Tabela está vazia, recriando com coluna id...)\n";
                
                // Drop e recriar
                $db->exec("DROP TABLE IF EXISTS mentor_chat_groups CASCADE");
                $db->exec("CREATE TABLE mentor_chat_groups (
                    id SERIAL PRIMARY KEY,
                    mentor_id INTEGER NOT NULL UNIQUE,
                    name VARCHAR(255) NOT NULL,
                    description TEXT,
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
                )");
                echo "✅ Tabela recriada com coluna 'id'!\n\n";
                
            } else {
                echo "  (Tabela tem dados, adicionando coluna de forma segura...)\n";
                
                // Adicionar a sequência
                $db->exec("CREATE SEQUENCE mentor_chat_groups_id_seq");
                
                // Adicionar coluna
                $db->exec("ALTER TABLE mentor_chat_groups 
                    ADD COLUMN id INTEGER DEFAULT nextval('mentor_chat_groups_id_seq') NOT NULL");
                
                // Fazer id PRIMARY KEY
                $db->exec("ALTER TABLE mentor_chat_groups 
                    ADD PRIMARY KEY (id)");
                
                // Fazer a sequência propriedade da coluna
                $db->exec("ALTER SEQUENCE mentor_chat_groups_id_seq OWNED BY mentor_chat_groups.id");
                
                echo "✅ Coluna 'id' adicionada com sucesso!\n\n";
            }
        } else {
            echo "✓ Coluna 'id' já existe. Status OK!\n\n";
        }
        
        // Verificar se coluna group_image existe
        echo "Verificando coluna 'group_image'...\n";
        $has_group_image = array_filter($columns, fn($c) => $c['column_name'] === 'group_image');
        
        if (empty($has_group_image)) {
            echo "❌ COLUNA 'group_image' NÃO EXISTE! Adicionando...\n";
            $db->exec("ALTER TABLE mentor_chat_groups 
                ADD COLUMN group_image VARCHAR(500)");
            echo "✅ Coluna 'group_image' adicionada com sucesso!\n\n";
        } else {
            echo "✓ Coluna 'group_image' já existe. Status OK!\n\n";
        }
    }
    
    // 3. Criar tabelas dependentes
    echo "3️⃣  Verificando tabelas dependentes...\n\n";
    
    $tables = [
        'mentor_group_members' => "CREATE TABLE mentor_group_members (
            id SERIAL PRIMARY KEY,
            group_id INTEGER NOT NULL,
            user_id INTEGER NOT NULL,
            role VARCHAR(20) DEFAULT 'member',
            joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
            UNIQUE(group_id, user_id)
        )",
        
        'mentor_group_messages' => "CREATE TABLE mentor_group_messages (
            id SERIAL PRIMARY KEY,
            group_id INTEGER NOT NULL,
            sender_id INTEGER NOT NULL,
            message TEXT,
            message_type VARCHAR(20) DEFAULT 'text',
            file_url VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        
        'mentor_group_meetings' => "CREATE TABLE mentor_group_meetings (
            id SERIAL PRIMARY KEY,
            group_id INTEGER NOT NULL,
            mentor_id INTEGER NOT NULL,
            title VARCHAR(255),
            scheduled_at TIMESTAMP,
            meet_link VARCHAR(500),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
            FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
        )",
        
        'mentor_group_resources' => "CREATE TABLE mentor_group_resources (
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
        )"
    ];
    
    foreach ($tables as $table_name => $create_sql) {
        $exists = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_name = '$table_name'
        )")->fetchColumn();
        
        if ($exists) {
            echo "  ✓ Tabela '$table_name' existe\n";
        } else {
            echo "  ❌ Tabela '$table_name' não existe. Criando...\n";
            $db->exec($create_sql);
            echo "  ✅ Tabela '$table_name' criada!\n";
        }
    }
    
    echo "\n✅ REPARO CONCLUÍDO COM SUCESSO!\n";
    echo "=".str_repeat("=", 80)."\n";
    echo "Agora você pode criar turmas sem erros!\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
    echo "Classe: " . get_class($e) . "\n";
    if (method_exists($e, 'errorInfo')) {
        echo "Info: " . json_encode($e->errorInfo()) . "\n";
    }
}

echo "</pre>";
?>
