<?php
/**
 * diagnose_and_fix_users_column.php
 * Script de diagnóstico e correção via web
 */
require_once '../configuracoes/base_dados.php';

echo "<style>
    body { background: #1e1e1e; color: #f7941d; font-family: monospace; padding: 20px; }
    .step { margin: 15px 0; padding: 10px; border-left: 3px solid #f7941d; }
    .success { color: #0f0; }
    .error { color: #f00; }
    .warning { color: #ff0; }
    pre { background: #0a0a0a; padding: 10px; border-radius: 5px; overflow-x: auto; }
</style>";

echo "<h2>🔍 Diagnóstico e Correção: Tabela USERS</h2>";

try {
    $db = (new Database())->getConnection();
    
    // 1. Verificar colunas
    echo "<div class='step'>";
    echo "<strong>1️⃣ Verificando colunas...</strong><br>";
    
    $result = $db->query("
        SELECT column_name, data_type, column_key
        FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'users'
        ORDER BY ordinal_position
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    $col_names = array_column($result, 'column_name');
    
    echo "<pre>";
    foreach ($result as $col) {
        $isPK = $col['column_key'] === 'PRI' ? " <strong>[PRIMARY KEY]</strong>" : "";
        echo htmlspecialchars($col['column_name']) . " (" . htmlspecialchars($col['data_type']) . ")" . $isPK . "\n";
    }
    echo "</pre>";
    
    // 2. Diagnóstico
    echo "<strong>2️⃣ Diagnóstico:</strong><br>";
    
    $has_id = in_array('id', $col_names);
    $has_user_id = in_array('user_id', $col_names);
    
    if ($has_id && $has_user_id) {
        echo "<span class='error'>❌ PROBLEMA: Ambas as colunas 'id' e 'user_id' existem!</span><br>";
        echo "Isto causa conflitos nas queries. Vou remover 'id'...<br>";
        
        // Remover coluna id
        try {
            $db->exec("ALTER TABLE users DROP COLUMN id CASCADE");
            echo "<span class='success'>✅ Coluna 'id' removida com sucesso!</span><br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ Erro ao remover: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    } elseif ($has_id && !$has_user_id) {
        echo "<span class='warning'>⚠️ Coluna 'id' encontrada (sem 'user_id')</span><br>";
        echo "Vou renomear 'id' → 'user_id'...<br>";
        
        try {
            $db->exec("ALTER TABLE users RENAME COLUMN id TO user_id");
            echo "<span class='success'>✅ Coluna renomeada com sucesso!</span><br>";
        } catch (Exception $e) {
            echo "<span class='error'>❌ Erro ao renomear: " . htmlspecialchars($e->getMessage()) . "</span><br>";
        }
    } elseif ($has_user_id) {
        echo "<span class='success'>✅ CORRETO: Apenas 'user_id' existe!</span><br>";
        echo "Nenhuma ação necessária.<br>";
    } else {
        echo "<span class='error'>❌ PROBLEMA CRÍTICO: Nem 'id' nem 'user_id' encontrados!</span><br>";
    }
    
    echo "</div>";
    
    // 3. Verificar após correção
    echo "<div class='step'>";
    echo "<strong>3️⃣ Verificação final:</strong><br>";
    
    $result2 = $db->query("
        SELECT column_name FROM information_schema.columns 
        WHERE table_schema = 'public' AND table_name = 'users'
        AND column_name IN ('id', 'user_id')
    ")->fetchAll(PDO::FETCH_ASSOC);
    
    if (empty($result2)) {
        echo "<span class='error'>❌ Nenhuma coluna de ID encontrada!</span>";
    } else {
        echo "<span class='success'>✅ Colunas de ID:</span><br>";
        echo "<pre>";
        foreach ($result2 as $col) {
            echo "  - " . htmlspecialchars($col['column_name']) . "\n";
        }
        echo "</pre>";
    }
    
    echo "</div>";
    
    // 4. Instrução final
    echo "<div class='step' style='background: rgba(0, 255, 0, 0.1); border-left-color: #0f0;'>";
    echo "<strong style='color: #0f0;'>✅ Próxima ação:</strong><br>";
    echo "1. Recarregue a página: <a href='http://192.168.0.195/kaliye/paginas/social/messages.php' target='_blank'>messages.php</a><br>";
    echo "2. Limpe o cache do navegador (Ctrl+F5)<br>";
    echo "3. Tente carregar os mentorados novamente<br>";
    echo "</div>";
    
} catch (Exception $e) {
    echo "<span class='error'><strong>❌ ERRO FATAL:</strong></span><br>";
    echo "<pre>";
    echo htmlspecialchars($e->getMessage());
    echo "</pre>";
}
?>
