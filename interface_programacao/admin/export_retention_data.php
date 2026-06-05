<?php
/**
 * interface_programacao/admin/export_retention_data.php
 * Exporta dados de retenção/arquivagem em CSV ou PDF
 */
session_start();

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/RetentionMaintenance.php';

$database = new Database();
$db = $database->getConnection();

// Validar acesso de Administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado. Privilégios de Administrador requeridos.');
}

$format = $_GET['format'] ?? 'csv';
$base_url = '../../';

// Fetch retention data
$snapshots = $db->query("
    SELECT source_table, archive_reason, COUNT(*) as count, MAX(created_at) as last_archived
    FROM data_archive_snapshots
    GROUP BY source_table, archive_reason
    ORDER BY last_archived DESC
")->fetchAll(PDO::FETCH_ASSOC);

$detailedSnapshots = $db->query("
    SELECT snapshot_id, source_table, source_pk, archive_reason, 
           created_at, LENGTH(payload) as payload_size
    FROM data_archive_snapshots
    ORDER BY created_at DESC
    LIMIT 100
")->fetchAll(PDO::FETCH_ASSOC);

// Get stats
$stats = [
    'archived_investments' => (int)$db->query("SELECT COUNT(*) FROM project_investments WHERE archived_at IS NOT NULL")->fetchColumn(),
    'archived_notifications' => (int)$db->query("SELECT COUNT(*) FROM notifications WHERE archived_at IS NOT NULL")->fetchColumn(),
    'archived_mentorships' => (int)$db->query("SELECT COUNT(*) FROM free_mentorship_requests WHERE archived_at IS NOT NULL")->fetchColumn(),
    'archived_resources' => (int)$db->query("SELECT COUNT(*) FROM mentorship_resources WHERE archived_at IS NOT NULL")->fetchColumn(),
    'total_snapshots' => (int)$db->query("SELECT COUNT(*) FROM data_archive_snapshots")->fetchColumn(),
];

// Generate CSV
if ($format === 'csv') {
    $filename = "relatorio_retencao_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    
    // Summary
    fputcsv($output, ['RESUMO DE ARQUIVAGEM']);
    fputcsv($output, []);
    fputcsv($output, ['Tipo', 'Quantidade']);
    fputcsv($output, ['Investimentos Arquivados', $stats['archived_investments']]);
    fputcsv($output, ['Notificações Arquivadas', $stats['archived_notifications']]);
    fputcsv($output, ['Mentorias Arquivadas', $stats['archived_mentorships']]);
    fputcsv($output, ['Recursos Arquivados', $stats['archived_resources']]);
    fputcsv($output, ['Total de Snapshots', $stats['total_snapshots']]);
    fputcsv($output, []);
    fputcsv($output, []);
    
    // Details by reason
    fputcsv($output, ['RAZÕES DE ARQUIVAGEM']);
    fputcsv($output, []);
    fputcsv($output, ['Tabela', 'Razão', 'Quantidade', 'Última Arquivagem']);
    foreach ($snapshots as $snapshot) {
        fputcsv($output, [
            $snapshot['source_table'],
            $snapshot['archive_reason'],
            $snapshot['count'],
            date('d/m/Y H:i', strtotime($snapshot['last_archived']))
        ]);
    }
    fputcsv($output, []);
    fputcsv($output, []);
    
    // Detailed snapshots
    fputcsv($output, ['ÚLTIMOS SNAPSHOTS (Últimas 100 arquivagens)']);
    fputcsv($output, []);
    fputcsv($output, ['ID Snapshot', 'Tabela', 'ID do Registo', 'Razão', 'Tamanho', 'Data']);
    foreach ($detailedSnapshots as $snap) {
        $sizeKB = round($snap['payload_size'] / 1024, 2);
        fputcsv($output, [
            $snap['snapshot_id'],
            $snap['source_table'],
            $snap['source_pk'],
            $snap['archive_reason'],
            $sizeKB . ' KB',
            date('d/m/Y H:i:s', strtotime($snap['created_at']))
        ]);
    }
    
    fclose($output);
    exit;

// Generate PDF/HTML
} else if ($format === 'pdf' || $format === 'view') {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Retenção de Dados - KALIYE</title>
        recursos/images/marca/favicon-16x16.ico">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --aksanti-orange: #f7941d;
                --bg-dark: #0f172a;
                --text-primary: #f8fafc;
                --text-secondary: #94a3b8;
            }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
            .logo-box img { width: 30px; height: 30px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .title-info p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
            .meta-info { text-align: right; color: #64748b; font-size: 14px; }
            
            .stats-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(150px, 1fr)); gap: 15px; margin: 25px 0; }
            .stat-card { background: #f1f5f9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--aksanti-orange); }
            .stat-card .value { font-size: 24px; font-weight: 700; color: var(--aksanti-orange); }
            .stat-card .label { font-size: 12px; color: #64748b; text-transform: uppercase; font-weight: 600; margin-top: 5px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 1.5rem; margin-bottom: 2rem; }
            th { background: var(--bg-dark); color: white; text-align: left; padding: 14px 15px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
            td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; }
            tr:last-child td { border-bottom: none; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .section-title { font-size: 18px; font-weight: 700; margin-top: 30px; margin-bottom: 15px; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
            .btn-print { background: var(--aksanti-orange); color: white; }
            .btn-print:hover { background: #e08210; }
            
            @media print {
                .actions { display: none; }
                body { padding: 0; }
                table { box-shadow: none; border: 1px solid #e2e8f0; }
                @page { margin: 1.5cm; }
            }
        </style>
        <?php 
    if (!function_exists('renderKaliyeFavicons')) {
        $root_dir_favicon = __DIR__;
        while (!is_dir($root_dir_favicon . '/inclusoes') && dirname($root_dir_favicon) !== $root_dir_favicon) {
            $root_dir_favicon = dirname($root_dir_favicon);
        }
        require_once $root_dir_favicon . '/inclusoes/components/favicon.php';
    }
    renderKaliyeFavicons($base_url ?? './'); 
    ?>
</head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir / Guardar PDF</button>
            <a href="export_retention_data.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="<?= $base_url ?>recursos/images/marca/favicon-16x16.ico" alt="KALIYE">
                </div>
                <div class="title-info">
                    <h1>Relatório de Retenção de Dados</h1>
                    <p>Gestão de Arquivagem e Snapshots de Auditoria KALIYE</p>
                </div>
            </div>
            <div class="meta-info">
                <strong>Data do Relatório:</strong> <?php echo date('d/m/Y H:i'); ?><br>
                <strong>Emitido por:</strong> Administrador (ID: <?php echo $_SESSION['user_id']; ?>)
            </div>
        </div>

        <div class="section-title"><i class="fas fa-chart-bar"></i> Resumo de Arquivagem</div>
        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $stats['archived_investments'] ?></div>
                <div class="label">Investimentos Arquivados</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['archived_notifications'] ?></div>
                <div class="label">Notificações Arquivadas</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['archived_mentorships'] ?></div>
                <div class="label">Mentorias Arquivadas</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['archived_resources'] ?></div>
                <div class="label">Recursos Arquivados</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['total_snapshots'] ?></div>
                <div class="label">Total de Snapshots</div>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-list"></i> Razões de Arquivagem</div>
        <table>
            <thead>
                <tr>
                    <th>Tabela</th>
                    <th>Razão</th>
                    <th style="text-align: right;">Quantidade</th>
                    <th>Última Arquivagem</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($snapshots)): ?>
                    <tr><td colspan="4" style="text-align: center; padding: 20px; color: #999;">Nenhuma arquivagem registada</td></tr>
                <?php else: ?>
                    <?php foreach ($snapshots as $snapshot): ?>
                    <tr>
                        <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 3px; font-size: 12px;"><?= htmlspecialchars($snapshot['source_table']) ?></code></td>
                        <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($snapshot['archive_reason']))) ?></td>
                        <td style="text-align: right; font-weight: 600;"><?= $snapshot['count'] ?></td>
                        <td><?= date('d/m/Y H:i', strtotime($snapshot['last_archived'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>

        <div class="section-title"><i class="fas fa-database"></i> Últimos Snapshots (Últimas 100 Arquivagens)</div>
        <table>
            <thead>
                <tr>
                    <th>ID Snapshot</th>
                    <th>Tabela</th>
                    <th>ID do Registo</th>
                    <th>Razão</th>
                    <th style="text-align: right;">Tamanho</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($detailedSnapshots)): ?>
                    <tr><td colspan="6" style="text-align: center; padding: 20px; color: #999;">Nenhum snapshot registado</td></tr>
                <?php else: ?>
                    <?php foreach ($detailedSnapshots as $snap): ?>
                    <tr>
                        <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 3px; font-size: 11px;">#<?= $snap['snapshot_id'] ?></code></td>
                        <td><?= htmlspecialchars($snap['source_table']) ?></td>
                        <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 3px; font-size: 11px;"><?= htmlspecialchars($snap['source_pk']) ?></code></td>
                        <td><?= htmlspecialchars(str_replace('_', ' ', ucfirst($snap['archive_reason']))) ?></td>
                        <td style="text-align: right;"><?= round($snap['payload_size'] / 1024, 2) ?> KB</td>
                        <td><?= date('d/m/Y H:i:s', strtotime($snap['created_at'])) ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
