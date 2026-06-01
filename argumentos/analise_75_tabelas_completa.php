<?php
/**
 * ANÁLISE COMPLETA DE 75 TABELAS - KALIYE
 * 
 * Identifica:
 * - Tabelas vazias (0 registos)
 * - Tabelas duplicadas (mesmo nome/estrutura)
 * - Tabelas órfãs (sem uso no código PHP)
 * - Potenciais candidatas a eliminação
 * 
 * Data: 1 de junho de 2026
 */

require_once '../configuracoes/base_dados.php';

// Escanear código PHP para referências de tabelas
function scanCodeForTableReferences() {
    $references = [];
    $phpDir = dirname(__FILE__) . '/..';
    
    // Procurar em todos os ficheiros PHP
    $files = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($phpDir, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::LEAVES_ONLY
    );
    
    foreach ($files as $file) {
        if ($file->getExtension() !== 'php') continue;
        if (strpos($file->getPathname(), '_lixo') !== false) continue;
        
        $content = file_get_contents($file->getPathname());
        
        // Padrões para encontrar referências a tabelas
        $patterns = [
            '/FROM\s+(\w+)(?:\s|;|$)/i',
            '/INTO\s+(\w+)\s*\(/i',
            '/UPDATE\s+(\w+)\s/i',
            '/DELETE\s+FROM\s+(\w+)/i',
            '/INSERT\s+INTO\s+(\w+)/i',
            '/JOIN\s+(\w+)/i',
            '/TABLE\s+(\w+)/i',
        ];
        
        foreach ($patterns as $pattern) {
            if (preg_match_all($pattern, $content, $matches)) {
                foreach ($matches[1] as $table) {
                    $table = strtolower(trim($table));
                    if (!isset($references[$table])) {
                        $references[$table] = [];
                    }
                    if (!in_array($file->getPathname(), $references[$table])) {
                        $references[$table][] = $file->getPathname();
                    }
                }
            }
        }
    }
    
    return $references;
}

