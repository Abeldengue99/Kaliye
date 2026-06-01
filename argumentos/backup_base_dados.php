<?php
/**
 * BACKUP COMPLETO DA BASE DE DADOS PostgreSQL
 * 
 * Cria backup seguro antes de eliminar tabelas
 * Este é o ficheiro MAIS IMPORTANTE - guarde a qualquer custo!
 */

set_time_limit(300);
ini_set('memory_limit', '512M');

require_once '../configuracoes/base_dados.php';

$backup_dir = dirname(__FILE__) . '/../../backups';

// Criar diretório de backups se não existir
if (!is_dir($backup_dir)) {
    mkdir($backup_dir, 0755, true);
}

// Nome do ficheiro de backup
$timestamp = date('Y-m-d_H-i-s');
$backup_file = $backup_dir . '/kaliye_backup_' . $timestamp . '.sql';

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔐 Backup da Base de Dados</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
            color: #e2e8f0;
            font-family: 'Segoe UI', Arial, sans-serif;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 900px;
            margin: 0 auto;
        }
        header {
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid #ef4444;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        h1 {
            color: #ef4444;
            margin-bottom: 10px;
        }
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            color: #fca5a5;
        }
        .section {
            background: rgba(15, 23, 42, 0.9);
            border: 1px solid #334155;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 5px 15px rgba(0,0,0,0.3);
        }
        h2 {
            color: #3b82f6;
            font-size: 1.3em;
            margin: 20px 0 15px 0;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        .progress {
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            height: 30px;
            overflow: hidden;
            margin: 15px 0;
        }
        .progress-bar {
            height: 100%;
            background: linear-gradient(90deg, #10b981, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
            transition: width 0.3s;
        }
        .success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            color: #86efac;
        }
        code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
            color: #10b981;
        }
        .stats {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin: 15px 0;
        }
        .stat-card {
            background: rgba(16, 185, 129, 0.1);
            border-left: 4px solid #10b981;
            padding: 15px;
            border-radius: 8px;
        }
        .stat-card h3 {
            color: #86efac;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        .stat-card .value {
            font-size: 1.6em;
            color: #10b981;
            font-weight: bold;
        }
        button {
            background: #10b981;
            color: white;
            border: none;
            padding: 12px 24px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            margin: 10px 5px 10px 0;
            transition: background 0.3s;
        }
        button:hover {
            background: #059669;
        }
        .log {
            background: rgba(20, 30, 48, 0.7);
            border: 1px solid #334155;
            border-radius: 6px;
            padding: 15px;
            max-height: 300px;
            overflow-y: auto;
            font-family: monospace;
            font-size: 0.9em;
            color: #cbd5e1;
            margin: 15px 0;
        }
        .log-entry {
            margin: 5px 0;
            padding: 5px;
            border-left: 2px solid #10b981;
            padding-left: 10px;
        }
        .log-entry.error {
            border-left-color: #ef4444;
            color: #fca5a5;
        }
        .log-entry.success {
            border-left-color: #10b981;
            color: #86efac;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        table thead {
            background: rgba(16, 185, 129, 0.2);
        }
        table tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔐 BACKUP COMPLETO DA BASE DE DADOS</h1>
            <p>Criar cópia de segurança antes de eliminar tabelas</p>
            
            <div class="warning">
                ⚠️ <strong>CRÍTICO:</strong> Este backup é essencial antes de qualquer operação destrutiva!
                <br>Guarde o ficheiro em local seguro!
            </div>
        </header>

        <?php
        try {
            $db = (new Database())->getConnection();
            
            // Obter informações da base de dados
            $tables = $db->query("
                SELECT table_name FROM information_schema.tables 
                WHERE table_schema = 'public' 
                ORDER BY table_name
            ")->fetchAll(\PDO::FETCH_COLUMN);
            
            $total_size = 0;
            $total_records = 0;
            
            foreach ($tables as $table) {
                try {
                    $size = $db->query("SELECT pg_total_relation_size('$table')::bigint")->fetchColumn() ?? 0;
                    $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn() ?? 0;
                    $total_size += $size;
                    $total_records += $count;
                } catch (Exception $e) {
                    // Skip
                }
            }
            
            $backup_exists = file_exists($backup_file);
            $backup_size = 0;
            
        } catch (Exception $e) {
            $error = $e->getMessage();
        }
        ?>
        
        <!-- SECÇÃO 1: INFORMAÇÕES DE BACKUP -->
        <div class="section">
            <h2>📊 Informações de Backup</h2>
            
            <div class="stats">
                <div class="stat-card">
                    <h3>📋 Tabelas</h3>
                    <div class="value"><?php echo count($tables); ?></div>
                </div>
                <div class="stat-card">
                    <h3>📦 Tamanho Total</h3>
                    <div class="value"><?php echo round($total_size / (1024*1024), 2); ?> MB</div>
                </div>
                <div class="stat-card">
                    <h3>📊 Registos</h3>
                    <div class="value"><?php echo number_format($total_records); ?></div>
                </div>
                <div class="stat-card">
                    <h3>📁 Localização</h3>
                    <div class="value" style="font-size: 0.8em; word-break: break-all;"><?php echo str_replace('\\', '/', $backup_dir); ?>/</div>
                </div>
            </div>
            
            <div class="log">
                <div class="log-entry">📌 Base de dados: <code>kaliye</code></div>
                <div class="log-entry">🗂️ Schema: <code>public</code></div>
                <div class="log-entry">📅 Timestamp: <code><?php echo $timestamp; ?></code></div>
                <div class="log-entry">💾 Ficheiro: <code><?php echo basename($backup_file); ?></code></div>
                <div class="log-entry">📍 Caminho: <code><?php echo str_replace('\\', '/', $backup_file); ?></code></div>
                <div class="log-entry">👤 Utilizador: <code>postgres</code></div>
                <div class="log-entry">🔐 Host: <code>127.0.0.1:5432</code></div>
            </div>
        </div>

        <!-- SECÇÃO 2: MÉTODOS DE BACKUP -->
        <div class="section">
            <h2>🔄 Executar Backup</h2>
            
            <div class="warning" style="background: rgba(249, 115, 22, 0.1); border-color: rgba(249, 115, 22, 0.3); color: #fdba74;">
                <strong>⚡ IMPORTANTE:</strong> Escolha um dos métodos abaixo para fazer backup
            </div>
            
            <h3 style="color: #3b82f6; margin-top: 20px; margin-bottom: 10px;">Método 1: PowerShell (Recomendado)</h3>
            <div style="background: rgba(20, 30, 48, 0.7); padding: 15px; border-radius: 8px; margin: 10px 0;">
                <p style="margin-bottom: 10px;">Execute este comando em PowerShell (como Administrator):</p>
                <code style="display: block; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all;">
$env:PGPASSWORD='5850'; & 'C:\Program Files\PostgreSQL\18\bin\pg_dump.exe' -U postgres -h 127.0.0.1 kaliye | Out-File -Encoding UTF8 '<?php echo str_replace('\\', '\\\\', $backup_file); ?>'
                </code>
            </div>
            
            <h3 style="color: #3b82f6; margin-top: 20px; margin-bottom: 10px;">Método 2: Linha de Comando (CMD)</h3>
            <div style="background: rgba(20, 30, 48, 0.7); padding: 15px; border-radius: 8px; margin: 10px 0;">
                <p style="margin-bottom: 10px;">Execute este comando em CMD (Command Prompt):</p>
                <code style="display: block; background: rgba(0,0,0,0.5); padding: 10px; border-radius: 4px; margin: 10px 0; word-break: break-all;">
set PGPASSWORD=5850 & "C:\Program Files\PostgreSQL\18\bin\pg_dump.exe" -U postgres -h 127.0.0.1 kaliye > "<?php echo $backup_file; ?>"
                </code>
            </div>
            
            <h3 style="color: #3b82f6; margin-top: 20px; margin-bottom: 10px;">Método 3: pgAdmin 4 (GUI)</h3>
            <div style="background: rgba(20, 30, 48, 0.7); padding: 15px; border-radius: 8px; margin: 10px 0;">
                <ol style="margin-left: 20px;">
                    <li>Abra pgAdmin 4</li>
                    <li>Direito-clique em <code>kaliye</code> database</li>
                    <li>Seleccione <code>Backup...</code></li>
                    <li>Format: <code>Plain</code></li>
                    <li>Filename: <code><?php echo basename($backup_file); ?></code></li>
                    <li>Clique <code>Backup</code></li>
                </ol>
            </div>
        </div>

        <!-- SECÇÃO 3: VERIFICAÇÃO DE BACKUP -->
        <div class="section">
            <h2>✅ Verificar Backup</h2>
            
            <?php
            $backup_exists = file_exists($backup_file);
            $backup_size = $backup_exists ? filesize($backup_file) : 0;
            ?>
            
            <div class="log">
                <div class="log-entry <?php echo $backup_exists ? 'success' : 'error'; ?>">
                    <?php echo $backup_exists ? '✅' : '❌'; ?> 
                    Ficheiro: <?php echo basename($backup_file); ?> 
                    (<?php echo $backup_exists ? (round($backup_size / 1024, 2) . ' KB') : 'NÃO ENCONTRADO'; ?>)
                </div>
                
                <?php if ($backup_exists): ?>
                    <div class="log-entry success">
                        ✅ Tamanho: <?php echo round($backup_size / 1024, 2); ?> KB
                    </div>
                    <div class="log-entry success">
                        ✅ Data de criação: <?php echo date('d/m/Y H:i:s', filemtime($backup_file)); ?>
                    </div>
                    <div class="log-entry success">
                        ✅ Localização: <?php echo str_replace('\\', '/', $backup_file); ?>
                    </div>
                <?php else: ?>
                    <div class="log-entry error">
                        ❌ Backup NÃO EXECUTADO AINDA
                    </div>
                    <div class="log-entry error">
                        ❌ Execute um dos comandos acima em PowerShell ou CMD
                    </div>
                <?php endif; ?>
            </div>
            
            <?php if ($backup_exists): ?>
                <div class="success">
                    ✅ <strong>BACKUP CRIADO COM SUCESSO!</strong>
                    <br>Ficheiro pronto em: <code><?php echo str_replace('\\', '/', $backup_file); ?></code>
                </div>
            <?php else: ?>
                <div class="warning">
                    ⚠️ <strong>BACKUP NÃO ENCONTRADO</strong>
                    <br>Execute um dos comandos acima para criar o backup antes de continuar!
                </div>
            <?php endif; ?>
        </div>

        <!-- SECÇÃO 4: TABELAS LISTADAS (para referência) -->
        <div class="section">
            <h2>📋 Tabelas na Base de Dados (<?php echo count($tables); ?>)</h2>
            
            <table>
                <thead>
                    <tr>
                        <th>#</th>
                        <th>Tabela</th>
                        <th>Tamanho</th>
                        <th>Registos</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $index = 1;
                    foreach ($tables as $table):
                        try {
                            $size = $db->query("SELECT pg_total_relation_size('$table')::bigint")->fetchColumn() ?? 0;
                            $count = $db->query("SELECT COUNT(*) FROM $table")->fetchColumn() ?? 0;
                        } catch (Exception $e) {
                            $size = 0;
                            $count = 0;
                        }
                    ?>
                        <tr>
                            <td><?php echo $index; ?></td>
                            <td><code><?php echo $table; ?></code></td>
                            <td><?php echo round($size / 1024, 2); ?> KB</td>
                            <td><?php echo number_format($count); ?></td>
                        </tr>
                    <?php $index++; endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SECÇÃO 5: PRÓXIMOS PASSOS -->
        <div class="section">
            <h2>🚀 Próximos Passos</h2>
            
            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); padding: 15px; border-radius: 8px; color: #86efac;">
                <ol style="margin-left: 20px;">
                    <li><strong>Execute um dos comandos acima</strong> para fazer o backup</li>
                    <li><strong>Refresque esta página</strong> para confirmar que foi criado</li>
                    <li><strong>Guarde o ficheiro em local seguro</strong> (pen drive, cloud, etc)</li>
                    <li><strong>Depois</strong> acesse: <code>argumentos/eliminar_tabelas_seguras.php</code></li>
                    <li><strong>Verifique a plataforma</strong> após eliminar tabelas</li>
                </ol>
            </div>
        </div>

        <!-- SECÇÃO 6: CHECKLIST -->
        <div class="section">
            <h2>📝 Checklist de Backup</h2>
            
            <div style="background: rgba(30, 41, 59, 0.5); padding: 15px; border-radius: 8px;">
                <div style="margin: 10px 0; padding: 10px; background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; border-radius: 4px;">
                    <input type="checkbox" id="check1"> <label for="check1">Executei o comando de backup acima</label>
                </div>
                <div style="margin: 10px 0; padding: 10px; background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; border-radius: 4px;">
                    <input type="checkbox" id="check2"> <label for="check2">Ficheiro de backup foi criado (<?php echo round($total_size / (1024*1024), 2); ?> MB esperado)</label>
                </div>
                <div style="margin: 10px 0; padding: 10px; background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; border-radius: 4px;">
                    <input type="checkbox" id="check3"> <label for="check3">Copiei o ficheiro para local seguro (fora do servidor)</label>
                </div>
                <div style="margin: 10px 0; padding: 10px; background: rgba(16, 185, 129, 0.1); border-left: 3px solid #10b981; border-radius: 4px;">
                    <input type="checkbox" id="check4"> <label for="check4">Testei a restauração do backup (recomendado)</label>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
