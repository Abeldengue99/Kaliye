<?php
// admin/finance/export_finance.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('finance_docs')) {
    die("Acesso negado.");
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// 1. Get Financial Overview
$financial_stats = $db->query("
    SELECT 
        SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_disbursed,
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_held,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as potential_pipeline,
        currency
    FROM project_investments
    GROUP BY currency
")->fetchAll(PDO::FETCH_ASSOC);

// 2. Get Investment List
$investments = $db->query("
    SELECT 
        pi.*, p.title as project_title, p.owner_id, 
        i.full_name as investor_name, o.full_name as owner_name
    FROM project_investments pi
    JOIN projects p ON pi.project_id = p.project_id
    JOIN users i ON pi.investor_id = i.user_id
    JOIN users o ON p.owner_id = o.user_id
    ORDER BY pi.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kaliye_finance_report_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM

    // Section 1: KPIs
    fputcsv($output, ['RESUMO FINANCEIRO POR MOEDA']);
    fputcsv($output, ['Moeda', 'Total Pago', 'Total Retido', 'Pipeline']);
    foreach ($financial_stats as $stat) {
        fputcsv($output, [
            $stat['currency'],
            number_format($stat['total_disbursed'], 2, '.', ''),
            number_format($stat['total_held'], 2, '.', ''),
            number_format($stat['potential_pipeline'], 2, '.', '')
        ]);
    }
    fputcsv($output, []); // Empty line

    // Section 2: Detailed Investments
    fputcsv($output, ['DETALHE DE INVESTIMENTOS']);
    fputcsv($output, ['ID', 'Projeto', 'Investidor', 'Dono do Projeto', 'Valor', 'Moeda', 'Estado', 'Data']);
    foreach ($investments as $inv) {
        fputcsv($output, [
            $inv['investment_id'],
            $inv['project_title'],
            $inv['investor_name'],
            $inv['owner_name'],
            number_format($inv['amount'], 2, '.', ''),
            $inv['currency'],
            $inv['status'],
            $inv['created_at']
        ]);
    }
    fclose($output);
    exit;
} else if ($format === 'view' || $format === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório Financeiro Consolidado - KALIYE</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --aksanti-orange: #f7941d;
                --accent-gold: #fbbf24;
                --bg-dark: #0f172a;
            }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
            .logo-box img { width: 30px; height: 30px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .meta-info { text-align: right; color: #64748b; font-size: 14px; }
            
            .kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-bottom: 40px; }
            .kpi-card { background: white; padding: 20px; border-radius: 12px; border-left: 4px solid var(--aksanti-orange); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            .kpi-label { color: #64748b; font-size: 12px; text-transform: uppercase; font-weight: 700; }
            .kpi-value { font-size: 24px; font-weight: 800; margin-top: 5px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            th { background: #0f172a; color: white; text-align: left; padding: 12px 15px; font-size: 12px; text-transform: uppercase; }
            td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .badge { padding: 4px 10px; border-radius: 20px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
            .status-paid { background: #dcfce7; color: #166534; }
            .status-approved { background: #dbeafe; color: #1e40af; }
            .status-pending { background: #fef3c7; color: #92400e; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
            
            @media print {
                .actions { display: none; }
                body { padding: 0; }
                @page { margin: 1cm; }
            }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn" style="background: var(--aksanti-orange); color: white;"><i class="fas fa-print"></i> Imprimir / PDF</button>
            <a href="export_finance.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> CSV</a>
        </div>

        <div class="header">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="../../recursos/images/marca/favicon-k-32x32.png" alt="KALIYE">
                </div>
                <div class="title-info">
                    <h1>Relatório Financeiro Consolidado</h1>
                    <p>Gestão de Fluxo de Capital - KALIYE</p>
                </div>
            </div>
            <div class="meta-info">
                <strong>Data:</strong> <?php echo date('d/m/Y H:i'); ?>
            </div>
        </div>

        <div class="kpi-grid">
            <?php foreach ($financial_stats as $stat): ?>
                <div class="kpi-card">
                    <div class="kpi-label">Volume Total (<?php echo $stat['currency']; ?>)</div>
                    <div class="kpi-value"><?php echo number_format($stat['total_disbursed'] + $stat['total_held'], 2, ',', '.') . ' ' . $stat['currency']; ?></div>
                    <div style="font-size: 11px; margin-top: 10px; color: #64748b;">
                        Pago: <?php echo number_format($stat['total_disbursed'], 2, ',', '.'); ?> | 
                        Retido: <?php echo number_format($stat['total_held'], 2, ',', '.'); ?>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Projeto</th>
                    <th>Partes Involvement</th>
                    <th>Valor</th>
                    <th>Estado</th>
                    <th>Data</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($investments as $inv): ?>
                <tr>
                    <td>#<?php echo $inv['investment_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($inv['project_title']); ?></strong></td>
                    <td>
                        <span style="font-size: 11px;">I: <?php echo htmlspecialchars($inv['investor_name']); ?></span><br>
                        <span style="font-size: 11px;">O: <?php echo htmlspecialchars($inv['owner_name']); ?></span>
                    </td>
                    <td><strong><?php echo number_format($inv['amount'], 2, ',', '.') . ' ' . $inv['currency']; ?></strong></td>
                    <td>
                        <span class="badge status-<?php echo $inv['status']; ?>">
                            <?php echo $inv['status']; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($inv['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}

