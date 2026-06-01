<?php
/**
 * VERIFICADOR DE USO DE TABELAS NO CÓDIGO
 * 
 * Identifica quais tabelas estão sendo realmente usadas no código PHP
 * vs tabelas órfãs que podem ser eliminadas
 */

require_once '../configuracoes/base_dados.php';

function get_all_tables($db) {
    return $db->query("
        SELECT table_name FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ")->fetchAll(\PDO::FETCH_COLUMN);
}

function get_table_count($db, $table_name) {
    try {
        return $db->query("SELECT COUNT(*) FROM $table_name")->fetchColumn() ?: 0;
    } catch (Exception $e) {
        return 0;
    }
}

function find_references_detailed($table_name, $code_dir = '../') {
    $references = [];
    $files_checked = 0;
    
    try {
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($code_dir, RecursiveDirectoryIterator::SKIP_DOTS),
            RecursiveIteratorIterator::LEAVES_ONLY
        );
        
        foreach ($iterator as $file) {
            if (!$file->isDot() && $file->getExtension() === 'php') {
                $files_checked++;
                $file_path = $file->getRealPath();
                $relative_path = str_replace(realpath($code_dir), '', $file_path);
                
                try {
                    $content = @file_get_contents($file_path);
                    if ($content === false) continue;
                    
                    // Procurar por padrões
                    $patterns = [
                        "FROM\s+['\"]?" . preg_quote($table_name) . "['\"]?",
                        "INTO\s+['\"]?" . preg_quote($table_name) . "['\"]?",
                        "UPDATE\s+['\"]?" . preg_quote($table_name) . "['\"]?",
                        "DELETE\s+FROM\s+['\"]?" . preg_quote($table_name) . "['\"]?",
                        "JOIN\s+['\"]?" . preg_quote($table_name) . "['\"]?",
                        "TABLE\s+['\"]?" . preg_quote($table_name) . "['\"]?",
                        "\['" . preg_quote($table_name) . "'\]",
                        '\["' . preg_quote($table_name) . '"\]',
                    ];
                    
                    $lines = explode("\n", $content);
                    $found_lines = [];
                    
                    foreach ($lines as $line_num => $line) {
                        foreach ($patterns as $pattern) {
                            if (preg_match('/' . $pattern . '/i', $line)) {
                                $found_lines[] = [
                                    'line' => $line_num + 1,
                                    'content' => trim($line)
                                ];
                                break;
                            }
                        }
                    }
                    
                    if (!empty($found_lines)) {
                        $references[] = [
                            'file' => $relative_path,
                            'lines' => $found_lines
                        ];
                    }
                } catch (Exception $e) {
                    // Skip files that can't be read
                }
            }
        }
    } catch (Exception $e) {
        // Skip on error
    }
    
    return [
        'references' => $references,
        'files_checked' => $files_checked,
        'found_count' => count($references)
    ];
}

// ============================================================================
// EXECUÇÃO
// ============================================================================