try {
    $db = (new Database())->getConnection();
    
    // Obter lista de todas as tabelas
    $tables = $db->query("
        SELECT table_name FROM information_schema.tables 
        WHERE table_schema = 'public' 
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);
    
    // Obter referências de código
    $codeReferences = scanCodeForTableReferences();
    
    // Análise de cada tabela
    $analysis = [];
    $totalSize = 0;
    $totalRecords = 0;
    
    foreach ($tables as $table) {
        try {
            $size = $db->query("SELECT pg_total_relation_size('\"$table\"')::bigint")->fetchColumn() ?? 0;
            $count = $db->query("SELECT COUNT(*) FROM \"$table\"")->fetchColumn() ?? 0;
            $totalSize += $size;
            $totalRecords += $count;
            
            // Verificar estrutura (colunas)
            $columns = $db->query("
                SELECT column_name, data_type 
                FROM information_schema.columns 
                WHERE table_name = '$table' 
                ORDER BY ordinal_position
            ")->fetchAll(PDO::FETCH_ASSOC);
            
            // Verificar referências no código
            $isUsed = isset($codeReferences[$table]);
            $usageCount = $isUsed ? count($codeReferences[$table]) : 0;
            
            $analysis[$table] = [
                'size' => $size,
                'records' => $count,
                'columns' => count($columns),
                'column_list' => $columns,
                'used_in_code' => $isUsed,
                'usage_count' => $usageCount,
                'file_references' => $codeReferences[$table] ?? []
            ];
            
        } catch (Exception $e) {
            $analysis[$table] = ['error' => $e->getMessage()];
        }
    }
    
    // Categorizar tabelas
    $empty = [];
    $orphaned = [];
    $small = [];
    $duplicates = [];
    
    foreach ($analysis as $table => $info) {
        if (isset($info['error'])) continue;
        
        // Tabelas vazias
        if ($info['records'] == 0) {
            $empty[$table] = $info;
        }
        
        // Tabelas órfãs
        if (!$info['used_in_code']) {
            $orphaned[$table] = $info;
        }
        
        // Tabelas pequenas
        if ($info['size'] < 100000 && $info['records'] < 100) {
            $small[$table] = $info;
        }
    }
    
    // Procurar potenciais duplicatas
    $structureHashes = [];
    foreach ($analysis as $table => $info) {
        if (isset($info['error'])) continue;
        
        $hash = md5(json_encode($info['column_list']));
        if (!isset($structureHashes[$hash])) {
            $structureHashes[$hash] = [];
        }
        $structureHashes[$hash][] = $table;
    }
    
    $duplicates = array_filter($structureHashes, fn($v) => count($v) > 1);
    
    ?>
    <!DOCTYPE html>
    <html lang="pt-PT">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>📊 Análise 75 Tabelas - KALIYE</title>
        <style>
            * { margin: 0; padding: 0; box-sizing: border-box; }
            body {
                background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%);
                color: #e2e8f0;
                font-family: 'Segoe UI', Arial, sans-serif;
                padding: 20px;
            }
            .container { max-width: 1200px; margin: 0 auto; }
            header {
                background: rgba(15, 23, 42, 0.9);
                border: 2px solid #3b82f6;
                border-radius: 12px;
                padding: 25px;
                margin-bottom: 25px;
            }
            h1 { color: #3b82f6; margin-bottom: 10px; }
            .stats {
                display: grid;
                grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
                gap: 15px;
                margin-top: 15px;
            }
            .stat { background: rgba(16, 185, 129, 0.1); padding: 15px; border-radius: 8px; text-align: center; }
            .stat h3 { color: #86efac; font-size: 0.9em; margin-bottom: 5px; }
            .stat .value { font-size: 1.8em; color: #10b981; font-weight: bold; }
            .section {
                background: rgba(15, 23, 42, 0.9);
                border: 1px solid #334155;
                border-radius: 12px;
                padding: 20px;
                margin-bottom: 20px;
            }
            h2 { color: #f59e0b; margin-bottom: 15px; padding-bottom: 10px; border-bottom: 2px solid #f59e0b; }
            table {
                width: 100%;
                border-collapse: collapse;
                margin-top: 15px;
            }
            th, td {
                padding: 10px;
                text-align: left;
                border-bottom: 1px solid #334155;
            }
            th {
                background: rgba(59, 130, 246, 0.2);
                color: #60a5fa;
                font-weight: 600;
            }
            tr:hover { background: rgba(59, 130, 246, 0.1); }
            .empty { color: #fca5a5; }
            .orphaned { color: #fbbf24; }
            .used { color: #86efac; }
            .critical { background: rgba(239, 68, 68, 0.1); }
            .warning { background: rgba(249, 115, 22, 0.1); }
            .success { background: rgba(34, 197, 94, 0.1); }
            code {
                background: rgba(0,0,0,0.3);
                padding: 3px 6px;
                border-radius: 3px;
                color: #10b981;
                font-size: 0.9em;
            }
        </style>
    </head>
    <body>
        <div class="container">
            <header>
                <h1>📊 ANÁLISE COMPLETA - 75 TABELAS POSTGRESQL</h1>
                <p>Identificação de duplicatas, órfãs, vazias e candidatas a eliminação</p>
                <div class="stats">
                    <div class="stat">
                        <h3>Total Tabelas</h3>
                        <div class="value"><?php echo count($tables); ?></div>
                    </div>
                    <div class="stat">
                        <h3>Tabelas Vazias</h3>
                        <div class="value" style="color: #ef4444;"><?php echo count($empty); ?></div>
                    </div>
                    <div class="stat">
                        <h3>Tabelas Órfãs</h3>
                        <div class="value" style="color: #eab308;"><?php echo count($orphaned); ?></div>
                    </div>
                    <div class="stat">
                        <h3>Potenciais Duplicatas</h3>
                        <div class="value" style="color: #f59e0b;"><?php echo count($duplicates); ?></div>
                    </div>
                    <div class="stat">
                        <h3>Espaço Total</h3>
                        <div class="value"><?php echo round($totalSize / (1024*1024), 1); ?>MB</div>
                    </div>
                </div>
            </header>

            <!-- TABELAS VAZIAS -->
            <div class="section critical">
                <h2>🚫 TABELAS VAZIAS (0 registos)</h2>
                <?php if (count($empty) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tabela</th>
                                <th>Registos</th>
                                <th>Tamanho</th>
                                <th>Colunas</th>
                                <th>Usada no Código</th>
                                <th>Candidata Eliminação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($empty as $table => $info): ?>
                                <tr>
                                    <td><code><?php echo $table; ?></code></td>
                                    <td class="empty">0</td>
                                    <td><?php echo round($info['size']/1024, 2); ?>KB</td>
                                    <td><?php echo $info['columns']; ?></td>
                                    <td><?php echo $info['used_in_code'] ? '✅ Sim' : '❌ Não'; ?></td>
                                    <td><?php echo $info['used_in_code'] ? '⚠️ Cuidado' : '✅ SIM'; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>✅ Nenhuma tabela vazia encontrada</p>
                <?php endif; ?>
            </div>

            <!-- TABELAS ÓRFÃS -->
            <div class="section warning">
                <h2>🐦 TABELAS ÓRFÃS (Sem uso no código PHP)</h2>
                <?php if (count($orphaned) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tabela</th>
                                <th>Registos</th>
                                <th>Tamanho</th>
                                <th>Última Modificação</th>
                                <th>Risco</th>
                                <th>Recomendação</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($orphaned as $table => $info): 
                                $risk = $info['records'] > 100000 ? '🔴 ALTO' : ($info['records'] > 0 ? '🟡 MÉDIO' : '🟢 BAIXO');
                                $recommendation = ($info['records'] == 0) ? '✅ ELIMINAR' : '⚠️ INVESTIGAR';
                            ?>
                                <tr class="<?php echo $info['records'] == 0 ? 'success' : ''; ?>">
                                    <td><code><?php echo $table; ?></code></td>
                                    <td><?php echo number_format($info['records']); ?></td>
                                    <td><?php echo round($info['size']/(1024*1024), 2); ?>MB</td>
                                    <td>-</td>
                                    <td><?php echo $risk; ?></td>
                                    <td><?php echo $recommendation; ?></td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php else: ?>
                    <p>✅ Nenhuma tabela órfã encontrada</p>
                <?php endif; ?>
            </div>

            <!-- POTENCIAIS DUPLICATAS -->
            <?php if (count($duplicates) > 0): ?>
            <div class="section warning">
                <h2>🔄 POTENCIAIS DUPLICATAS (Mesma estrutura)</h2>
                <?php foreach ($duplicates as $hash => $duplicateTables): ?>
                    <div style="margin-bottom: 20px;">
                        <h3>Grupo: <?php echo implode(', ', array_map(fn($t) => "<code>$t</code>", $duplicateTables)); ?></h3>
                        <?php
                            $table = $duplicateTables[0];
                            if (isset($analysis[$table]['column_list'])):
                        ?>
                            <p><strong>Estrutura:</strong></p>
                            <ul style="margin-left: 20px; margin-top: 5px;">
                                <?php foreach ($analysis[$table]['column_list'] as $col): ?>
                                    <li><code><?php echo $col['column_name']; ?></code> (<?php echo $col['data_type']; ?>)</li>
                                <?php endforeach; ?>
                            </ul>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- TABELAS PEQUENAS (< 100KB, < 100 registos) -->
            <div class="section">
                <h2>📦 TABELAS PEQUENAS (Potencial Arquivo)</h2>
                <p><?php echo count($small); ?> tabelas com tamanho &lt;100KB e &lt;100 registos</p>
                <?php if (count($small) > 0): ?>
                    <table>
                        <thead>
                            <tr>
                                <th>Tabela</th>
                                <th>Registos</th>
                                <th>Tamanho</th>
                                <th>Usada</th>
                                <th>Tipo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($small as $table => $info): ?>
                                <tr>
                                    <td><code><?php echo $table; ?></code></td>
                                    <td><?php echo $info['records']; ?></td>
                                    <td><?php echo round($info['size']/1024, 2); ?>KB</td>
                                    <td><?php echo $info['used_in_code'] ? '✅' : '❌'; ?></td>
                                    <td>
                                        <?php 
                                            if ($info['records'] == 0) echo '🚫 Vazia';
                                            elseif (!$info['used_in_code']) echo '🐦 Órfã';
                                            else echo '✅ Em uso';
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                <?php endif; ?>
            </div>

            <!-- RESUMO E RECOMENDAÇÕES -->
            <div class="section success">
                <h2>✅ RESUMO E RECOMENDAÇÕES</h2>
                <div style="line-height: 1.8;">
                    <p><strong>Tabelas SEGURAS para eliminar imediatamente:</strong></p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <li><?php echo count($empty); ?> tabelas vazias (0 registos)</li>
                        <?php 
                            $emptyOrphanedCount = count(array_filter($empty, fn($v) => !$v['used_in_code']));
                            echo "<li>$emptyOrphanedCount tabelas vazias E órfãs (zero risco)</li>";
                        ?>
                    </ul>
                    
                    <p style="margin-top: 15px;"><strong>Tabelas para INVESTIGAR:</strong></p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <?php 
                            $orphanedWithData = count(array_filter($orphaned, fn($v) => $v['records'] > 0));
                            echo "<li>$orphanedWithData tabelas órfãs COM dados (investigar antes de eliminar)</li>";
                        ?>
                        <li><?php echo count($duplicates); ?> grupos de potenciais duplicatas</li>
                    </ul>
                    
                    <p style="margin-top: 15px;"><strong>Espaço total a libertar (se eliminar vazias + órfãs):</strong></p>
                    <ul style="margin-left: 20px; margin-top: 5px;">
                        <?php 
                            $potentialFreeSpace = array_reduce(
                                [...$empty, ...$orphaned],
                                fn($carry, $item) => $carry + ($item['size'] ?? 0),
                                0
                            );
                            echo "<li>" . round($potentialFreeSpace/(1024*1024), 2) . " MB</li>";
                        ?>
                    </ul>
                </div>
            </div>

            <!-- TODAS AS TABELAS (Referência) -->
            <div class="section">
                <h2>📋 TODAS AS 75 TABELAS (Referência Completa)</h2>
                <table>
                    <thead>
                        <tr>
                            <th>#</th>
                            <th>Tabela</th>
                            <th>Registos</th>
                            <th>Tamanho</th>
                            <th>Colunas</th>
                            <th>Usada</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php $idx = 1; foreach ($analysis as $table => $info): if (isset($info['error'])) continue; ?>
                            <tr>
                                <td><?php echo $idx++; ?></td>
                                <td><code><?php echo $table; ?></code></td>
                                <td><?php echo number_format($info['records']); ?></td>
                                <td><?php echo round($info['size']/(1024*1024), 2); ?>MB</td>
                                <td><?php echo $info['columns']; ?></td>
                                <td><?php echo $info['used_in_code'] ? '✅' : '❌'; ?></td>
                                <td>
                                    <?php 
                                        if ($info['records'] == 0 && !$info['used_in_code']) echo '🔴 ELIMINAR';
                                        elseif ($info['records'] == 0) echo '🟡 CUIDADO';
                                        elseif (!$info['used_in_code']) echo '🟠 ÓRFÃ';
                                        else echo '🟢 OK';
                                    ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

        </div>
    </body>
    </html>

    <?php
} catch (Exception $e) {
    echo "<pre>ERRO: " . $e->getMessage() . "</pre>";
}
?>
