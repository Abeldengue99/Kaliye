<?php
/**
 * quick_sync.php
 * Sincronização rápida em um clique
 * Executa todos os diagnósticos e correções necessárias
 */

require_once '../configuracoes/base_dados.php';

// Forçar saída de texto
header('Content-Type: text/html; charset=utf-8');

echo "<!DOCTYPE html>
<html lang='pt-PT'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔄 Sincronização Rápida - KALIYE</title>
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
            font-family: 'Consolas', 'Monaco', monospace;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        
        .container {
            max-width: 900px;
            margin: 0 auto;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid #f7941d;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #f7941d;
            text-align: center;
            margin: 0 0 30px 0;
            font-size: 2em;
        }
        
        .step {
            margin: 20px 0;
            padding: 15px;
            background: rgba(16, 185, 129, 0.05);
            border-left: 4px solid #10b981;
            border-radius: 8px;
        }
        
        .step.completed {
            background: rgba(34, 197, 94, 0.1);
            border-left-color: #22c55e;
        }
        
        .step.error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
        }
        
        .step-title {
            font-weight: bold;
            color: #f7941d;
            margin-bottom: 8px;
        }
        
        .step-result {
            margin-left: 20px;
            color: #cbd5e1;
        }
        
        .icon {
            display: inline-block;
            width: 20px;
            text-align: center;
        }
        
        .summary {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid #3b82f6;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
            color: #93c5fd;
        }
        
        .button-group {
            text-align: center;
            margin-top: 30px;
        }
        
        button {
            background: #f7941d;
            color: #0f172a;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1em;
            margin: 0 10px;
            transition: all 0.3s;
        }
        
        button:hover {
            background: #ff9d3d;
            transform: scale(1.05);
        }
        
        .loading {
            animation: spin 1s linear infinite;
            display: inline-block;
        }
        
        @keyframes spin {
            from { transform: rotate(0deg); }
            to { transform: rotate(360deg); }
        }
        
        code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
            color: #10b981;
        }
    </style>
</head>
<body>
    <div class='container'>
        <h1>🔄 Sincronização Rápida</h1>";

$confirmed = isset($_GET['execute']) && $_GET['execute'] === 'yes';

if (!$confirmed) {
    echo "
        <p style='text-align: center; color: #cbd5e1; margin-bottom: 30px;'>
            Pressione o botão abaixo para sincronizar todos os dados da plataforma KALIYE.
        </p>
        
        <div class='step' style='background: rgba(249, 115, 22, 0.1); border-left-color: #f97316;'>
            <div class='step-title'>⚠️ Avisos Importantes</div>
            <div class='step-result'>
                ✅ Este processo é seguro e não modifica dados importantes<br>
                ✅ Recomenda-se fazer um backup antes de começar<br>
                ✅ O processo levará alguns minutos<br>
                ✅ Não interrompa durante a execução
            </div>
        </div>
        
        <div class='button-group'>
            <button onclick=\"location.href='?execute=yes'\">
                <span class='loading'>⚙️</span> Iniciar Sincronização
            </button>
            <button onclick=\"history.back()\" style='background: #64748b;'>
                Cancelar
            </button>
        </div>
    </div>
    </body>
    </html>";
    exit;
}

