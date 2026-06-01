<?php
/**
 * init_database.php
 * Inicializa a base de dados com todas as tabelas necessárias
 * Executa o script SQL no PostgreSQL
 */

require_once '../configuracoes/base_dados.php';

echo "<!DOCTYPE html>
<html lang='pt-PT'>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>🗄️ Inicialização da Base de Dados - KALIYE</title>
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
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 30px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #10b981;
            text-align: center;
            margin: 0 0 30px 0;
            font-size: 2em;
        }
        
        .step {
            margin: 15px 0;
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
        
        .icon {
            display: inline-block;
            width: 20px;
            text-align: center;
            margin-right: 10px;
        }
        
        .summary {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid #10b981;
            border-radius: 8px;
            padding: 20px;
            margin-top: 30px;
        }
        
        button {
            background: #10b981;
            color: #0f172a;
            border: none;
            padding: 12px 30px;
            border-radius: 8px;
            font-weight: bold;
            cursor: pointer;
            font-size: 1em;
            margin-top: 20px;
            transition: all 0.3s;
        }
        
        button:hover {
            background: #059669;
            transform: scale(1.05);
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
            font-size: 0.9em;
        }
        
        table th {
            background: rgba(16, 185, 129, 0.2);
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #10b981;
        }
        
        table td {
            padding: 8px;
            border-bottom: 1px solid rgba(16, 185, 129, 0.1);
        }
        
        table tr:hover {
            background: rgba(16, 185, 129, 0.05);
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
        <h1>🗄️ Inicialização da Base de Dados</h1>";

$confirmed = isset($_GET['execute']) && $_GET['execute'] === 'yes';

if (!$confirmed) {
    echo "
        <div class='step'>
            <strong>⚠️ AVISO IMPORTANTE</strong><br>
            Este processo irá criar TODAS as tabelas necessárias na base de dados KALIYE.
            <br><br>
            Se já tem tabelas existentes, elas NÃO serão removidas (usa-se CREATE TABLE IF NOT EXISTS).
        </div>
        
        <div class='step' style='background: rgba(59, 130, 246, 0.1); border-left-color: #3b82f6;'>
            <strong>📋 Tabelas que serão criadas:</strong>
            <ul style='margin: 10px 0; padding-left: 20px;'>
                <li>users - Utilizadores da plataforma</li>
                <li>institutions - Instituições</li>
                <li>user_profiles - Perfis estendidos</li>
                <li>otp_codes - Códigos de verificação</li>
                <li>projects - Projetos</li>
                <li>project_applications - Candidaturas</li>
                <li>mentorship_contracts - Contratos de mentoria</li>
                <li>mentor_chat_groups - Salas VIP</li>
                <li>mentor_group_members - Membros de grupos</li>
                <li>mentor_group_messages - Mensagens VIP</li>
                <li>notifications - Notificações</li>
                <li>audit_logs - Logs de auditoria</li>
                <li>user_connections - Conexões entre utilizadores</li>
                <li>direct_messages - Mensagens diretas</li>
                <li>user_reviews - Avaliações</li>
                <li>project_investments - Investimentos</li>
                <li>support_messages - Suporte</li>
            </ul>
        </div>
        
        <button onclick=\"location.href='?execute=yes'\" style='background: #059669;'>
            ✅ Iniciar Criação de Tabelas
        </button>
        
        <button onclick=\"history.back()\" style='background: #64748b;'>
            Cancelar
        </button>
    </div>
    </body>
    </html>";
    exit;
}

// ============================================================================
// EXECUÇÃO REAL
// ============================================================================

$tables_created = 0;
$tables_skipped = 0;
$errors = [];

try {
    $db = (new Database())->getConnection();
    
    echo "<div class='step'><span class='icon'>✓</span> Conexão à BD estabelecida</div>";
    
    // Ler e executar o script SQL
    $sql_file = __DIR__ . '/../sql/init_database_postgresql.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("Ficheiro SQL não encontrado: $sql_file");
    }
    
    echo "<div class='step'><span class='icon'>✓</span> Ficheiro SQL encontrado</div>";
    
    // Ler o arquivo SQL
    $sql_content = file_get_contents($sql_file);
    
    // Dividir em comandos
    $statements = preg_split('/;[\s\n]+/', $sql_content);
    
    echo "<div class='step'><span class='icon'>⏳</span> Executando " . count($statements) . " comandos SQL...</div>";
    
    foreach ($statements as $statement) {
        $statement = trim($statement);
        
        // Ignorar comentários e linhas vazias
        if (empty($statement) || strpos($statement, '--') === 0) {
            continue;
        }
        
        try {
            $db->exec($statement);
            
            // Verificar se é CREATE TABLE
            if (stripos($statement, 'CREATE TABLE') !== false) {
                // Extrair nome da tabela
                if (preg_match('/CREATE TABLE\s+(?:IF\s+NOT\s+EXISTS\s+)?(\w+)/i', $statement, $matches)) {
                    $table_name = $matches[1];
                    echo "<div class='step completed'><span class='icon'>✓</span> Tabela criada: <code>$table_name</code></div>";
                    $tables_created++;
                }
            }
        } catch (Exception $e) {
            // Se tabela já existe, não é erro
            if (strpos($e->getMessage(), 'already exists') !== false || 
                strpos($e->getMessage(), 'relation') !== false) {
                $tables_skipped++;
            } else {
                $errors[] = $e->getMessage();
                echo "<div class='step error'><span class='icon'>❌</span> Erro: " . htmlspecialchars($e->getMessage()) . "</div>";
            }
        }
    }
    
    // ========================================================================
    // Verificar tabelas criadas
    // ========================================================================
    echo "<div class='summary'><h3 style='color: #10b981; margin-top: 0;'>✅ INICIALIZAÇÃO CONCLUÍDA!</h3>";
    
    echo "<p>Tabelas criadas: <strong>$tables_created</strong></p>";
    if ($tables_skipped > 0) {
        echo "<p>Tabelas já existentes: <strong>$tables_skipped</strong></p>";
    }
    
    if (!empty($errors)) {
        echo "<p style='color: #ef4444;'>Erros encontrados: <strong>" . count($errors) . "</strong></p>";
    }
    
    // Listar todas as tabelas
    echo "<p><strong>Tabelas no banco de dados:</strong></p>";
    
    $tables = $db->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    echo "<table>
        <tr>
            <th>Tabela</th>
            <th>Registos</th>
            <th>Status</th>
        </tr>";
    
    foreach ($tables as $table) {
        try {
            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn();
            echo "<tr>
                <td><code>$table</code></td>
                <td>$count</td>
                <td>✓ OK</td>
            </tr>";
        } catch (Exception $e) {
            echo "<tr>
                <td><code>$table</code></td>
                <td>-</td>
                <td>⚠ Erro</td>
            </tr>";
        }
    }
    
    echo "</table>";
    
    echo "<p style='color: #cbd5e1; margin-top: 20px; font-size: 0.9em;'>
        ✅ Base de dados pronta para uso!<br>
        Próximos passos:<br>
        1. Recarregue o navegador (Ctrl+F5)<br>
        2. Teste a plataforma<br>
        3. Se encontrar erros, execute: <code>argumentos/verify_users_table.php</code>
    </p>";
    
    echo "</div>";
    
} catch (Exception $e) {
    echo "<div class='step error'>
        <h3 style='color: #ef4444; margin-top: 0;'>❌ ERRO CRÍTICO!</h3>
        <p>" . htmlspecialchars($e->getMessage()) . "</p>
        <p style='color: #cbd5e1; font-size: 0.9em;'>
            Possíveis soluções:<br>
            1. Verifique a conexão ao PostgreSQL<br>
            2. Confirme as credenciais em <code>configuracoes/base_dados.php</code><br>
            3. Verifique que a base de dados 'kaliye' existe
        </p>
    </div>";
}

echo "
        <div style='text-align: center; margin-top: 30px;'>
            <button onclick=\"location.href='quick_sync.php'\" style='background: #3b82f6;'>
                🔄 Sincronizar Dados
            </button>
            <button onclick=\"location.href='verify_users_table.php'\" style='background: #3b82f6;'>
                📋 Verificar Tabelas
            </button>
        </div>
    </div>
    </body>
    </html>";
?>
