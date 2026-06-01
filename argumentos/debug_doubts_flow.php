<?php
/**
 * debug_doubts_flow.php
 * Script de diagnóstico para o fluxo de dúvidas e comentários
 * Acesso: /argumentos/debug_doubts_flow.php
 */

session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/auth_check.php';

if (!isAdmin()) {
    die('❌ Acesso negado. Apenas admins podem executar este diagnóstico.');
}

$database = new Database();
$db = $database->getConnection();

echo "<!DOCTYPE html>
<html lang='pt-PT'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🔍 Diagnóstico do Sistema de Dúvidas</title>
    <style>
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
            font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;
            margin: 0;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: rgba(15, 23, 42, 0.8);
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        h1 {
            color: #10b981;
            text-align: center;
            margin: 0 0 30px 0;
        }
        h2 {
            color: #f7941d;
            border-bottom: 2px solid rgba(247, 148, 29, 0.3);
            padding-bottom: 10px;
            margin-top: 30px;
        }
        .section {
            background: rgba(16, 185, 129, 0.05);
            border-left: 4px solid #10b981;
            padding: 20px;
            margin: 20px 0;
            border-radius: 8px;
        }
        .success {
            background: rgba(34, 197, 94, 0.1);
            border-left-color: #22c55e;
        }
        .error {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
        }
        .warning {
            background: rgba(245, 158, 11, 0.1);
            border-left-color: #f59e0b;
        }
        code {
            background: rgba(0, 0, 0, 0.2);
            padding: 2px 6px;
            border-radius: 3px;
            font-family: 'Courier New', monospace;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin: 15px 0;
        }
        th {
            background: rgba(16, 185, 129, 0.2);
            border: 1px solid rgba(16, 185, 129, 0.3);
            padding: 10px;
            text-align: left;
        }
        td {
            border: 1px solid rgba(16, 185, 129, 0.2);
            padding: 10px;
        }
        tr:nth-child(even) {
            background: rgba(16, 185, 129, 0.05);
        }
    </style>
</head>
<body>
<div class='container'>
    <h1>🔍 Diagnóstico do Sistema de Dúvidas</h1>
";

// ===== 1. Verificação de Tabelas =====
echo "<h2>1️⃣ Verificação de Tabelas da Base de Dados</h2>";

$tables_to_check = [
    'doubts' => ['doubt_id', 'user_id', 'title', 'description', 'status', 'created_at'],
    'doubt_comments' => ['comment_id', 'doubt_id', 'user_id', 'content', 'created_at', 'is_helpful', 'parent_id'],
    'doubt_comment_votes' => ['vote_id', 'user_id', 'comment_id', 'created_at'],
];

foreach ($tables_to_check as $table => $required_columns) {
    try {
        $result = $db->query("SELECT COUNT(*) FROM information_schema.tables WHERE table_name = '$table' AND table_schema = 'public'");
        $exists = $result->fetchColumn() > 0;
        
        if ($exists) {
            echo "<div class='section success'><strong>✅ Tabela <code>$table</code> existe</strong>";
            
            // Check columns
            $cols_result = $db->query("SELECT column_name FROM information_schema.columns WHERE table_name = '$table' AND table_schema = 'public' ORDER BY ordinal_position");
            $columns = $cols_result->fetchAll(PDO::FETCH_COLUMN);
            
            echo "<br>Colunas encontradas: " . count($columns) . "<br>";
            $missing = array_diff($required_columns, $columns);
            if (!empty($missing)) {
                echo "<span style='color: #ef4444;'>⚠️ Colunas faltantes: " . implode(', ', $missing) . "</span>";
            } else {
                echo "<span style='color: #22c55e;'>✅ Todas as colunas obrigatórias existem</span>";
            }
            echo "</div>";
        } else {
            echo "<div class='section error'><strong>❌ Tabela <code>$table</code> NÃO EXISTE</strong></div>";
        }
    } catch (Exception $e) {
        echo "<div class='section error'><strong>❌ Erro ao verificar tabela <code>$table</code></strong><br>" . $e->getMessage() . "</div>";
    }
}

// ===== 2. Verificação de Dados =====
echo "<h2>2️⃣ Verificação de Dados de Exemplo</h2>";

