<?php
/**
 * ANALISE COMPLETA DE TABELAS PostgreSQL
 * 
 * Identifica:
 * - Tabelas duplicadas/redundantes
 * - Espaço ocupado por tabela
 * - Colunas similares entre tabelas
 * - Referências no código PHP
 * - Tabelas vazias/sem uso
 */

require_once '../configuracoes/base_dados.php';

// ============================================================================
// FUNÇÕES AUXILIARES
// ============================================================================

function get_all_tables($db) {
    $sql = "
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ";
    return $db->query($sql)->fetchAll(\PDO::FETCH_COLUMN);
}

function get_table_info($db, $table_name) {
    // Número de registos
    try {
        $count = $db->query("SELECT COUNT(*) FROM $table_name")->fetchColumn();
    } catch (Exception $e) {
        $count = 0;
    }
    
    // Tamanho em bytes
    $size_sql = "
        SELECT pg_total_relation_size('" . $table_name . "')::bigint as bytes
    ";
    $bytes = $db->query($size_sql)->fetchColumn() ?? 0;
    
    // Colunas
    $columns_sql = "
        SELECT column_name, data_type, is_nullable
        FROM information_schema.columns
        WHERE table_name = '$table_name' AND table_schema = 'public'
        ORDER BY ordinal_position
    ";
    $columns = $db->query($columns_sql)->fetchAll(\PDO::FETCH_ASSOC);
    
    // Foreign keys
    $fk_sql = "
        SELECT constraint_name, column_name, referenced_table_name, referenced_column_name
        FROM information_schema.key_column_usage
        WHERE table_name = '$table_name' AND table_schema = 'public' AND referenced_table_name IS NOT NULL
    ";
    try {
        $foreign_keys = $db->query($fk_sql)->fetchAll(\PDO::FETCH_ASSOC);
    } catch (Exception $e) {
        $foreign_keys = [];
    }
    
    return [
        'name' => $table_name,
        'count' => $count,
        'bytes' => $bytes,
        'kb' => round($bytes / 1024, 2),
        'mb' => round($bytes / (1024*1024), 2),
        'columns' => $columns,
        'foreign_keys' => $foreign_keys
    ];
}

function find_table_references($table_name, $code_dir = '../') {
    $references = [];
    
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($code_dir),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if ($file->getExtension() === 'php') {
            $content = file_get_contents($file->getRealPath());
            
            // Procurar por referências diretas à tabela
            $patterns = [
                "/FROM\s+['\"]?" . preg_quote($table_name) . "['\"]?/i",
                "/INTO\s+['\"]?" . preg_quote($table_name) . "['\"]?/i",
                "/UPDATE\s+['\"]?" . preg_quote($table_name) . "['\"]?/i",
                "/DELETE\s+FROM\s+['\"]?" . preg_quote($table_name) . "['\"]?/i",
                "/TABLE\s+['\"]?" . preg_quote($table_name) . "['\"]?/i",
                "/\$db->prepare\(['\"].*" . preg_quote($table_name) . "/i",
            ];
            
            foreach ($patterns as $pattern) {
                if (preg_match($pattern, $content)) {
                    $references[] = str_replace(realpath('../'), '', $file->getRealPath());
                    break;
                }
            }
        }
    }
    
    return array_unique($references);
}

function detect_duplicate_tables($tables_info) {
    $duplicates = [];
    
    // Agrupar por padrão de nomes similares
    $name_patterns = [];
    foreach ($tables_info as $table) {
        $name = $table['name'];
        
        // Remover sufixos comuns
        $base_name = preg_replace('/(s|es|_old|_backup|_temp)$/i', '', $name);
        
        if (!isset($name_patterns[$base_name])) {
            $name_patterns[$base_name] = [];
        }
        $name_patterns[$base_name][] = $name;
    }
    
    // Agrupar por estrutura de colunas similar
    $column_patterns = [];
    foreach ($tables_info as $table) {
        $col_pattern = implode('|', array_map(function($c) {
            return $c['column_name'] . ':' . $c['data_type'];
        }, $table['columns']));
        
        if (!isset($column_patterns[$col_pattern])) {
            $column_patterns[$col_pattern] = [];
        }
        $column_patterns[$col_pattern][] = $table['name'];
    }
    
    return [
        'by_name' => array_filter($name_patterns, function($v) { return count($v) > 1; }),
        'by_structure' => array_filter($column_patterns, function($v) { return count($v) > 1; })
    ];
}

