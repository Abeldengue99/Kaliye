<?php
/**
 * sync_database.php
 * Sincroniza dados do banco de dados
 * Reconstrói índices, verifica integridade referencial, atualiza estatísticas
 */

require_once '../configuracoes/base_dados.php';

echo "<pre style='background: #0f172a; color: #10b981; padding: 20px; font-family: monospace; border-radius: 5px; margin: 20px;'>";
echo "🔄 SINCRONIZAÇÃO DE BANCO DE DADOS\n";
echo "=".str_repeat("=", 80)."\n\n";

$mode = $_GET['mode'] ?? 'report'; // report, rebuild, analyze, repair, all
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($mode !== 'report' && !$confirmed) {
    echo "⚠️  Use ?confirm=yes para executar as operações de sincronização.\n";
    echo "   Operações disponíveis: rebuild, analyze, repair, all\n";
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // ========================================================================
    // 1. ANÁLISE DE INTEGRIDADE
    // ========================================================================
    echo "1️⃣  ANÁLISE DE INTEGRIDADE\n";
    echo str_repeat("-", 80) . "\n\n";
    
    // Tabelas críticas
    $tables = [
        'users',
        'mentor_chat_groups',
        'mentor_group_members',
        'mentorship_contracts',
        'projects',
        'project_applications',
        'notifications',
    ];
    
    $stats = [];
    foreach ($tables as $table) {
        try {
            $exists = $db->query("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = 'public' AND table_name = '$table'
                )
            ")->fetchColumn();
            
            if ($exists) {
                $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
                $stats[$table] = [
                    'exists' => true,
                    'count' => $count,
                ];
                echo "✅ $table: $count registos\n";
            } else {
                $stats[$table] = ['exists' => false, 'count' => 0];
                echo "⚠️  $table: AUSENTE\n";
            }
        } catch (Exception $e) {
            $stats[$table] = ['exists' => false, 'error' => $e->getMessage()];
            echo "❌ $table: ERRO - " . $e->getMessage() . "\n";
        }
    }
    
    echo "\n";
    
    // ========================================================================
    // 2. VERIFICAÇÃO DE FOREIGN KEYS
    // ========================================================================
    echo "2️⃣  VERIFICAÇÃO DE FOREIGN KEYS\n";
    echo str_repeat("-", 80) . "\n\n";
    
    $fks = $db->query("
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
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    echo count($fks) . " Foreign Keys encontradas:\n";
    foreach ($fks as $fk) {
        echo "  • {$fk['table_name']}.{$fk['column_name']} → {$fk['foreign_table_name']}.{$fk['foreign_column_name']}\n";
    }
    
    // Verificar orfãos
    echo "\n  Procurando por registos órfãos...\n";
    $orphans_found = 0;
    
    // Exemplo: Procurar users orfãos em mentor_chat_groups
    try {
        $orphans = $db->query("
            SELECT COUNT(*) FROM mentor_chat_groups mg
            WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.user_id = mg.mentor_id)
        ")->fetchColumn();
        
        if ($orphans > 0) {
            echo "  ⚠️  mentor_chat_groups: $orphans registos órfãos encontrados\n";
            $orphans_found += $orphans;
            
            if ($mode !== 'report' && $confirmed) {
                echo "     Removendo registos órfãos...\n";
                $deleted = $db->exec("
                    DELETE FROM mentor_chat_groups 
                    WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.user_id = mentor_id)
                ");
                echo "     ✅ $deleted registos removidos\n";
            }
        } else {
            echo "  ✅ mentor_chat_groups: Sem registos órfãos\n";
        }
    } catch (Exception $e) {
        echo "  ℹ️  mentor_chat_groups: Tabela ausente ou erro\n";
    }
    
    if ($orphans_found === 0) {
        echo "\n  ✅ Nenhum registo órfão encontrado!\n";
    }
    
    echo "\n";
    
    // ========================================================================
    // 3. ANÁLISE DE ÍNDICES
    // ========================================================================
    echo "3️⃣  ANÁLISE DE ÍNDICES\n";
    echo str_repeat("-", 80) . "\n\n";
    
    try {
        $indexes = $db->query("
            SELECT 
                schemaname,
                tablename,
                indexname
            FROM pg_indexes
            WHERE schemaname = 'public'
            ORDER BY tablename, indexname
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        echo count($indexes) . " Índices encontrados:\n";
        $index_by_table = [];
        foreach ($indexes as $idx) {
            $index_by_table[$idx['tablename']][] = $idx['indexname'];
        }
        
        foreach ($index_by_table as $table => $idxs) {
            echo "  • $table: " . count($idxs) . " índices\n";
        }
        
        if ($mode === 'rebuild' && $confirmed) {
            echo "\n  🔧 Reconstruindo índices...\n";
            foreach ($indexes as $idx) {
                if (strpos($idx['indexname'], 'pk_') === false) {
                    try {
                        $db->exec("REINDEX INDEX {$idx['indexname']}");
                        echo "     ✅ {$idx['indexname']}\n";
                    } catch (Exception $e) {
                        echo "     ⚠️  {$idx['indexname']}: " . $e->getMessage() . "\n";
                    }
                }
            }
            echo "  ✅ Reconstrução de índices completa!\n";
        }
    } catch (Exception $e) {
        echo "  ℹ️  Erro ao listar índices\n";
    }
    
    echo "\n";
    
    // ========================================================================
    // 4. LIMPEZA E OTIMIZAÇÃO
    // ========================================================================
    echo "4️⃣  LIMPEZA E OTIMIZAÇÃO\n";
    echo str_repeat("-", 80) . "\n\n";
    
    if ($mode === 'analyze' && $confirmed) {
        echo "  🔧 Analisando tabelas...\n";
        foreach ($tables as $table) {
            try {
                $db->exec("ANALYZE $table");
                echo "     ✅ $table\n";
            } catch (Exception $e) {
                echo "     ⚠️  $table: Tabela ausente\n";
            }
        }
        echo "  ✅ Análise completa!\n";
    } else {
        echo "  ℹ️  Use ?mode=analyze&confirm=yes para analisar tabelas\n";
    }
    
    echo "\n";
    
    // ========================================================================
    // 5. RELATÓRIO E PRÓXIMAS ETAPAS
    // ========================================================================
    echo "5️⃣  RELATÓRIO FINAL\n";
    echo str_repeat("=", 80) . "\n\n";
    
    echo "Resumo:\n";
    echo "  • Tabelas verificadas: " . count($stats) . "\n";
    echo "  • Foreign Keys: " . count($fks) . "\n";
    echo "  • Registos órfãos encontrados: $orphans_found\n";
    
    echo "\n";
    if ($mode === 'report') {
        echo "Próximos passos:\n";
        echo "  1. Remover registos órfãos:  ?mode=repair&confirm=yes\n";
        echo "  2. Reconstruir índices:      ?mode=rebuild&confirm=yes\n";
        echo "  3. Analisar tabelas:         ?mode=analyze&confirm=yes\n";
        echo "  4. Fazer tudo:               ?mode=all&confirm=yes\n";
    } else if ($confirmed) {
        echo "✅ SINCRONIZAÇÃO COMPLETA!\n";
        echo "\nVerifique:\n";
        echo "  • Navegador - Recarregue (Ctrl+F5)\n";
        echo "  • Chat - Teste carregamento de mentorados\n";
        echo "  • Logs - Verifique erros em argumentos/debug/\n";
    }
    
    echo "\n";
    
} catch (Exception $e) {
    echo "❌ ERRO CRÍTICO: " . $e->getMessage() . "\n";
    echo "\nStack trace:\n";
    echo $e->getTraceAsString();
}

echo "</pre>";
?>
