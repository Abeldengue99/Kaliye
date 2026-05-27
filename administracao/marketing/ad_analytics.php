<?php
// admin/marketing/ad_analytics.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

if (!hasPermission('ads')) {
    header("Location: ../index.php"); 
    exit();
}

$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;

if ($ad_id <= 0) {
    header("Location: manage_ads.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Buscar informações do anúncio
$ad_query = "SELECT * FROM ads WHERE ad_id = :ad_id";
$ad_stmt = $db->prepare($ad_query);
$ad_stmt->execute([':ad_id' => $ad_id]);
$ad = $ad_stmt->fetch();

if (!$ad) {
    header("Location: manage_ads.php");
    exit();
}

// Calcular estado de expiração
$today = date('Y-m-d');
$is_expired = $ad['end_date'] && $today > $ad['end_date'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Analytics: <?php echo htmlspecialchars($ad['title']); ?> | KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../../recursos/images/marca/apple-touch-icon-k.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#f7941d">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body class="admin-dashboard-layout">
    <!-- Top Navigation Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <a href="manage_ads.php" style="color: var(--aksanti-orange); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-arrow-left"></i> Voltar aos Anúncios
                </a>
                <h1>Analytics da Campanha</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;"><?php echo htmlspecialchars($ad['title']); ?></p>
            </div>
            <button onclick="generateReport()" class="btn-admin btn-admin-primary">
                <i class="fas fa-file-pdf"></i> GERAR RELATÓRIO PDF
            </button>
        </header>

        <div id="kpiCards" class="stats-grid">
            <div class="admin-card-premium" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 800; letter-spacing: 1px;">
                    <i class="fas fa-eye" style="color: #60a5fa;"></i> Total de Visualizações
                </div>
                <div id="totalViews" class="stat-value" style="font-size: 2rem; color: #60a5fa;">-</div>
            </div>
            <div class="admin-card-premium" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 800; letter-spacing: 1px;">
                    <i class="fas fa-mouse-pointer" style="color: var(--aksanti-orange);"></i> Total de Cliques
                </div>
                <div id="totalClicks" class="stat-value" style="font-size: 2rem; color: var(--aksanti-orange);">-</div>
            </div>
            <div class="admin-card-premium" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 800; letter-spacing: 1px;">
                    <i class="fas fa-chart-line" style="color: #fbbf24;"></i> CTR (Taxa de Clique)
                </div>
                <div id="ctr" class="stat-value" style="font-size: 2rem; color: #fbbf24;">-</div>
            </div>
            <div class="admin-card-premium" style="text-align: center; padding: 1.5rem;">
                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); text-transform: uppercase; margin-bottom: 0.5rem; font-weight: 800; letter-spacing: 1px;">
                    <i class="fas fa-users" style="color: #34d399;"></i> Alcance Real
                </div>
                <div id="uniqueUsers" class="stat-value" style="font-size: 2rem; color: #34d399;">-</div>
            </div>
        </div>

        <div class="admin-card-premium" style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem; color: #fff;"><i class="fas fa-info-circle" style="color: var(--aksanti-orange);"></i> Informações da Campanha</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Cliente</strong>
                    <div style="font-size: 1rem; font-weight: 600; margin-top: 0.25rem;"><?php echo htmlspecialchars($ad['client_name'] ?: 'Não especificado'); ?></div>
                </div>
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Vigência</strong>
                    <div style="font-size: 1rem; font-weight: 600; margin-top: 0.25rem;">
                        <?php 
                        if ($ad['start_date'] && $ad['end_date']) {
                            echo date('d/m/Y', strtotime($ad['start_date'])) . ' - ' . date('d/m/Y', strtotime($ad['end_date']));
                        } else {
                            echo 'Sem período definido';
                        }
                        ?>
                    </div>
                </div>
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Investimento</strong>
                    <div style="font-size: 1rem; font-weight: 600; margin-top: 0.25rem; color: #fbbf24;">
                        <?php echo $ad['budget'] > 0 ? number_format($ad['budget'], 2, ',', '.') . ' AOA' : 'Não definido'; ?>
                    </div>
                </div>
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Status da Veiculação</strong>
                    <div style="margin-top: 0.25rem;">
                        <?php if ($is_expired): ?>
                            <span class="user-badge-premium" style="color: #f43f5e; border-color: rgba(244, 63, 94, 0.2);">EXPIRADO</span>
                        <?php elseif ($ad['is_active']): ?>
                            <span class="user-badge-premium" style="color: #34d399; border-color: rgba(52, 211, 153, 0.2);">EM EXIBIÇÃO</span>
                        <?php else: ?>
                            <span class="user-badge-premium" style="color: #94a3b8; border-color: rgba(148, 163, 184, 0.2);">PAUSADO</span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Gráficos -->
        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(350px, 1fr)); gap: 1.5rem; margin-bottom: 2rem;">
            <div class="admin-card-premium">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem;"><i class="fas fa-chart-area" style="color: #60a5fa;"></i> Performance Diária</h3>
                <div style="height: 300px;">
                    <canvas id="dailyChart"></canvas>
                </div>
            </div>
            <div class="admin-card-premium">
                <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem;"><i class="fas fa-clock" style="color: #10b981;"></i> Engagement por Horário</h3>
                <div style="height: 300px;">
                    <canvas id="hourlyChart"></canvas>
                </div>
            </div>
        </div>

        <!-- ROI e Custos -->
        <div id="roiSection" class="admin-card-premium" style="margin-bottom: 2rem;">
            <h3 style="margin: 0 0 1.5rem 0; font-size: 1.1rem;"><i class="fas fa-coins" style="color: #fbbf24;"></i> Indicadores Financeiros (Efetivos)</h3>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 2rem;">
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">CPV (Custo por View)</strong>
                    <div id="cpv" style="font-size: 1.3rem; font-weight: 800; color: #60a5fa; margin-top: 0.25rem;">-</div>
                </div>
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">CPC (Custo por Clique)</strong>
                    <div id="cpc" style="font-size: 1.3rem; font-weight: 800; color: var(--aksanti-orange); margin-top: 0.25rem;">-</div>
                </div>
                <div>
                    <strong style="color: rgba(255,255,255,0.4); font-size: 0.7rem; text-transform: uppercase; letter-spacing: 1px;">Tempo de Veiculação Restante</strong>
                    <div id="daysRemaining" style="font-size: 1.3rem; font-weight: 800; color: #fbbf24; margin-top: 0.25rem;">-</div>
                </div>
            </div>
        </div>
    </main>

    <script>
        const adId = <?php echo $ad_id; ?>;
        let dailyChart, hourlyChart;

        async function loadAnalytics() {
            try {
                const response = await fetch(`../../interface_programacao/ads/get_ad_analytics.php?ad_id=${adId}`);
                const data = await response.json();
                
                if (data.success) {
                    document.getElementById('totalViews').innerText = data.metrics.total_views.toLocaleString();
                    document.getElementById('totalClicks').innerText = data.metrics.total_clicks.toLocaleString();
                    document.getElementById('ctr').innerText = data.metrics.ctr + '%';
                    document.getElementById('uniqueUsers').innerText = data.metrics.unique_users.toLocaleString();
                    document.getElementById('cpv').innerText = data.metrics.cpv.toFixed(2) + ' AOA';
                    document.getElementById('cpc').innerText = data.metrics.cpc.toFixed(2) + ' AOA';
                    document.getElementById('daysRemaining').innerText = data.metrics.days_remaining !== null ? 
                        (data.metrics.days_remaining >= 0 ? data.metrics.days_remaining + ' dias' : 'Expirado') : 
                        'Sem limite';
                    renderDailyChart(data.daily_data);
                    renderHourlyChart(data.peak_hours);
                } else {
                    alert('Erro ao carregar analytics: ' + data.message);
                }
            } catch (error) {
                console.error('Erro:', error);
                alert('Erro ao carregar dados');
            }
        }

        function renderDailyChart(dailyData) {
            const ctx = document.getElementById('dailyChart').getContext('2d');
            if (dailyChart) dailyChart.destroy();
            dailyChart = new Chart(ctx, {
                type: 'line',
                data: {
                    labels: dailyData.map(d => new Date(d.date).toLocaleDateString('pt-PT')),
                    datasets: [
                        { label: 'Visualizações', data: dailyData.map(d => d.views), borderColor: '#3b82f6', backgroundColor: 'rgba(59, 130, 246, 0.1)', tension: 0.4 },
                        { label: 'Cliques', data: dailyData.map(d => d.clicks), borderColor: '#f7941d', backgroundColor: 'rgba(247, 148, 29, 0.1)', tension: 0.4 }
                    ]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { labels: { color: '#fff' } } }, scales: { y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } }, x: { ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } } } }
            });
        }

        function renderHourlyChart(peakHours) {
            const ctx = document.getElementById('hourlyChart').getContext('2d');
            if (hourlyChart) hourlyChart.destroy();
            hourlyChart = new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: peakHours.map(h => h.hour + 'h'),
                    datasets: [{ label: 'Interações', data: peakHours.map(h => h.interactions), backgroundColor: '#10b981', borderRadius: 8 }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } }, scales: { y: { beginAtZero: true, ticks: { color: '#94a3b8' }, grid: { color: 'rgba(148, 163, 184, 0.1)' } }, x: { ticks: { color: '#94a3b8' }, grid: { display: false } } } }
            });
        }

        function generateReport() {
            window.open(`export_ad_report.php?ad_id=${adId}`, '_blank');
        }

        loadAnalytics();
        setInterval(loadAnalytics, 30000);
    </script>
</body>
</html>

