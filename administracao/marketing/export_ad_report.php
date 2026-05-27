<?php
// admin/marketing/export_ad_report.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('ads')) {
    die("Acesso negado.");
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
$db->exec("CREATE TABLE IF NOT EXISTS ad_metrics (
    metric_id SERIAL PRIMARY KEY,
    ad_id INT NOT NULL,
    metric_type VARCHAR(20) NOT NULL,
    user_id INT NULL,
    ip_address VARCHAR(45),
    user_agent TEXT,
    referrer TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");
$db->exec("ALTER TABLE ad_metrics ADD COLUMN IF NOT EXISTS metric_type VARCHAR(20) NOT NULL DEFAULT 'view'");
$db->exec("ALTER TABLE ad_metrics ADD COLUMN IF NOT EXISTS user_id INT NULL");
$db->exec("ALTER TABLE ad_metrics ADD COLUMN IF NOT EXISTS ip_address VARCHAR(45)");
$db->exec("ALTER TABLE ad_metrics ADD COLUMN IF NOT EXISTS user_agent TEXT");
$db->exec("ALTER TABLE ad_metrics ADD COLUMN IF NOT EXISTS referrer TEXT");
$db->exec("ALTER TABLE ad_metrics ADD COLUMN IF NOT EXISTS created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP");

$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;
if ($ad_id <= 0) die("ID Inválido");

// 1. Fetch Ad Details
$ad_stmt = $db->prepare("SELECT * FROM ads WHERE ad_id = ?");
$ad_stmt->execute([$ad_id]);
$ad = $ad_stmt->fetch(PDO::FETCH_ASSOC);
if (!$ad) die("Anúncio não encontrado");

// 2. Fetch Metrics from real event ledger (ad_metrics)
$totals_stmt = $db->prepare("SELECT
    COUNT(*) FILTER (WHERE metric_type = 'view') AS total_views,
    COUNT(*) FILTER (WHERE metric_type = 'click') AS total_clicks,
    COUNT(DISTINCT COALESCE(user_id::text, md5(COALESCE(ip_address, '') || COALESCE(user_agent, '')))) AS reach
    FROM ad_metrics WHERE ad_id = ?");
$totals_stmt->execute([$ad_id]);
$totals = $totals_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
$total_views = (int)($totals['total_views'] ?? 0);
$total_clicks = (int)($totals['total_clicks'] ?? 0);
$unique_users = (int)($totals['reach'] ?? 0);
$ctr = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;
$cpc = $total_clicks > 0 ? round($ad['budget'] / $total_clicks, 2) : 0;
$cpv = $total_views > 0 ? round($ad['budget'] / $total_views, 2) : 0;
$daily_stmt = $db->prepare("
    SELECT created_at::DATE as date, metric_type, COUNT(*) as count 
    FROM ad_metrics WHERE ad_id = ? AND created_at >= NOW() - INTERVAL '30 days'
    GROUP BY created_at::DATE, metric_type ORDER BY date DESC
");
$daily_stmt->execute([$ad_id]);
$daily_metrics = $daily_stmt->fetchAll(PDO::FETCH_ASSOC);

$format = $_GET['format'] ?? 'view';

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kaliye_ad_report_' . $ad_id . '_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, ['METRICAS DA CAMPANHA: ' . $ad['title']]);
    fputcsv($output, ['Visualizações Totais', 'Cliques Totais', 'CTR (%)', 'Alcance Real', 'CPC (Custo p/ Clique)', 'CPV (Custo p/ View)', 'Orçamento Total']);
    fputcsv($output, [$total_views, $total_clicks, $ctr . '%', $unique_users, $cpc, $cpv, $ad['budget']]);
    fputcsv($output, []);
    fputcsv($output, ['HISTÓRICO DIÁRIO (Últimos 30 Dias)']);
    fputcsv($output, ['Data', 'Tipo de Métrica', 'Contagem']);
    foreach($daily_metrics as $dm) {
        fputcsv($output, [$dm['date'], $dm['metric_type'], $dm['count']]);
    }
    fclose($output);
    exit;
} else {
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Performance - <?php echo htmlspecialchars($ad['title']); ?></title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --aksanti-orange: #f7941d; --accent-gold: #fbbf24; --bg-dark: #0f172a; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
        .logo-box img { width: 30px; height: 30px; }
        .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
        .meta-info { text-align: right; color: #64748b; font-size: 14px; }
        
        .kpi-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 20px; margin-bottom: 40px; }
        .kpi-card { background: white; padding: 20px; border-radius: 12px; border-top: 4px solid var(--aksanti-orange); box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); text-align: center; }
        .kpi-label { color: #64748b; font-size: 10px; text-transform: uppercase; font-weight: 700; margin-bottom: 5px; }
        .kpi-value { font-size: 22px; font-weight: 800; color: #0f172a; }

        .details-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 40px; margin-bottom: 40px; }
        .detail-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        .detail-box h3 { margin: 0 0 15px 0; font-size: 14px; text-transform: uppercase; color: var(--aksanti-orange); border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
        .info-row { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 13px; }
        .info-label { color: #64748b; font-weight: 500; }
        .info-value { color: #0f172a; font-weight: 700; }

        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        th { background: #0f172a; color: white; text-align: left; padding: 12px 15px; font-size: 11px; text-transform: uppercase; }
        td { padding: 10px 15px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        
        .actions { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
        
        @media print { .actions { display: none; } body { padding: 0; } @page { margin: 1cm; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()" class="btn" style="background: var(--aksanti-orange); color: white;"><i class="fas fa-print"></i> Imprimir / PDF</button>
        <a href="export_ad_report.php?format=csv&ad_id=<?php echo $ad_id; ?>" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> CSV</a>
    </div>

    <div class="header">
        <div class="logo-section">
            <div class="logo-box"><img src="../../recursos/images/marca/favicon-k-32x32.png"></div>
            <div class="title-info">
                <h1>Performance de Campanha</h1>
                <p><?php echo htmlspecialchars($ad['title']); ?></p>
            </div>
        </div>
        <div class="meta-info">
            <strong>ID:</strong> #<?php echo $ad['ad_id']; ?><br>
            <strong>Gerado em:</strong> <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <div class="kpi-grid">
        <div class="kpi-card">
            <div class="kpi-label">Visualizações</div>
            <div class="kpi-value"><?php echo number_format($total_views); ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Cliques</div>
            <div class="kpi-value text-orange"><?php echo number_format($total_clicks); ?></div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">CTR Médio</div>
            <div class="kpi-value"><?php echo $ctr; ?>%</div>
        </div>
        <div class="kpi-card">
            <div class="kpi-label">Alcance Real</div>
            <div class="kpi-value"><?php echo number_format($unique_users); ?></div>
        </div>
    </div>

    <div class="details-grid">
        <div class="detail-box">
            <h3>Dados da Campanha</h3>
            <div class="info-row"><span class="info-label">Cliente</span> <span class="info-value"><?php echo htmlspecialchars($ad['client_name']); ?></span></div>
            <div class="info-row"><span class="info-label">Tipo</span> <span class="info-value"><?php echo htmlspecialchars($ad['type']); ?></span></div>
            <div class="info-row"><span class="info-label">Status</span> <span class="info-value"><?php echo $ad['is_active'] ? 'ATIVO' : 'PAUSADO'; ?></span></div>
            <div class="info-row"><span class="info-label">Início</span> <span class="info-value"><?php echo date('d/m/Y', strtotime($ad['start_date'])); ?></span></div>
            <div class="info-row"><span class="info-label">Fim</span> <span class="info-value"><?php echo $ad['end_date'] ? date('d/m/Y', strtotime($ad['end_date'])) : 'Indeterminado'; ?></span></div>
        </div>
        <div class="detail-box">
            <h3>Métricas Financeiras</h3>
            <div class="info-row"><span class="info-label">Orçamento Total</span> <span class="info-value"><?php echo number_format($ad['budget'], 2, ',', '.'); ?> AOA</span></div>
            <div class="info-row"><span class="info-label">CPC (Custo p/ Clique)</span> <span class="info-value"><?php echo number_format($cpc, 2, ',', '.'); ?> AOA</span></div>
            <div class="info-row"><span class="info-label">CPV (Custo p/ View)</span> <span class="info-value"><?php echo number_format($cpv, 2, ',', '.'); ?> AOA</span></div>
            <div class="info-row"><span class="info-label">Pagamento</span> <span class="info-value"><?php echo strtoupper($ad['payment_status']); ?></span></div>
        </div>
    </div>

    <h3 style="font-size: 14px; text-transform: uppercase; color: #64748b; margin-bottom: 15px;">Histórico Recente (30 Dias)</h3>
    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Métrica</th>
                <th>Contagem</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach($daily_metrics as $dm): ?>
            <tr>
                <td><?php echo date('d/m/Y', strtotime($dm['date'])); ?></td>
                <td><?php echo strtoupper($dm['metric_type']); ?></td>
                <td><strong><?php echo $dm['count']; ?></strong></td>
            </tr>
            <?php endforeach; ?>
            <?php if(empty($daily_metrics)): ?>
            <tr><td colspan="3" style="text-align:center; padding: 20px;">Nenhum registo nos últimos 30 dias.</td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</body>
</html>
<?php } ?>