// ============================================================================
// EXECUÇÃO
// ============================================================================

try {
    $db = (new Database())->getConnection();
    
    // Obter todas as tabelas
    $all_tables = get_all_tables($db);
    $tables_info = array_map(function($t) use ($db) {
        return get_table_info($db, $t);
    }, $all_tables);
    
    // Calcular estatísticas
    $total_tables = count($tables_info);
    $total_bytes = array_sum(array_column($tables_info, 'bytes'));
    $total_records = array_sum(array_column($tables_info, 'count'));
    $empty_tables = array_filter($tables_info, function($t) { return $t['count'] == 0; });
    
    // Ordenar por tamanho
    usort($tables_info, function($a, $b) { return $b['bytes'] - $a['bytes']; });
    
    // Detectar duplicatas
    $duplicates = detect_duplicate_tables($tables_info);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>📊 Análise de Tabelas PostgreSQL</title>
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
            max-width: 1400px;
            margin: 0 auto;
        }
        
        header {
            background: rgba(15, 23, 42, 0.9);
            border: 2px solid #3b82f6;
            border-radius: 12px;
            padding: 30px;
            margin-bottom: 30px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #3b82f6;
            font-size: 2em;
            margin-bottom: 10px;
        }
        
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
            gap: 15px;
            margin-top: 20px;
        }
        
        .stat-card {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 8px;
        }
        
        .stat-card h3 {
            color: #93c5fd;
            font-size: 0.9em;
            margin-bottom: 5px;
        }
        
        .stat-card .value {
            font-size: 1.8em;
            color: #3b82f6;
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
        
        .section h2 {
            color: #3b82f6;
            font-size: 1.5em;
            margin-bottom: 20px;
            padding-bottom: 10px;
            border-bottom: 2px solid #3b82f6;
        }
        
        .section h3 {
            color: #93c5fd;
            font-size: 1.1em;
            margin-top: 20px;
            margin-bottom: 10px;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 8px;
            overflow: hidden;
            margin-bottom: 20px;
        }
        
        table thead {
            background: rgba(59, 130, 246, 0.2);
        }
        
        table th {
            padding: 12px;
            text-align: left;
            color: #93c5fd;
            font-weight: 600;
            border-bottom: 1px solid #334155;
        }
        
        table td {
            padding: 10px 12px;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
        }
        
        table tr:hover {
            background: rgba(59, 130, 246, 0.05);
        }
        
        .badge {
            display: inline-block;
            padding: 4px 10px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 600;
        }
        
        .badge.empty {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        
        .badge.small {
            background: rgba(34, 197, 94, 0.2);
            color: #86efac;
        }
        
        .badge.medium {
            background: rgba(59, 130, 246, 0.2);
            color: #93c5fd;
        }
        
        .badge.large {
            background: rgba(249, 115, 22, 0.2);
            color: #fdba74;
        }
        
        .badge.huge {
            background: rgba(239, 68, 68, 0.2);
            color: #fca5a5;
        }
        
        .cols-list {
            background: rgba(30, 41, 59, 0.7);
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            font-size: 0.9em;
        }
        
        .col-item {
            margin: 5px 0;
            padding: 5px;
            border-left: 3px solid #3b82f6;
            padding-left: 10px;
        }
        
        .col-name {
            color: #93c5fd;
            font-weight: 600;
        }
        
        .col-type {
            color: #cbd5e1;
        }
        
        .refs-list {
            background: rgba(30, 41, 59, 0.7);
            padding: 12px;
            border-radius: 6px;
            margin: 10px 0;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .ref-item {
            padding: 5px;
            margin: 5px 0;
            background: rgba(59, 130, 246, 0.1);
            border-left: 3px solid #3b82f6;
            padding-left: 10px;
            border-radius: 4px;
            font-size: 0.9em;
            color: #cbd5e1;
        }
        
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border-left: 4px solid #ef4444;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            color: #fca5a5;
        }
        
        .success {
            background: rgba(34, 197, 94, 0.1);
            border-left: 4px solid #22c55e;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            color: #86efac;
        }
        
        .info {
            background: rgba(59, 130, 246, 0.1);
            border-left: 4px solid #3b82f6;
            padding: 15px;
            border-radius: 6px;
            margin: 15px 0;
            color: #93c5fd;
        }
        
        .duplicate-alert {
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin: 15px 0;
        }
        
        .duplicate-alert h4 {
            color: #fdba74;
            margin-bottom: 10px;
        }
        
        .duplicate-tables {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 10px;
        }
        
        .dup-table {
            background: rgba(30, 41, 59, 0.7);
            padding: 10px;
            border-radius: 6px;
            border-left: 3px solid #fdba74;
            color: #cbd5e1;
        }
        
        .progress-bar {
            width: 100%;
            height: 20px;
            background: rgba(30, 41, 59, 0.5);
            border-radius: 10px;
            overflow: hidden;
            margin: 10px 0;
        }
        
        .progress-fill {
            height: 100%;
            background: linear-gradient(90deg, #3b82f6, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: #fff;
            font-size: 0.8em;
            font-weight: 600;
        }
        
        .recommendations {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 20px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .recommendations h3 {
            color: #86efac;
            margin-bottom: 15px;
        }
        
        .recommendations ol {
            margin-left: 20px;
        }
        
        .recommendations li {
            margin: 8px 0;
            color: #cbd5e1;
        }
        
        .recommendations strong {
            color: #86efac;
        }
        
        code {
            background: rgba(0,0,0,0.3);
            padding: 2px 6px;
            border-radius: 4px;
            color: #86efac;
            font-family: 'Courier New', monospace;
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>📊 Análise Completa de Tabelas PostgreSQL</h1>
            <p>Identificação de tabelas duplicadas, desnecessárias e espaço desperdiçado</p>
            
            <?php if (isset($error)): ?>
                <div class="warning">
                    <strong>❌ Erro:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <div class="stats-grid">
                    <div class="stat-card">
                        <h3>📋 Total de Tabelas</h3>
                        <div class="value"><?php echo $total_tables; ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>📦 Espaço Total</h3>
                        <div class="value"><?php echo round($total_bytes / (1024*1024), 2); ?> MB</div>
                    </div>
                    <div class="stat-card">
                        <h3>📊 Total de Registos</h3>
                        <div class="value"><?php echo number_format($total_records); ?></div>
                    </div>
                    <div class="stat-card">
                        <h3>⚠️ Tabelas Vazias</h3>
                        <div class="value"><?php echo count($empty_tables); ?></div>
                    </div>
                </div>
            <?php endif; ?>
        </header>

        <?php if (!isset($error)): ?>
        
        <!-- SEÇÃO 1: RESUMO DE TABELAS POR TAMANHO -->
        <div class="section">
            <h2>1️⃣ Tabelas Ordenadas por Tamanho</h2>
            
            <table>
                <thead>
                    <tr>
                        <th style="width: 25%;">Tabela</th>
                        <th style="width: 15%;">Registos</th>
                        <th style="width: 15%;">Tamanho</th>
                        <th style="width: 15%;">Percentagem</th>
                        <th style="width: 30%;">Gráfico</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($tables_info as $table): 
                        $percentage = ($total_bytes > 0) ? ($table['bytes'] / $total_bytes * 100) : 0;
                        $size_badge = '';
                        if ($table['bytes'] == 0) {
                            $size_badge = 'empty';
                        } elseif ($table['mb'] < 1) {
                            $size_badge = 'small';
                        } elseif ($table['mb'] < 10) {
                            $size_badge = 'medium';
                        } elseif ($table['mb'] < 50) {
                            $size_badge = 'large';
                        } else {
                            $size_badge = 'huge';
                        }
                    ?>
                        <tr>
                            <td><strong><?php echo $table['name']; ?></strong></td>
                            <td><?php echo number_format($table['count']); ?></td>
                            <td>
                                <span class="badge <?php echo $size_badge; ?>">
                                    <?php 
                                    if ($table['mb'] >= 1) {
                                        echo $table['mb'] . ' MB';
                                    } else {
                                        echo $table['kb'] . ' KB';
                                    }
                                    ?>
                                </span>
                            </td>
                            <td><?php echo round($percentage, 2); ?>%</td>
                            <td>
                                <div class="progress-bar">
                                    <div class="progress-fill" style="width: <?php echo $percentage; ?>%;">
                                        <?php echo round($percentage, 1); ?>%
                                    </div>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- SEÇÃO 2: TABELAS VAZIAS -->
        <div class="section">
            <h2>2️⃣ Tabelas Vazias (Sem Registos)</h2>
            
            <?php if (!empty($empty_tables)): ?>
                <div class="warning">
                    ⚠️ Encontradas <?php echo count($empty_tables); ?> tabelas vazias que podem não estar a ser usadas.
                </div>
                
                <table>
                    <thead>
                        <tr>
                            <th style="width: 30%;">Tabela</th>
                            <th style="width: 20%;">Registos</th>
                            <th style="width: 20%;">Tamanho</th>
                            <th style="width: 30%;">Colunas</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($empty_tables as $table): ?>
                            <tr>
                                <td><strong><?php echo $table['name']; ?></strong></td>
                                <td><span class="badge empty">0</span></td>
                                <td><?php echo $table['kb'] . ' KB'; ?></td>
                                <td><?php echo count($table['columns']); ?> colunas</td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php else: ?>
                <div class="success">
                    ✅ Nenhuma tabela vazia encontrada!
                </div>
            <?php endif; ?>
        </div>

        <!-- SEÇÃO 3: DUPLICATAS POTENCIAIS -->
        <div class="section">
            <h2>3️⃣ Tabelas Potencialmente Duplicadas</h2>
            
            <?php if (!empty($duplicates['by_name']) || !empty($duplicates['by_structure'])): ?>
                
                <?php if (!empty($duplicates['by_name'])): ?>
                    <h3>📌 Por Padrão de Nome</h3>
                    <?php foreach ($duplicates['by_name'] as $base_name => $tables): ?>
                        <div class="duplicate-alert">
                            <h4>🔄 Base: <code><?php echo $base_name; ?></code></h4>
                            <div class="duplicate-tables">
                                <?php foreach ($tables as $table_name): ?>
                                    <div class="dup-table">
                                        <strong><?php echo $table_name; ?></strong>
                                        <?php 
                                        $table_info = array_column($tables_info, null, 'name')[$table_name] ?? null;
                                        if ($table_info) {
                                            echo '<br><small>' . $table_info['count'] . ' registos • ' . $table_info['kb'] . ' KB</small>';
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
                
                <?php if (!empty($duplicates['by_structure'])): ?>
                    <h3>📌 Por Estrutura Similar (Colunas Idênticas)</h3>
                    <div class="info">
                        Tabelas com exatamente as mesmas colunas e tipos de dados (potencialmente redundantes):
                    </div>
                    <?php 
                    $structure_count = 0;
                    foreach ($duplicates['by_structure'] as $structure => $tables): 
                        if (count($tables) > 1):
                            $structure_count++;
                    ?>
                        <div class="duplicate-alert">
                            <h4>🔄 Grupo de Estrutura <?php echo $structure_count; ?></h4>
                            <div class="duplicate-tables">
                                <?php foreach ($tables as $table_name): ?>
                                    <div class="dup-table">
                                        <strong><?php echo $table_name; ?></strong>
                                        <?php 
                                        $table_info = array_column($tables_info, null, 'name')[$table_name] ?? null;
                                        if ($table_info) {
                                            echo '<br><small>' . $table_info['count'] . ' registos • ' . $table_info['kb'] . ' KB</small>';
                                        }
                                        ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php 
                        endif;
                    endforeach; 
                    ?>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="success">
                    ✅ Nenhuma duplicata óbvia detectada baseado em nomes e estrutura!
                </div>
            <?php endif; ?>
        </div>

        <!-- SEÇÃO 4: DETALHES COMPLETOS DE CADA TABELA -->
        <div class="section">
            <h2>4️⃣ Detalhes Completos de Cada Tabela</h2>
            
            <?php foreach ($tables_info as $table): ?>
                <div style="margin-bottom: 30px; padding-bottom: 20px; border-bottom: 1px solid #334155;">
                    <h3 style="color: #3b82f6; margin-bottom: 10px;">
                        📌 <?php echo $table['name']; ?>
                    </h3>
                    
                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 10px; margin-bottom: 15px;">
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 6px;">
                            <div style="color: #93c5fd; font-size: 0.9em;">Registos</div>
                            <div style="color: #3b82f6; font-weight: bold; font-size: 1.3em;"><?php echo number_format($table['count']); ?></div>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 6px;">
                            <div style="color: #93c5fd; font-size: 0.9em;">Tamanho</div>
                            <div style="color: #3b82f6; font-weight: bold; font-size: 1.3em;"><?php echo $table['mb'] >= 1 ? $table['mb'] . ' MB' : $table['kb'] . ' KB'; ?></div>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 6px;">
                            <div style="color: #93c5fd; font-size: 0.9em;">Colunas</div>
                            <div style="color: #3b82f6; font-weight: bold; font-size: 1.3em;"><?php echo count($table['columns']); ?></div>
                        </div>
                        <div style="background: rgba(59, 130, 246, 0.1); padding: 10px; border-radius: 6px;">
                            <div style="color: #93c5fd; font-size: 0.9em;">Foreign Keys</div>
                            <div style="color: #3b82f6; font-weight: bold; font-size: 1.3em;"><?php echo count($table['foreign_keys']); ?></div>
                        </div>
                    </div>
                    
                    <h4 style="color: #93c5fd; margin-top: 15px; margin-bottom: 10px;">Colunas:</h4>
                    <div class="cols-list">
                        <?php foreach ($table['columns'] as $col): ?>
                            <div class="col-item">
                                <span class="col-name"><?php echo $col['column_name']; ?></span>
                                <span class="col-type">— <?php echo $col['data_type']; ?> (<?php echo $col['is_nullable'] === 'YES' ? 'NULL' : 'NOT NULL'; ?>)</span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    
                    <?php if (!empty($table['foreign_keys'])): ?>
                        <h4 style="color: #93c5fd; margin-top: 15px; margin-bottom: 10px;">Chaves Estrangeiras:</h4>
                        <div class="cols-list">
                            <?php foreach ($table['foreign_keys'] as $fk): ?>
                                <div class="col-item">
                                    <span class="col-name"><?php echo $fk['column_name']; ?></span>
                                    <span class="col-type">→ <?php echo $fk['referenced_table_name'] . '(' . $fk['referenced_column_name'] . ')'; ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php 
                    $refs = find_table_references($table['name']);
                    if (!empty($refs)): 
                    ?>
                        <h4 style="color: #93c5fd; margin-top: 15px; margin-bottom: 10px;">Referências no Código (<?php echo count($refs); ?>):</h4>
                        <div class="refs-list">
                            <?php foreach (array_slice($refs, 0, 5) as $ref): ?>
                                <div class="ref-item">📄 <?php echo htmlspecialchars($ref); ?></div>
                            <?php endforeach; ?>
                            <?php if (count($refs) > 5): ?>
                                <div class="ref-item" style="color: #fbbf24;">... e mais <?php echo count($refs) - 5; ?></div>
                            <?php endif; ?>
                        </div>
                    <?php else: ?>
                        <div class="warning" style="margin-top: 15px;">
                            ⚠️ Nenhuma referência encontrada no código PHP!
                        </div>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- SEÇÃO 5: RECOMENDAÇÕES -->
        <div class="section">
            <div class="recommendations">
                <h3>✅ Recomendações de Limpeza</h3>
                
                <ol>
                    <li>
                        <strong>Tabelas Vazias (<?php echo count($empty_tables); ?> encontradas):</strong>
                        Considere eliminar as tabelas vazias que não têm referências no código. Liberta espaço imediatamente.
                    </li>
                    
                    <li>
                        <strong>Duplicatas (<?php echo count(array_filter($duplicates['by_name'], function($v) { return count($v) > 1; })) + count(array_filter($duplicates['by_structure'], function($v) { return count($v) > 1; })); ?> grupos encontrados):</strong>
                        Analise cuidadosamente as tabelas duplicadas. Mescle dados antes de eliminar.
                    </li>
                    
                    <li>
                        <strong>Sem Referências:</strong>
                        Tabelas sem qualquer referência no código PHP podem ser desnecessárias. Confirme antes de eliminar.
                    </li>
                    
                    <li>
                        <strong>Tabelas Grandes (> 10 MB):</strong>
                        Para tabelas grandes, considere arquivar dados antigos em vez de eliminar.
                    </li>
                    
                    <li>
                        <strong>Backup Anterior:</strong>
                        Sempre faça backup completo antes de eliminar qualquer tabela!
                    </li>
                </ol>
                
                <div class="info" style="margin-top: 20px;">
                    <strong>Próximos Passos:</strong>
                    <ol style="margin: 10px 0 0 20px;">
                        <li>Verifique referências no código manualmente para tabelas suspeitas</li>
                        <li>Procure por consultas dinâmicas que podem referenciar tabelas</li>
                        <li>Considere arquivar dados antes de eliminar</li>
                        <li>Teste a plataforma depois de qualquer exclusão</li>
                    </ol>
                </div>
            </div>
        </div>

        <?php endif; ?>
    </div>
</body>
</html>