try {
    $doubts_count = $db->query("SELECT COUNT(*) FROM doubts")->fetchColumn();
    $comments_count = $db->query("SELECT COUNT(*) FROM doubt_comments")->fetchColumn();
    
    echo "<div class='section'>";
    echo "<p><strong>Total de Dúvidas:</strong> $doubts_count</p>";
    echo "<p><strong>Total de Comentários:</strong> $comments_count</p>";
    
    if ($doubts_count > 0) {
        echo "<h3>Últimas 5 Dúvidas:</h3>";
        $recent = $db->query("SELECT doubt_id, user_id, title, status, created_at FROM doubts ORDER BY doubt_id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
        echo "<table><tr><th>ID</th><th>Utilizador</th><th>Título</th><th>Status</th><th>Criado em</th></tr>";
        foreach ($recent as $d) {
            echo "<tr><td>{$d['doubt_id']}</td><td>{$d['user_id']}</td><td>" . substr($d['title'], 0, 30) . "...</td><td><strong>{$d['status']}</strong></td><td>{$d['created_at']}</td></tr>";
        }
        echo "</table>";
    } else {
        echo "<div class='warning'><strong>⚠️ Nenhuma dúvida encontrada. Crie uma para testar.</strong></div>";
    }
    echo "</div>";
} catch (Exception $e) {
    echo "<div class='section error'>❌ Erro: " . $e->getMessage() . "</div>";
}

// ===== 3. Verificação de Segurança =====
echo "<h2>3️⃣ Verificação de Segurança (CSRF)</h2>";

echo "<div class='section'>";
echo "<p><strong>Função getallheaders() disponível?</strong> " . (function_exists('getallheaders') ? '✅ Sim' : '❌ Não (usando fallback $_SERVER)') . "</p>";
echo "<p><strong>Superglobal \$_SERVER CSRF detection:</strong>";
echo "<ul>";
foreach ($_SERVER as $key => $value) {
    if (strpos($key, 'CSRF') !== false || strpos($key, 'X_CSRF') !== false) {
        echo "<li>$key: " . substr($value, 0, 20) . "...</li>";
    }
}
if (empty(array_filter($_SERVER, fn($k) => strpos($k, 'CSRF') !== false || strpos($k, 'X_CSRF') !== false, ARRAY_FILTER_USE_KEY))) {
    echo "<li>(nenhuma encontrada)</li>";
}
echo "</ul>";
echo "</p>";
echo "</div>";

// ===== 4. Verificação de Fluxo =====
echo "<h2>4️⃣ Testes de Fluxo</h2>";

$test_doubt_id = null;
try {
    // Procurar uma dúvida aberta
    $stmt = $db->prepare("SELECT doubt_id FROM doubts WHERE status = 'open' LIMIT 1");
    $stmt->execute();
    $result = $stmt->fetch();
    $test_doubt_id = $result ? $result['doubt_id'] : null;
} catch (Exception $e) {}

if ($test_doubt_id) {
    echo "<div class='section success'>";
    echo "<h3>✅ Dúvida de teste encontrada: #$test_doubt_id</h3>";
    echo "<p>URLs de teste (copie e cole no navegador):</p>";
    echo "<ul>";
    echo "<li><a href='../paginas/explorar/doubts.php' target='_blank'>📋 Abrir página de dúvidas</a></li>";
    echo "<li><a href='../interface_programacao/social/get_doubt_detail.php?doubt_id=$test_doubt_id' target='_blank'>🔗 Testar API get_doubt_detail.php</a></li>";
    echo "</ul>";
    
    // Testar comentários dessa dúvida
    $comments = $db->prepare("SELECT COUNT(*) FROM doubt_comments WHERE doubt_id = ?")->execute([$test_doubt_id]);
    $comment_count = $db->prepare("SELECT COUNT(*) FROM doubt_comments WHERE doubt_id = ?")->execute([$test_doubt_id]);
    $comment_count = $db->query("SELECT COUNT(*) FROM doubt_comments WHERE doubt_id = $test_doubt_id")->fetchColumn();
    
    echo "<p><strong>Comentários nesta dúvida:</strong> $comment_count</p>";
    echo "</div>";
} else {
    echo "<div class='section warning'>";
    echo "<h3>⚠️ Nenhuma dúvida aberta encontrada</h3>";
    echo "<p>Crie uma dúvida na página de <a href='../paginas/explorar/doubts.php'>dúvidas</a> para testar.</p>";
    echo "</div>";
}

// ===== 5. Logs Recentes =====
echo "<h2>5️⃣ Logs de Erro Recentes</h2>";

$log_file = sys_get_temp_dir() . '/php_errors.log';
if (file_exists($log_file)) {
    $lines = array_slice(file($log_file, FILE_SKIP_EMPTY_LINES), -10);
    echo "<div class='section'>";
    echo "<p><strong>Últimas 10 linhas de erro:</strong></p>";
    echo "<pre style='background: rgba(0,0,0,0.3); padding: 15px; border-radius: 8px; overflow-x: auto; max-height: 300px;'>";
    foreach ($lines as $line) {
        if (strpos($line, 'doubt') !== false || strpos($line, 'comment') !== false || strpos($line, 'csrf') !== false) {
            echo "➜ " . htmlspecialchars($line);
        }
    }
    echo "</pre>";
    echo "</div>";
} else {
    echo "<div class='section warning'><p>⚠️ Arquivo de log não encontrado em: $log_file</p></div>";
}

echo "
</div>
</body>
</html>";
