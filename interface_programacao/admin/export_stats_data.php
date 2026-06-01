<?php
/**
 * interface_programacao/admin/export_stats_data.php
 * Exporta estatísticas de inteligência de dados em CSV ou PDF
 */
session_start();

require_once '../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado.');
}

$format = $_GET['format'] ?? 'csv';
$base_url = '../../';

// Fetch stats
$stats = [
    'users' => (int)$db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'investments' => (int)$db->query("SELECT COUNT(*) FROM project_investments WHERE status = 'paid' AND archived_at IS NULL")->fetchColumn(),
    'projects' => (int)$db->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
    'mentors' => (int)$db->query("SELECT COUNT(*) FROM users WHERE user_type = 'mentor'")->fetchColumn(),
];

$gender_stats = $db->query("SELECT COALESCE(gender, 'N/A') as label, COUNT(*) as value FROM users GROUP BY gender")->fetchAll(PDO::FETCH_ASSOC);
$location_stats = $db->query("SELECT COALESCE(location, 'Global') as label, COUNT(*) as value FROM users GROUP BY location ORDER BY value DESC LIMIT 10")->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    $filename = "estatisticas_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // Main stats
    fputcsv($output, ['ESTATÍSTICAS PRINCIPAIS']);
    fputcsv($output, []);
    fputcsv($output, ['Métrica', 'Valor']);
    foreach ($stats as $key => $value) {
        fputcsv($output, [ucfirst(str_replace('_', ' ', $key)), $value]);
    }
    
    fputcsv($output, []);
    fputcsv($output, []);
    fputcsv($output, ['DISTRIBUIÇÃO POR GÉNERO']);
    fputcsv($output, []);
    fputcsv($output, ['Género', 'Quantidade']);
    foreach ($gender_stats as $g) {
        fputcsv($output, [$g['label'], $g['value']]);
    }
    
    fputcsv($output, []);
    fputcsv($output, []);
    fputcsv($output, ['TOP 10 LOCALIZAÇÕES']);
    fputcsv($output, []);
    fputcsv($output, ['Localização', 'Utilizadores']);
    foreach ($location_stats as $l) {
        fputcsv($output, [$l['label'], $l['value']]);
    }
    
    fclose($output);
    exit;

} else if ($format === 'pdf' || $format === 'view') {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Estatísticas - KALIYE</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root { --aksanti-orange: #f7941d; --bg-dark: #0f172a; }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 25px 0; }
            .stat-card { background: #f1f5f9; padding: 20px; border-radius: 8px; border-left: 4px solid var(--aksanti-orange); text-align: center; }
            .stat-card .value { font-size: 32px; font-weight: 700; color: var(--aksanti-orange); }
            .stat-card .label { font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 8px; font-weight: 600; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; margin-top: 1.5rem; margin-bottom: 2rem; }
            th { background: var(--bg-dark); color: white; padding: 14px; font-size: 12px; text-transform: uppercase; font-weight: 700; }
            td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .section-title { font-size: 18px; font-weight: 700; margin-top: 30px; margin-bottom: 15px; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; text-decoration: none; }
            .btn-print { background: var(--aksanti-orange); color: white; }
            
            @media print { .actions { display: none; } }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir / PDF</button>
            <a href="export_stats_data.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box"><img src="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png" alt="K" style="width: 30px; height: 30px;"></div>
                <div class="title-info">
                    <h1>Relatório de Inteligência de Dados</h1>
                    <p>Análise Avançada e Estatísticas da Plataforma</p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= number_format($stats['users']) ?></div>
                <div class="label">Utilizadores</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= number_format($stats['projects']) ?></div>
                <div class="label">Projectos</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= number_format($stats['investments']) ?></div>
                <div class="label">Investimentos Pagos</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= number_format($stats['mentors']) ?></div>
                <div class="label">Mentores</div>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-venus-mars"></i> Distribuição por Género</div>
        <table>
            <thead>
                <tr>
                    <th>Género</th>
                    <th style="text-align: right;">Quantidade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($gender_stats as $g): ?>
                <tr>
                    <td><?= htmlspecialchars($g['label']) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= $g['value'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="section-title"><i class="fas fa-globe"></i> Top 10 Localizações</div>
        <table>
            <thead>
                <tr>
                    <th>Localização</th>
                    <th style="text-align: right;">Utilizadores</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($location_stats as $l): ?>
                <tr>
                    <td><?= htmlspecialchars($l['label']) ?></td>
                    <td style="text-align: right; font-weight: 600;"><?= $l['value'] ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
