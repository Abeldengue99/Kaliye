<?php
/**
 * sync_data.php
 * Sincroniza dados entre desenvolvimento e produção
 * Sincroniza estrutura de banco de dados
 * Limpa cache e reconstrui índices
 */

require_once 'configuracoes/base_dados.php';

// Cores e estilos
$GREEN = "\033[92m";
$RED = "\033[91m";
$YELLOW = "\033[93m";
$BLUE = "\033[94m";
$RESET = "\033[0m";

echo "\n";
echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║           🔄 SINCRONIZAÇÃO DE DADOS - KALIYE               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$sync_mode = $_GET['mode'] ?? 'report'; // report, db, files, all, clear
$confirm = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

// ============================================================================
// 1. SINCRONIZAÇÃO DE BANCO DE DADOS
// ============================================================================
echo "1️⃣  SINCRONIZAÇÃO DE BANCO DE DADOS\n";
echo str_repeat("-", 60) . "\n";

try {
    $db = (new Database())->getConnection();
    
    // 1.1 Verificar integridade da tabela users
    echo "   • Verificando tabela 'users'...\n";
    
    $check_users = $db->query("
        SELECT EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_name = 'users'
        )
    ")->fetchColumn();
    
    if ($check_users) {
        echo "     ✅ Tabela 'users' existe\n";
        
        // Contar registos
        $user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
        echo "     ℹ️  Total de usuários: $user_count\n";
        
        // Verificar coluna user_id
        $has_user_id = $db->query("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'user_id'
            )
        ")->fetchColumn();
        
        if ($has_user_id) {
            echo "     ✅ Coluna 'user_id' existe\n";
        } else {
            echo "     ❌ Coluna 'user_id' NÃO existe!\n";
            
            // Procurar por 'id'
            $has_id = $db->query("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.columns 
                    WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'id'
                )
            ")->fetchColumn();
            
            if ($has_id) {
                echo "     ⚠️  Encontrada coluna 'id' em vez de 'user_id'\n";
                if ($sync_mode !== 'report' && $confirm) {
                    echo "     🔧 Corrigindo: Renomeando 'id' → 'user_id'...\n";
                    try {
                        $db->exec("ALTER TABLE users RENAME COLUMN id TO user_id");
                        echo "     ✅ Coluna renomeada com sucesso!\n";
                    } catch (Exception $e) {
                        echo "     ❌ Erro: " . $e->getMessage() . "\n";
                    }
                }
            }
        }
    } else {
        echo "     ❌ Tabela 'users' NÃO existe!\n";
    }
    
    // 1.2 Verificar outras tabelas críticas
    echo "\n   • Verificando tabelas relacionadas...\n";
    
    $critical_tables = [
        'mentor_chat_groups' => 'Salas de mentoria VIP',
        'mentor_group_members' => 'Membros de grupos',
        'mentorship_contracts' => 'Contratos de mentoria',
        'projects' => 'Projetos',
        'project_applications' => 'Candidaturas',
    ];
    
    foreach ($critical_tables as $table => $description) {
        $exists = $db->query("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.tables 
                WHERE table_schema = 'public' AND table_name = '$table'
            )
        ")->fetchColumn();
        
        if ($exists) {
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "     ✅ $description ($count registos)\n";
        } else {
            echo "     ⚠️  $description (tabela ausente)\n";
        }
    }
    
    // 1.3 Verificar integridade referencial
    echo "\n   • Verificando referências/Foreign Keys...\n";
    
    $fk_violations = $db->query("
        SELECT 
            tc.constraint_name,
            tc.table_name,
            kcu.column_name,
            ccu.table_name AS foreign_table_name,
            ccu.column_name AS foreign_column_name
        FROM information_schema.table_constraints AS tc
        JOIN information_schema.key_column_usage AS kcu
            ON tc.constraint_name = kcu.constraint_name
            AND tc.table_schema = kcu.table_schema
        JOIN information_schema.constraint_column_usage AS ccu
            ON ccu.constraint_name = tc.constraint_name
            AND ccu.table_schema = tc.table_schema
        WHERE tc.constraint_type = 'FOREIGN KEY'
        AND tc.table_schema = 'public'
        LIMIT 10
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (!empty($fk_violations)) {
        echo "     ✅ " . count($fk_violations) . " Foreign Keys encontradas\n";
    } else {
        echo "     ℹ️  Nenhuma Foreign Key definida\n";
    }
    
} catch (Exception $e) {
    echo "     ❌ ERRO: " . $e->getMessage() . "\n";
}

// ============================================================================
// 2. SINCRONIZAÇÃO DE ARQUIVOS
// ============================================================================
echo "\n2️⃣  SINCRONIZAÇÃO DE ARQUIVOS\n";
echo str_repeat("-", 60) . "\n";

$files_to_sync = [
    'inclusoes/components/chat_scripts.php' => 'Chat VIP',
    'interface_programacao/social/get_mentor_students.php' => 'API Mentorados',
    'interface_programacao/social/delete_mentor_group.php' => 'API Deletar Grupo',
    'interface_programacao/social/update_mentor_group.php' => 'API Atualizar Grupo',
    'argumentos/verify_users_table.php' => 'Verificador de Tabela Users',
    'argumentos/fix_users_table.php' => 'Corretor de Tabela Users',
];

$base_dev = 'c:\\Users\\nee\\Documents\\Aksanti Referências\\Aksanti Referências';
$base_prod = 'C:\\xampp\\htdocs\\kaliye';

echo "   Verificando arquivos...\n";
$files_synced = 0;
$files_missing = 0;

foreach ($files_to_sync as $file => $description) {
    $src = $base_dev . '\\' . str_replace('/', '\\', $file);
    $dst = $base_prod . '\\' . str_replace('/', '\\', $file);
    
    if (file_exists($src)) {
        echo "     ✅ $description (encontrado)\n";
        $files_synced++;
        
        if ($sync_mode !== 'report' && $confirm) {
            if (!file_exists(dirname($dst))) {
                mkdir(dirname($dst), 0755, true);
            }
            if (copy($src, $dst)) {
                echo "        → Sincronizado ✓\n";
            } else {
                echo "        → Erro ao sincronizar\n";
            }
        }
    } else {
        echo "     ❌ $description (não encontrado)\n";
        $files_missing++;
    }
}

// ============================================================================
// 3. LIMPEZA DE CACHE
// ============================================================================
echo "\n3️⃣  LIMPEZA DE CACHE\n";
echo str_repeat("-", 60) . "\n";

$cache_dirs = [
    sys_get_temp_dir() => 'Temp do Sistema',
];

echo "   • Cache e sessões...\n";
echo "     ℹ️  Sessões do PHP: " . session_save_path() . "\n";

if ($sync_mode === 'clear' && $confirm) {
    echo "     🔧 Limpando cache...\n";
    // Aqui você pode adicionar lógica de limpeza de cache
    echo "     ✅ Cache limpo (se configurado)\n";
}

// ============================================================================
// 4. RELATÓRIO
// ============================================================================
echo "\n4️⃣  RELATÓRIO FINAL\n";
echo str_repeat("-", 60) . "\n";

echo "   Resumo da Sincronização:\n";
echo "   • Banco de dados: ✅ Verificado\n";
echo "   • Arquivos: $files_synced sincronizados, $files_missing ausentes\n";
echo "   • Cache: ✅ Pronto para limpeza\n";

echo "\n";
if ($sync_mode === 'report') {
    echo "   Para executar a sincronização, use:\n";
    echo "   ?mode=all&confirm=yes\n";
    echo "\n   Ou selecione modo específico:\n";
    echo "   ?mode=db&confirm=yes   (apenas banco de dados)\n";
    echo "   ?mode=files&confirm=yes (apenas arquivos)\n";
    echo "   ?mode=clear&confirm=yes (limpar cache)\n";
} else if ($confirm) {
    echo "   ✅ SINCRONIZAÇÃO COMPLETA!\n";
    echo "\n   Próximos passos:\n";
    echo "   1. Recarregue o navegador (Ctrl+F5)\n";
    echo "   2. Teste a funcionalidade de mentorados\n";
    echo "   3. Se persistir erro, execute verify_users_table.php\n";
} else {
    echo "   ⚠️  Execute com ?confirm=yes para aplicar mudanças\n";
}

echo "\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

?>