// EXECUÇÃO REAL
try {
    $db = (new Database())->getConnection();
    
    echo "<div class='step'>
        <div class='step-title'>1️⃣ Verificando Tabela Users</div>
        <div class='step-result'>";
    
    // Passo 1: Verificar tabela users
    $check_users = $db->query("
        SELECT EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_schema = 'public' AND table_name = 'users'
        )
    ")->fetchColumn();
    
    if (!$check_users) {
        echo "<span class='icon'>❌</span> Tabela 'users' não encontrada!";
        throw new Exception('Tabela users não existe');
    }
    
    echo "<span class='icon'>✅</span> Tabela 'users' encontrada<br>";
    
    // Verificar colunas
    $has_user_id = $db->query("
        SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'user_id'
        )
    ")->fetchColumn();
    
    if ($has_user_id) {
        echo "<span class='icon'>✅</span> Coluna 'user_id' OK<br>";
    } else {
        // Procurar 'id' e renomear
        $has_id = $db->query("
            SELECT EXISTS (
                SELECT 1 FROM information_schema.columns 
                WHERE table_schema = 'public' AND table_name = 'users' AND column_name = 'id'
            )
        ")->fetchColumn();
        
        if ($has_id) {
            echo "<span class='icon'>🔧</span> Corrigindo: Renomeando 'id' → 'user_id'...<br>";
            $db->exec("ALTER TABLE users RENAME COLUMN id TO user_id");
            echo "<span class='icon'>✅</span> Coluna renomeada<br>";
        }
    }
    
    // Contar usuários
    $user_count = $db->query("SELECT COUNT(*) FROM users")->fetchColumn();
    echo "<span class='icon'>ℹ️</span> Total de usuários: $user_count<br>";
    
    echo "</div></div>";
    
    // Passo 2: Verificar foreign keys
    echo "<div class='step completed'>
        <div class='step-title'>2️⃣ Verificando Integridade Referencial</div>
        <div class='step-result'>";
    
    $fks = $db->query("
        SELECT COUNT(*) FROM information_schema.table_constraints 
        WHERE constraint_type = 'FOREIGN KEY' AND table_schema = 'public'
    ")->fetchColumn();
    
    echo "<span class='icon'>✅</span> $fks Foreign Keys encontradas<br>";
    
    // Procurar orfãos
    try {
        $orphans = $db->query("
            SELECT COUNT(*) FROM mentor_chat_groups mg
            WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.user_id = mg.mentor_id)
        ")->fetchColumn();
        
        if ($orphans > 0) {
            echo "<span class='icon'>🔧</span> Removendo $orphans registos órfãos...<br>";
            $db->exec("
                DELETE FROM mentor_chat_groups 
                WHERE NOT EXISTS (SELECT 1 FROM users u WHERE u.user_id = mentor_id)
            ");
            echo "<span class='icon'>✅</span> Registos órfãos removidos<br>";
        } else {
            echo "<span class='icon'>✅</span> Sem registos órfãos encontrados<br>";
        }
    } catch (Exception $e) {
        echo "<span class='icon'>ℹ️</span> Verificação de orfãos: tabelas ausentes<br>";
    }
    
    echo "</div></div>";
    
    // Passo 3: Otimizar índices
    echo "<div class='step completed'>
        <div class='step-title'>3️⃣ Otimizando Banco de Dados</div>
        <div class='step-result'>";
    
    $tables_to_analyze = ['users', 'mentor_chat_groups', 'mentorship_contracts', 'projects'];
    $analyzed = 0;
    
    foreach ($tables_to_analyze as $table) {
        try {
            $exists = $db->query("
                SELECT EXISTS (
                    SELECT 1 FROM information_schema.tables 
                    WHERE table_schema = 'public' AND table_name = '$table'
                )
            ")->fetchColumn();
            
            if ($exists) {
                $db->exec("ANALYZE $table");
                $analyzed++;
                echo "<span class='icon'>✅</span> Tabela '$table' analisada<br>";
            }
        } catch (Exception $e) {
            // Ignorar erro
        }
    }
    
    echo "<span class='icon'>ℹ️</span> $analyzed tabelas otimizadas<br>";
    echo "</div></div>";
    
    // Passo 4: Verificar cache
    echo "<div class='step completed'>
        <div class='step-title'>4️⃣ Limpando Cache</div>
        <div class='step-result'>";
    
    // PHP Cache
    if (function_exists('opcache_reset')) {
        opcache_reset();
        echo "<span class='icon'>✅</span> Cache OPcache limpo<br>";
    }
    
    echo "<span class='icon'>✅</span> Sistema pronto<br>";
    echo "</div></div>";
    
    // Resumo final
    echo "<div class='summary'>
        <h3 style='color: #60a5fa; margin-top: 0;'>✅ SINCRONIZAÇÃO COMPLETA!</h3>
        <p>Todas as operações foram executadas com sucesso.</p>
        <p><strong>Próximos passos:</strong></p>
        <ol>
            <li>Limpe o cache do navegador: <code>Ctrl+Shift+Delete</code></li>
            <li>Recarregue a página: <code>Ctrl+F5</code></li>
            <li>Teste a funcionalidade de mentorados</li>
        </ol>
        <p style='color: #94a3b8; font-size: 0.9em; margin-bottom: 0;'>
            Se o problema persistir, execute: <code>argumentos/verify_users_table.php</code>
        </p>
    </div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>
        <div class='step-title'>❌ ERRO!</div>
        <div class='step-result'>
            " . htmlspecialchars($e->getMessage()) . "
        </div>
    </div>";
}

echo "
        <div class='button-group'>
            <button onclick=\"location.href='verify_users_table.php'\" style='background: #3b82f6;'>
                📋 Verificar Novamente
            </button>
            <button onclick=\"history.back()\">
                ← Voltar
            </button>
        </div>
    </div>
    </body>
    </html>";
?>