try {
    $db = (new Database())->getConnection();
    $all_tables = get_all_tables($db);
    
    $table_usage = [];
    foreach ($all_tables as $table) {
        $count = get_table_count($db, $table);
        $refs = find_references_detailed($table);
        
        $table_usage[] = [
            'name' => $table,
            'count' => $count,
            'references' => $refs['references'],
            'found_in_files' => $refs['found_count'],
            'is_used' => $refs['found_count'] > 0
        ];
    }
    
    // Classificar
    usort($table_usage, function($a, $b) {
        return $b['is_used'] - $a['is_used'];
    });
    
    $used_tables = array_filter($table_usage, function($t) { return $t['is_used']; });
    $unused_tables = array_filter($table_usage, function($t) { return !$t['is_used']; });
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📚 Verificador de Uso de Tabelas</title>
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
            max-width: 1200px;
            margin: 0 auto;
        }
        
        header {
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid #10b981;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #10b981;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
            margin-top: 15px;
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
            font-size: 1.8em;
            color: #10b981;
            font-weight: bold;
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
            color: #10b981;
            font-size: 1.3em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #10b981;
        }
        
        .tabs {
            display: flex;
            gap: 10px;
            margin-bottom: 20px;
            border-bottom: 1px solid #334155;
        }
        
        .tab-button {
            background: none;
            border: none;
            color: #cbd5e1;
            padding: 10px 20px;
            cursor: pointer;
            border-bottom: 2px solid transparent;
            transition: all 0.3s;
            font-weight: 600;
        }
        
        .tab-button:hover {
            color: #10b981;
        }
        
        .tab-button.active {
            color: #10b981;
            border-bottom-color: #10b981;
        }
        
        .tab-content {
            display: none;
        }
        
        .tab-content.active {
            display: block;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            overflow: hidden;
        }
        
        table thead {
            background: rgba(16, 185, 129, 0.2);
        }
        
        table th {
            padding: 12px;
            text-align: left;
            color: #86efac;
            font-weight: 600;
            border-bottom: 1px solid #334155;
        }
        
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
        }
        
        table tr:hover {
            background: rgba(16, 185, 129, 0.05);
        }
        
        .table-row-used {
            background: rgba(34, 197, 94, 0.05);
        }
        
        .table-row-unused {
            background: rgba(239, 68, 68, 0.05);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge.used {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        
        .badge.unused {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            color: #fca5a5;
        }
        
        .success {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
            color: #86efac;
        }
        
        .ref-list {
            background: rgba(30, 41, 59, 0.7);
            padding: 12px;
            border-radius: 6px;
            margin-top: 10px;
            max-height: 150px;
            overflow-y: auto;
            font-size: 0.9em;
        }
        
        .ref-item {
            padding: 5px;
            margin: 3px 0;
            background: rgba(16, 185, 129, 0.1);
            border-left: 3px solid #10b981;
            border-radius: 3px;
            padding-left: 8px;
        }
        
        .ref-file {
            color: #86efac;
            font-weight: 600;
        }
        
        .ref-lines {
            color: #cbd5e1;
            font-size: 0.85em;
            margin-top: 3px;
        }
        
        .line-preview {
            background: rgba(20, 30, 48, 0.5);
            padding: 3px 6px;
            border-radius: 3px;
            margin-top: 2px;
            color: #cbd5e1;
            font-family: 'Courier New', monospace;
            overflow: hidden;
            text-overflow: ellipsis;
            white-space: nowrap;
            font-size: 0.8em;
        }
        
        .expandable {
            cursor: pointer;
            user-select: none;
        }
        
        .expandable.expanded + .ref-list {
            display: block !important;
        }
        
        .expand-toggle {
            display: inline-block;
            margin-right: 8px;
            transition: transform 0.2s;
        }
        
        .expandable.expanded .expand-toggle {
            transform: rotate(90deg);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📚 Verificador de Uso de Tabelas</h1>
            <p>Identifique quais tabelas estão sendo usadas vs tabelas órfãs no código PHP</p>
            
            <?php if (isset($error)): ?>
                <div class="warning">
                    <strong>❌ Erro:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>📋 Tabelas Totais</h3>
                        <div class="value"><?php echo count($table_usage); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>✅ Tabelas Usadas</h3>
                        <div class="value"><?php echo count($used_tables); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>❌ Tabelas Órfãs</h3>
                        <div class="value"><?php echo count($unused_tables); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>📊 Taxa de Uso</h3>
                        <div class="value"><?php echo count($table_usage) > 0 ? round(count($used_tables) / count($table_usage) * 100) : 0; ?>%</div>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!isset($error)): ?>
        
        <div class="section">
            <h2>📊 Análise de Uso</h2>
            
            <div class="tabs">
                <button class="tab-button active" onclick="switchTab(event, 'all-tables')">
                    📋 Todas as Tabelas (<?php echo count($table_usage); ?>)
                </button>
                <button class="tab-button" onclick="switchTab(event, 'used-tables')">
                    ✅ Usadas (<?php echo count($used_tables); ?>)
                </button>
                <button class="tab-button" onclick="switchTab(event, 'unused-tables')">
                    ❌ Órfãs (<?php echo count($unused_tables); ?>)
                </button>
            </div>
            
            <!-- TAB: TODAS AS TABELAS -->
            <div id="all-tables" class="tab-content active">
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Tabela</th>
                            <th style="width: 15%;">Registos</th>
                            <th style="width: 15%;">Status</th>
                            <th style="width: 40%;">Uso no Código</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($table_usage as $table): ?>
                            <tr class="<?php echo $table['is_used'] ? 'table-row-used' : 'table-row-unused'; ?>">
                                <td><strong><?php echo $table['name']; ?></strong></td>
                                <td><?php echo number_format($table['count']); ?></td>
                                <td>
                                    <span class="badge <?php echo $table['is_used'] ? 'used' : 'unused'; ?>">
                                        <?php echo $table['is_used'] ? '✅ Usada' : '❌ Órfã'; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($table['is_used']): ?>
                                        <strong style="color: #86efac;">Encontrada em <?php echo $table['found_in_files']; ?> ficheiro(s)</strong>
                                        <div class="ref-list" style="display: none;">
                                            <?php foreach ($table['references'] as $ref): ?>
                                                <div class="ref-item">
                                                    <div class="ref-file">📄 <?php echo htmlspecialchars($ref['file']); ?></div>
                                                    <div class="ref-lines">
                                                        Linhas: <?php echo implode(', ', array_map(function($l) { return '#' . $l['line']; }, $ref['lines'])); ?>
                                                    </div>
                                                    <?php foreach (array_slice($ref['lines'], 0, 2) as $line): ?>
                                                        <div class="line-preview">
                                                            → <?php echo htmlspecialchars($line['content']); ?>
                                                        </div>
                                                    <?php endforeach; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                        <div class="expandable" onclick="toggleDetails(this)" style="color: #86efac; cursor: pointer;">
                                            <span class="expand-toggle">▶</span>Ver detalhes
                                        </div>
                                    <?php else: ?>
                                        <strong style="color: #fca5a5;">❌ Nenhuma referência encontrada</strong>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- TAB: TABELAS USADAS -->
            <div id="used-tables" class="tab-content">
                <div class="success">
                    ✅ <?php echo count($used_tables); ?> tabelas estão sendo usadas no código PHP
                </div>
                
                <table style="margin-top: 15px;">
                    <thead>
                        <tr>
                            <th style="width: 30%;">Tabela</th>
                            <th style="width: 15%;">Registos</th>
                            <th style="width: 20%;">Ficheiros</th>
                            <th style="width: 35%;">Referências</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($used_tables as $table): ?>
                            <tr class="table-row-used">
                                <td><strong><?php echo $table['name']; ?></strong></td>
                                <td><?php echo number_format($table['count']); ?></td>
                                <td><span style="color: #86efac; font-weight: 600;"><?php echo $table['found_in_files']; ?></span></td>
                                <td>
                                    <div class="ref-list">
                                        <?php foreach ($table['references'] as $ref): ?>
                                            <div class="ref-item">
                                                <div class="ref-file">📄 <?php echo htmlspecialchars($ref['file']); ?></div>
                                                <div class="ref-lines">Linhas: <?php echo implode(', ', array_map(function($l) { return '#' . $l['line']; }, array_slice($ref['lines'], 0, 3))); ?></div>
                                            </div>
                                        <?php endforeach; ?>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            
            <!-- TAB: TABELAS ÓRFÃS -->
            <div id="unused-tables" class="tab-content">
                <?php if (!empty($unused_tables)): ?>
                    <div class="warning">
                        ⚠️ <?php echo count($unused_tables); ?> tabelas NÃO têm referências no código PHP.
                        <br><br>
                        <strong>Estas tabelas são CANDIDATAS A ELIMINAÇÃO:</strong>
                        <ul style="margin: 10px 0 0 20px;">
                            <li>Verifique se são de importância crítica antes de eliminar</li>
                            <li>Faça backup completo da base de dados primeiro</li>
                            <li>Procure por referências dinâmicas (variáveis de tabela)</li>
                            <li>Verifique se há queries construídas dinamicamente</li>
                        </ul>
                    </div>
                    
                    <table style="margin-top: 15px;">
                        <thead>
                            <tr>
                                <th style="width: 30%;">Tabela</th>
                                <th style="width: 20%;">Registos</th>
                                <th style="width: 50%;">Recomendação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($unused_tables as $table): ?>
                                <tr class="table-row-unused">
                                    <td><strong><?php echo $table['name']; ?></strong></td>
                                    <td><?php echo number_format($table['count']); ?></td>
                                    <td>
                                        <?php if ($table['count'] == 0): ?>
                                            <span style="color: #fca5a5;">🗑️ SEGURO ELIMINAR - tabela vazia</span>
                                        <?php elseif ($table['count'] > 100000): ?>
                                            <span style="color: #fca5a5;">⚠️ CAUTELA - tabela grande (<?php echo number_format($table['count']); ?> registos), ARQUIVO antes de eliminar</span>
                                        <?php elseif ($table['count'] > 1000): ?>
                                            <span style="color: #fdba74;">⚡ BACKUP primeiro, depois avaliar se pode eliminar</span>
                                        <?php else: ?>
                                            <span style="color: #86efac;">🔍 Verificar com PM se é seguro eliminar</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <div class="success">
                        ✅ Excelente! Nenhuma tabela órfã encontrada. Todas as tabelas estão a ser usadas.
                    </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- RECOMENDAÇÕES FINAIS -->
        <div class="section">
            <h2>💡 Recomendações Finais</h2>
            
            <div style="background: rgba(34, 197, 94, 0.1); border: 1px solid rgba(34, 197, 94, 0.3); padding: 15px; border-radius: 8px; color: #86efac; margin-bottom: 15px;">
                <strong>Antes de Eliminar Qualquer Tabela:</strong>
                <ol style="margin: 10px 0 0 20px;">
                    <li>Faça <strong>BACKUP COMPLETO</strong> da base de dados PostgreSQL</li>
                    <li>Verifique se há <strong>queries dinâmicas</strong> construídas em tempo de execução</li>
                    <li>Procure no código por <strong>variáveis que armazenam nomes de tabelas</strong></li>
                    <li>Verifique se há <strong>histórico/auditoria</strong> que referenvia a tabela</li>
                    <li>Teste a plataforma após <strong>cada eliminação</strong></li>
                    <li>Documente cada tabela eliminada no <strong>histórico de mudanças</strong></li>
                </ol>
            </div>
            
            <div style="background: rgba(249, 115, 22, 0.1); border: 1px solid rgba(249, 115, 22, 0.3); padding: 15px; border-radius: 8px; color: #fdba74;">
                <strong>Estratégia Recomendada:</strong>
                <ol style="margin: 10px 0 0 20px;">
                    <li>Elimine tabelas <strong>VAZIAS</strong> primeiro (sem risco)</li>
                    <li>Depois tabelas <strong>órfãs com poucos registos</strong> (&lt; 100)</li>
                    <li>Tabelas <strong>órfãs grandes</strong> → ARQUIVO para backup histórico</li>
                    <li>Mantenha tabelas <strong>usadas</strong> no sistema ativo</li>
                </ol>
            </div>
        </div>

        <?php endif; ?>
    </div>
    
    <script>
        function switchTab(event, tabName) {
            // Remover classe active de todos os botões
            document.querySelectorAll('.tab-button').forEach(btn => btn.classList.remove('active'));
            document.querySelectorAll('.tab-content').forEach(content => content.classList.remove('active'));
            
            // Adicionar active ao clicado
            event.target.classList.add('active');
            document.getElementById(tabName).classList.add('active');
        }
        
        function toggleDetails(element) {
            element.classList.toggle('expanded');
        }
    </script>
</body>
</html>
