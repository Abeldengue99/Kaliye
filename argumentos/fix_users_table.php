<?php
/**
 * fix_users_table.php
 * Corrige problemas comuns na tabela 'users'
 * 
 * CUIDADO: Este script modifica a estrutura do banco de dados!
 * Faça um backup antes de executar.
 */

require_once '../configuracoes/base_dados.php';

echo "<pre style='background: #1e1e1e; color: #f7941d; padding: 20px; font-family: monospace; border-radius: 5px; margin: 20px;'>";
echo "⚠️  REPARAÇÃO DA TABELA USERS\n";
echo "=".str_repeat("=", 80)."\n\n";

$mode = $_GET['mode'] ?? 'report'; // report ou fix
$confirmed = isset($_GET['confirm']) && $_GET['confirm'] === 'yes';

if ($mode === 'fix' && !$confirmed) {
    echo "❌ Use ?mode=fix&confirm=yes para executar as correções.\n";
    echo "\nPrimeiro, execute em modo report para ver quais correções serão feitas:\n";
    echo "?mode=report\n";
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    echo "1️⃣  Analisando estrutura da tabela 'users'...\n\n";
    
    $columns = $db->query("
        SELECT column_name, data_type 
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'users'
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $column_names = array_map(fn($c) => $c['column_name'], $columns);
    $issues = [];
    
    // Problema 1: Coluna 'id' em vez de 'user_id'
    if (in_array('id', $column_names) && !in_array('user_id', $column_names)) {
        $issues[] = [
            'type' => 'wrong_primary_key',
            'description' => "Coluna 'id' encontrada, mas falta 'user_id'",
            'fix' => "Renomear 'id' para 'user_id'"
        ];
    }
    
    // Problema 2: Ambas as colunas existem
    if (in_array('id', $column_names) && in_array('user_id', $column_names)) {
        $issues[] = [
            'type' => 'duplicate_keys',
            'description' => "Ambas as colunas 'id' e 'user_id' existem",
            'fix' => "Remover a coluna 'id' redundante"
        ];
    }
    
    if (empty($issues)) {
        echo "✅ Nenhum problema encontrado!\n";
        echo "\nEstrutura da tabela:\n";
        foreach ($columns as $col) {
            echo "   - {$col['column_name']} ({$col['data_type']})\n";
        }
        exit;
    }
    
    // Mostrar problemas encontrados
    echo "⚠️  PROBLEMAS ENCONTRADOS:\n\n";
    foreach ($issues as $i => $issue) {
        echo ($i+1) . ". {$issue['description']}\n";
        echo "   Solução: {$issue['fix']}\n\n";
    }
    
    if ($mode === 'report') {
        echo "Para executar as correções, use:\n";
        echo "?mode=fix&confirm=yes\n";
        exit;
    }
    
    // MODO FIX
    echo "🔧 APLICANDO CORREÇÕES...\n\n";
    
    foreach ($issues as $issue) {
        if ($issue['type'] === 'wrong_primary_key') {
            echo "   - Renomeando 'id' → 'user_id'... ";
            try {
                // Para PostgreSQL
                $db->exec("ALTER TABLE users RENAME COLUMN id TO user_id");
                echo "✅\n";
            } catch (Exception $e) {
                echo "❌ Erro: " . $e->getMessage() . "\n";
            }
        }
        
        if ($issue['type'] === 'duplicate_keys') {
            echo "   - Removendo coluna 'id' redundante... ";
            try {
                // Primeiro, verificar se há referências a 'id'
                $db->exec("ALTER TABLE users DROP COLUMN id");
                echo "✅\n";
            } catch (Exception $e) {
                echo "❌ Erro: " . $e->getMessage() . "\n";
            }
        }
    }
    
    echo "\n";
    echo "✅ CORREÇÕES APLICADAS!\n\n";
    echo "Próximos passos:\n";
    echo "1. Teste a API em: interface_programacao/social/get_mentor_students.php\n";
    echo "2. Limpe o cache do navegador\n";
    echo "3. Tente carregar os mentorados novamente\n";
    
} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}

echo "</pre>";
?>
