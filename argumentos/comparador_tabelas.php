<?php
/**
 * COMPARADOR DE TABELAS POTENCIALMENTE DUPLICADAS
 * 
 * Compara estrutura, dados e referências entre tabelas similares
 */

require_once '../configuracoes/base_dados.php';

function get_all_tables($db) {
    return $db->query("
        SELECT table_name FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ")->fetchAll(\PDO::FETCH_COLUMN);
}

function get_table_structure($db, $table_name) {
    $columns = $db->query("
        SELECT column_name, data_type, is_nullable, column_default
        FROM information_schema.columns
        WHERE table_name = '$table_name' AND table_schema = 'public'
        ORDER BY ordinal_position
    ")->fetchAll(\PDO::FETCH_ASSOC);
    
    return $columns;
}

function compare_tables($db, $table1, $table2) {
    $struct1 = get_table_structure($db, $table1);
    $struct2 = get_table_structure($db, $table2);
    
    $cols1 = array_column($struct1, 'column_name');
    $cols2 = array_column($struct2, 'column_name');
    
    $only_in_1 = array_diff($cols1, $cols2);
    $only_in_2 = array_diff($cols2, $cols1);
    $common = array_intersect($cols1, $cols2);
    
    // Contar registos
    try {
        $count1 = $db->query("SELECT COUNT(*) FROM $table1")->fetchColumn();
        $count2 = $db->query("SELECT COUNT(*) FROM $table2")->fetchColumn();
    } catch (Exception $e) {
        $count1 = $count2 = 0;
    }
    
    // Tamanho
    $size1 = $db->query("SELECT pg_total_relation_size('$table1')::bigint")->fetchColumn() ?? 0;
    $size2 = $db->query("SELECT pg_total_relation_size('$table2')::bigint")->fetchColumn() ?? 0;
    
    return [
        'table1' => $table1,
        'table2' => $table2,
        'struct1' => $struct1,
        'struct2' => $struct2,
        'only_in_1' => $only_in_1,
        'only_in_2' => $only_in_2,
        'common_columns' => count($common),
        'count1' => $count1,
        'count2' => $count2,
        'size1' => $size1,
        'size2' => $size2,
        'similarity' => count($common) > 0 ? (count($common) / max(count($cols1), count($cols2))) * 100 : 0
    ];
}

// ============================================================================
// EXECUÇÃO
// ============================================================================

try {
    $db = (new Database())->getConnection();
    $all_tables = get_all_tables($db);
    
} catch (Exception $e) {
    $error = $e->getMessage();
}

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>🔍 Comparador de Tabelas</title>
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
            border: 2px solid #8b5cf6;
            border-radius: 12px;
            padding: 25px;
            margin-bottom: 25px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.5);
        }
        
        h1 {
            color: #8b5cf6;
            margin-bottom: 10px;
        }
        
        .form-group {
            margin: 15px 0;
        }
        
        label {
            display: inline-block;
            color: #cbd5e1;
            margin-right: 10px;
            font-weight: 600;
        }
        
        select {
            background: rgba(30, 41, 59, 0.8);
            color: #e2e8f0;
            border: 1px solid #334155;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 1em;
            margin-right: 10px;
        }
        
        button {
            background: #8b5cf6;
            color: white;
            border: none;
            padding: 10px 20px;
            border-radius: 6px;
            cursor: pointer;
            font-weight: 600;
            transition: background 0.3s;
        }
        
        button:hover {
            background: #7c3aed;
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
            color: #8b5cf6;
            font-size: 1.3em;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #8b5cf6;
        }
        
        .comparison-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 20px;
            margin-bottom: 20px;
        }
        
        .table-card {
            background: rgba(30, 41, 59, 0.7);
            border: 1px solid #334155;
            border-radius: 8px;
            padding: 15px;
        }
        
        .table-card h3 {
            color: #8b5cf6;
            margin-bottom: 10px;
        }
        
        .stat-row {
            display: flex;
            justify-content: space-between;
            padding: 8px 0;
            border-bottom: 1px solid rgba(51, 65, 85, 0.3);
        }
        
        .stat-row:last-child {
            border-bottom: none;
        }
        
        .stat-label {
            color: #cbd5e1;
        }
        
        .stat-value {
            color: #8b5cf6;
            font-weight: 600;
        }
        
        .col-list {
            background: rgba(20, 30, 48, 0.5);
            padding: 10px;
            border-radius: 6px;
            margin-top: 10px;
            max-height: 200px;
            overflow-y: auto;
        }
        
        .col-item {
            padding: 4px 8px;
            margin: 2px 0;
            background: rgba(139, 92, 246, 0.1);
            border-left: 2px solid #8b5cf6;
            padding-left: 8px;
            font-size: 0.9em;
            border-radius: 3px;
        }
        
        .col-item.only-1 {
            background: rgba(239, 68, 68, 0.1);
            border-left-color: #ef4444;
        }
        
        .col-item.only-2 {
            background: rgba(34, 197, 94, 0.1);
            border-left-color: #22c55e;
        }
        
        .col-item.common {
            background: rgba(59, 130, 246, 0.1);
            border-left-color: #3b82f6;
        }
        
        .similarity-bar {
            width: 100%;
            height: 30px;
            background: rgba(20, 30, 48, 0.5);
            border-radius: 6px;
            overflow: hidden;
            margin-top: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
        }
        
        .similarity-fill {
            height: 100%;
            background: linear-gradient(90deg, #8b5cf6, #06b6d4);
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 600;
        }
        
        .differences {
            background: rgba(249, 115, 22, 0.1);
            border: 1px solid rgba(249, 115, 22, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
        }
        
        .diff-list {
            margin-top: 10px;
        }
        
        .diff-item {
            padding: 8px;
            margin: 5px 0;
            background: rgba(30, 41, 59, 0.5);
            border-left: 3px solid #fdba74;
            border-radius: 4px;
        }
        
        .recommendation {
            background: rgba(34, 197, 94, 0.1);
            border: 1px solid rgba(34, 197, 94, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #86efac;
        }
        
        .warning {
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.3);
            padding: 15px;
            border-radius: 8px;
            margin-top: 20px;
            color: #fca5a5;
        }
        
        table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 15px;
        }
        
        table thead {
            background: rgba(139, 92, 246, 0.2);
        }
        
        table th, table td {
            padding: 10px;
            text-align: left;
            border-bottom: 1px solid #334155;
        }
        
        table tr:hover {
            background: rgba(139, 92, 246, 0.05);
        }
    </style>
</head>
<body>
    <div class="container">
        <header>
            <h1>🔍 Comparador de Tabelas PostgreSQL</h1>
            <p>Identifique diferenças e similaridades entre tabelas potencialmente duplicadas</p>
            
            <?php if (isset($error)): ?>
                <div style="color: #fca5a5; margin-top: 15px;">
                    <strong>❌ Erro:</strong> <?php echo htmlspecialchars($error); ?>
                </div>
            <?php else: ?>
                <form method="POST" style="margin-top: 20px;">
                    <div class="form-group">
                        <label for="table1">Tabela 1:</label>
                        <select name="table1" id="table1">
                            <option value="">-- Selecione uma tabela --</option>
                            <?php foreach ($all_tables as $table): ?>
                                <option value="<?php echo $table; ?>" <?php echo ($_POST['table1'] ?? '') === $table ? 'selected' : ''; ?>>
                                    <?php echo $table; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <div class="form-group">
                        <label for="table2">Tabela 2:</label>
                        <select name="table2" id="table2">
                            <option value="">-- Selecione uma tabela --</option>
                            <?php foreach ($all_tables as $table): ?>
                                <option value="<?php echo $table; ?>" <?php echo ($_POST['table2'] ?? '') === $table ? 'selected' : ''; ?>>
                                    <?php echo $table; ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    
                    <button type="submit">🔍 Comparar Tabelas</button>
                </form>
            <?php endif; ?>
        </header>

        <?php 
        if (!isset($error) && $_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['table1']) && !empty($_POST['table2'])):
            $comparison = compare_tables($db, $_POST['table1'], $_POST['table2']);
        ?>
        
        <div class="section">
            <h2>📊 Comparação: <?php echo $comparison['table1']; ?> vs <?php echo $comparison['table2']; ?></h2>
            
            <div class="comparison-grid">
                <!-- TABELA 1 -->
                <div class="table-card">
                    <h3>📌 <?php echo $comparison['table1']; ?></h3>
                    
                    <div class="stat-row">
                        <span class="stat-label">Registos:</span>
                        <span class="stat-value"><?php echo number_format($comparison['count1']); ?></span>
                    </div>
                    
                    <div class="stat-row">
                        <span class="stat-label">Tamanho:</span>
                        <span class="stat-value"><?php echo round($comparison['size1'] / 1024, 2); ?> KB</span>
                    </div>
                    
                    <div class="stat-row">
                        <span class="stat-label">Colunas:</span>
                        <span class="stat-value"><?php echo count($comparison['struct1']); ?></span>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <div style="color: #cbd5e1; font-size: 0.9em; margin-bottom: 5px;">Colunas:</div>
                        <div class="col-list">
                            <?php foreach ($comparison['struct1'] as $col): ?>
                                <div class="col-item <?php echo in_array($col['column_name'], $comparison['only_in_1']) ? 'only-1' : (in_array($col['column_name'], $comparison['only_in_2']) ? '' : 'common'); ?>">
                                    <strong><?php echo $col['column_name']; ?></strong>
                                    <small> — <?php echo $col['data_type']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                
                <!-- TABELA 2 -->
                <div class="table-card">
                    <h3>📌 <?php echo $comparison['table2']; ?></h3>
                    
                    <div class="stat-row">
                        <span class="stat-label">Registos:</span>
                        <span class="stat-value"><?php echo number_format($comparison['count2']); ?></span>
                    </div>
                    
                    <div class="stat-row">
                        <span class="stat-label">Tamanho:</span>
                        <span class="stat-value"><?php echo round($comparison['size2'] / 1024, 2); ?> KB</span>
                    </div>
                    
                    <div class="stat-row">
                        <span class="stat-label">Colunas:</span>
                        <span class="stat-value"><?php echo count($comparison['struct2']); ?></span>
                    </div>
                    
                    <div style="margin-top: 10px;">
                        <div style="color: #cbd5e1; font-size: 0.9em; margin-bottom: 5px;">Colunas:</div>
                        <div class="col-list">
                            <?php foreach ($comparison['struct2'] as $col): ?>
                                <div class="col-item <?php echo in_array($col['column_name'], $comparison['only_in_2']) ? 'only-2' : (in_array($col['column_name'], $comparison['only_in_1']) ? '' : 'common'); ?>">
                                    <strong><?php echo $col['column_name']; ?></strong>
                                    <small> — <?php echo $col['data_type']; ?></small>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- SIMILARIDADE -->
            <div style="margin-top: 20px;">
                <h3 style="color: #8b5cf6; margin-bottom: 10px;">Similaridade de Estrutura</h3>
                <div class="similarity-bar">
                    <div class="similarity-fill" style="width: <?php echo $comparison['similarity']; ?>%;">
                        <?php echo round($comparison['similarity'], 1); ?>% similar
                    </div>
                </div>
                <div style="margin-top: 10px; color: #cbd5e1; text-align: center;">
                    <?php echo $comparison['common_columns']; ?> colunas comuns de <?php echo max(count($comparison['struct1']), count($comparison['struct2'])); ?> totais
                </div>
            </div>
        </div>
        
        <!-- DIFERENÇAS -->
        <?php if (!empty($comparison['only_in_1']) || !empty($comparison['only_in_2'])): ?>
            <div class="section">
                <h2>⚠️ Diferenças Estruturais</h2>
                
                <div class="differences">
                    <?php if (!empty($comparison['only_in_1'])): ?>
                        <h3 style="color: #ef4444; margin-bottom: 10px;">❌ Apenas em <?php echo $comparison['table1']; ?>:</h3>
                        <div class="diff-list">
                            <?php foreach ($comparison['only_in_1'] as $col): ?>
                                <div class="diff-item">
                                    <strong><?php echo $col; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (!empty($comparison['only_in_2'])): ?>
                        <h3 style="color: #22c55e; margin-top: 15px; margin-bottom: 10px;">✅ Apenas em <?php echo $comparison['table2']; ?>:</h3>
                        <div class="diff-list">
                            <?php foreach ($comparison['only_in_2'] as $col): ?>
                                <div class="diff-item" style="border-left-color: #22c55e;">
                                    <strong><?php echo $col; ?></strong>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
        
        <!-- RECOMENDAÇÃO -->
        <div class="section">
            <h2>💡 Análise e Recomendação</h2>
            
            <?php 
            $similarity = $comparison['similarity'];
            $data_ratio = max($comparison['count1'], $comparison['count2']) > 0 
                ? min($comparison['count1'], $comparison['count2']) / max($comparison['count1'], $comparison['count2']) 
                : 0;
            
            if ($similarity >= 90):
            ?>
                <div class="warning">
                    <strong>⚠️ ATENÇÃO: Tabelas Potencialmente Duplicadas!</strong>
                    <p style="margin-top: 10px;">
                        Estas tabelas têm uma similaridade de estrutura de <strong><?php echo round($similarity, 1); ?>%</strong>, 
                        o que indica que podem estar a armazenar dados similares ou redundantes.
                    </p>
                </div>
                
                <div class="recommendation" style="margin-top: 15px;">
                    <strong>✅ Próximos Passos:</strong>
                    <ol style="margin: 10px 0 0 20px;">
                        <li>Analise manualmente os dados em ambas as tabelas</li>
                        <li>Verifique se há registos duplicados com mesmos valores-chave</li>
                        <li>Consolide dados se forem totalmente redundantes</li>
                        <li>Elimine a tabela menos usada (verificar referências no código)</li>
                        <li>Faça backup antes de qualquer operação</li>
                    </ol>
                </div>
            <?php elseif ($similarity >= 70): ?>
                <div class="warning">
                    <strong>⚠️ POSSÍVEL Sobreposição de Funcionalidade</strong>
                    <p style="margin-top: 10px;">
                        Similaridade de <strong><?php echo round($similarity, 1); ?>%</strong> sugere que podem ter propósitos similares, 
                        mas diferenças estruturais específicas.
                    </p>
                </div>
            <?php else: ?>
                <div class="recommendation">
                    <strong>✅ Tabelas Suficientemente Diferentes</strong>
                    <p style="margin-top: 10px;">
                        Com similaridade de apenas <strong><?php echo round($similarity, 1); ?>%</strong>, parecem ter propósitos distintos. 
                        Ambas parecem necessárias.
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <?php endif; ?>
    </div>
</body>
</html>
