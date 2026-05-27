<?php
// admin/telemetry.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch latest logins
$query = "
    SELECT l.*, u.full_name, u.email, u.profile_pic 
    FROM login_logs l
    JOIN users u ON l.user_id = u.user_id
    ORDER BY l.login_at DESC
    LIMIT 50
";
$logins = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

// Fetch stats for devices
$device_stats = $db->query("SELECT device_type, COUNT(*) as count FROM login_logs GROUP BY device_type")->fetchAll(PDO::FETCH_ASSOC);
$brand_stats = $db->query("SELECT device_brand, COUNT(*) as count FROM login_logs GROUP BY device_brand")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Telemetria de Acesso - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <style>
        .telemetry-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 1.5rem; margin-bottom: 2rem; }
        @media (max-width: 992px) { .telemetry-grid { grid-template-columns: 1fr; } }
        .device-tag { font-size: 0.65rem; font-weight: 900; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; }
        .tag-mobile { background: rgba(59, 130, 246, 0.1); color: #60a5fa; }
        .tag-tablet { background: rgba(167, 139, 250, 0.1); color: #a78bfa; }
        .tag-desktop { background: rgba(16, 185, 129, 0.1); color: #34d399; }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Telemetria de Acesso</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Inteligência de tráfego, geolocalização e análise de dispositivos.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="export_telemetry.php?format=view" target="_blank" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #fff; padding: 0.75rem 1.5rem; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="export_telemetry.php?format=csv" class="btn-admin btn-admin-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
            </div>
        </header>

        <div class="telemetry-grid">
            <div class="admin-card-premium" style="padding: 1.5rem;">
                <h4 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-microchip" style="color: #f7941d;"></i> Mix de Dispositivos
                </h4>
                <div style="height: 280px;">
                    <canvas id="deviceChart"></canvas>
                </div>
            </div>
            <div class="admin-card-premium" style="padding: 1.5rem;">
                <h4 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; margin-bottom: 2rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-tag" style="color: #f7941d;"></i> Marcas Predominantes
                </h4>
                <div style="height: 280px;">
                    <canvas id="brandChart"></canvas>
                </div>
            </div>
        </div>

        <div class="admin-card-premium" style="padding: 0;">
            <div style="padding: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05); display: flex; justify-content: space-between; align-items: center;">
                <h4 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; margin: 0;">Tráfego em Tempo Real (Últimos 50 acessos)</h4>
            </div>
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>IP / Conectividade</th>
                            <th>Geolocalização</th>
                            <th>Plataforma</th>
                            <th>Cronologia</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logins as $log): 
                            $typeClass = 'tag-desktop';
                            if($log['device_type'] == 'Mobile') $typeClass = 'tag-mobile';
                            if($log['device_type'] == 'Tablet') $typeClass = 'tag-tablet';
                        ?>
                        <tr>
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                <?php 
                                    $pic_raw = $log['profile_pic'];
                                    $final_pic = $base_url . 'recursos/images/default_profile.png';
                                    if ($pic_raw && $pic_raw != 'default_profile.png') {
                                        if (strpos($pic_raw, 'http') === 0) {
                                            $final_pic = $pic_raw;
                                        } elseif (strpos($pic_raw, 'carregamentos/') === 0) {
                                            $final_pic = $base_url . $pic_raw;
                                        } else {
                                            $final_pic = $base_url . 'carregamentos/profiles/' . $pic_raw;
                                        }
                                    }
                                ?>
                                <img src="<?= $final_pic ?>" style="width: 32px; height: 32px; border-radius: 8px; border: 1px solid rgba(255,255,255,0.1); object-fit: cover;">
                                    <div>
                                        <div style="font-weight: 800; color: #fff; font-size: 0.85rem;"><?= htmlspecialchars($log['full_name']) ?></div>
                                        <div style="font-size: 0.65rem; color: rgba(255,255,255,0.3);"><?= htmlspecialchars($log['email']) ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <code style="color: #f7941d; font-size: 0.75rem; font-weight: 800; background: rgba(247, 148, 29, 0.05); padding: 2px 6px; border-radius: 4px;"><?= $log['ip_address'] ?></code>
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.2); margin-top: 4px;"><?= htmlspecialchars($log['isp'] ?? 'ISP Unknown') ?></div>
                            </td>
                            <td>
                                <?php if(!empty($log['country'])): ?>
                                    <div style="font-size: 0.8rem; font-weight: 700; color: #fff;"><?= htmlspecialchars($log['city'] . ', ' . $log['country']) ?></div>
                                    <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4);"><?= htmlspecialchars($log['region']) ?></div>
                                <?php else: ?>
                                    <span style="color: rgba(255,255,255,0.2); font-size: 0.7rem;">Dados indisponíveis</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="device-tag <?= $typeClass ?>"><?= $log['device_type'] ?></span>
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.4); margin-top: 4px; font-weight: 600;"><?= $log['device_brand'] ?></div>
                            </td>
                            <td>
                                <div style="font-weight: 700; font-size: 0.8rem; color: #fff;"><?= date('d M, Y', strtotime($log['login_at'])) ?></div>
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.3);"><?= date('H:i', strtotime($log['login_at'])) ?>h</div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
        const chartConfig = {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: { position: 'bottom', labels: { color: 'rgba(255,255,255,0.5)', font: { family: 'Outfit', size: 11, weight: '700' }, usePointStyle: true, padding: 20 } }
            }
        };

        // Device Chart
        new Chart(document.getElementById('deviceChart').getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: <?= json_encode(array_column($device_stats, 'device_type')) ?>,
                datasets: [{
                    data: <?= json_encode(array_column($device_stats, 'count')) ?>,
                    backgroundColor: ['#f7941d', '#60a5fa', '#34d399', '#f87171'],
                    hoverOffset: 15,
                    borderWidth: 0
                }]
            },
            options: {
                ...chartConfig,
                cutout: '70%'
            }
        });

        // Brand Chart
        new Chart(document.getElementById('brandChart').getContext('2d'), {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_column($brand_stats, 'device_brand')) ?>,
                datasets: [{
                    label: 'Sessões',
                    data: <?= json_encode(array_column($brand_stats, 'count')) ?>,
                    backgroundColor: 'rgba(247, 148, 29, 0.2)',
                    borderColor: '#f7941d',
                    borderWidth: 2,
                    borderRadius: 8
                }]
            },
            options: {
                ...chartConfig,
                plugins: { ...chartConfig.plugins, legend: { display: false } },
                scales: {
                    y: { grid: { color: 'rgba(255,255,255,0.05)', drawBorder: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { weight: '800' } } },
                    x: { grid: { display: false }, ticks: { color: 'rgba(255,255,255,0.3)', font: { weight: '800' } } }
                }
            }
        });
    </script>
</body>
</html>

