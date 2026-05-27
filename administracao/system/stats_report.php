<?php
/**
 * admin/system/stats_report.php - Advanced Intelligence & Activity Dashboard (v3.0)
 */
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
$db = $database->getConnection();

// --- DATA FETCHING ---

// 1. General Growth (Signups by Month - Last 6 Months)
// Using PostgreSQL DATE_TRUNC or to_char
$growth_stats = $db->query("
    SELECT to_char(created_at, 'Month') as month, COUNT(*) as count 
    FROM users 
    WHERE created_at > CURRENT_DATE - INTERVAL '6 months' 
    GROUP BY month, date_trunc('month', created_at)
    ORDER BY date_trunc('month', created_at) ASC
")->fetchAll();

// 2. Gender Distribution
$gender_stats = $db->query("SELECT COALESCE(gender, 'N/S') as label, COUNT(*) as value FROM users GROUP BY gender")->fetchAll();

// 3. Localization
$location_stats = $db->query("SELECT COALESCE(location, 'Global') as label, COUNT(*) as value FROM users GROUP BY location ORDER BY value DESC LIMIT 10")->fetchAll();

// 4. Mentorship Pipeline
$mentorship_data = [
    'total_users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'approved_mentors' => $db->query("SELECT COUNT(*) FROM users WHERE user_type = 'mentor' OR mentorship_status = 'approved'")->fetchColumn(),
    'pending_mentors' => $db->query("SELECT COUNT(*) FROM users WHERE mentor_status = 'pending' OR mentorship_status = 'pending'")->fetchColumn()
];

// 5. Investment Health (Paid vs Pending)
$investment_stats = $db->query("SELECT status, COUNT(*) as count FROM project_investments GROUP BY status")->fetchAll();

// 6. Top Institutions
$institution_stats = $db->query("SELECT COALESCE(institution, 'N/A') as label, COUNT(*) as value FROM users WHERE institution IS NOT NULL AND institution != '' GROUP BY institution ORDER BY value DESC LIMIT 5")->fetchAll();

// 7. Top Publishers (Most Projects)
$top_publishers = $db->query("
    SELECT u.full_name, u.profile_pic, COUNT(p.project_id) as project_count 
    FROM users u 
    INNER JOIN projects p ON u.user_id = p.owner_id 
    GROUP BY u.user_id, u.full_name, u.profile_pic 
    ORDER BY project_count DESC LIMIT 5
")->fetchAll();

// 8. Top Engagers
$top_engagers = $db->query("
    SELECT u.full_name, u.profile_pic, 
    (SELECT COUNT(*) FROM project_likes l WHERE l.user_id = u.user_id) + 
    (SELECT COUNT(*) FROM project_comments c WHERE c.user_id = u.user_id) as score
    FROM users u 
    WHERE ((SELECT COUNT(*) FROM project_likes l WHERE l.user_id = u.user_id) + (SELECT COUNT(*) FROM project_comments c WHERE c.user_id = u.user_id)) > 0
    ORDER BY score DESC LIMIT 5
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Inteligência de Dados Elite - KALIYE</title>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    
    <style>
        :root {
            --chart-orange: #f7941d;
            --chart-blue: #3b82f6;
            --chart-green: #10b981;
            --chart-red: #ef4444;
        }
        
        .stats-report-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }
        
        .intelligence-card {
            background: rgba(13, 22, 40, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 28px;
            padding: 1.8rem;
            backdrop-filter: blur(12px);
            display: flex;
            flex-direction: column;
            gap: 1.2rem;
        }
        
        .intelligence-card.wide { grid-column: span 2; }
        .intelligence-card.full { grid-column: span 3; }
        
        .card-header-stats {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
        }
        
        .card-header-stats h3 {
            font-size: 1rem; color: #fff; margin: 0;
            display: flex; align-items: center; gap: 10px;
            font-family: 'Outfit', sans-serif;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .card-header-stats i { color: var(--chart-orange); }
        
        .stat-badge-mini {
            font-size: 0.65rem; background: rgba(16, 185, 129, 0.1); 
            color: #10b981; padding: 4px 10px; border-radius: 6px; font-weight: 800;
        }

        .chart-box { height: 260px; width: 100%; position: relative; }

        /* Rankings Layout */
        .ranking-list { display: flex; flex-direction: column; gap: 0.8rem; }
        .ranking-row {
            display: flex; align-items: center; gap: 12px;
            background: rgba(255,255,255,0.02); padding: 10px 14px;
            border-radius: 16px; border: 1px solid rgba(255,255,255,0.04);
            transition: 0.3s;
        }
        .ranking-row:hover { background: rgba(255,255,255,0.05); border-color: rgba(247,148,29,0.2); }
        
        .user-pic { width: 34px; height: 34px; border-radius: 10px; object-fit: cover; }
        .user-info-name { flex: 1; font-weight: 700; color: #fff; font-size: 0.85rem; }
        .user-info-score { font-size: 0.75rem; color: var(--chart-orange); font-weight: 300; }
        .score-val { font-weight: 900; }

        /* Metric Grid */
        .metric-mini-grid { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; }
        .metric-mini-box {
            padding: 1rem; background: rgba(255,255,255,0.02);
            border-radius: 20px; text-align: center; border: 1px solid rgba(255,255,255,0.04);
        }
        .metric-mini-box h5 { color: rgba(255,255,255,0.4); font-size: 0.6rem; text-transform: uppercase; margin-bottom: 5px; }
        .metric-mini-box .val { font-size: 1.4rem; color: #fff; font-weight: 900; }

        @media (max-width: 1200px) { .stats-report-grid { grid-template-columns: 1fr; } .intelligence-card { grid-column: span 1 !important; } }
    </style>
</head>
<body class="admin-dashboard-layout">

    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1 style="font-family: 'Outfit', sans-serif;">Inteligência de Rede Eksanti</h1>
                <p style="color: rgba(255,255,255,0.4);">Análise estratégica v3.1 — Sincronizado em Tempo Real.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button onclick="window.location.reload()" class="btn-admin" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa;"><i class="fas fa-sync"></i></button>
                <button onclick="window.print()" class="btn-admin btn-admin-primary"><i class="fas fa-print"></i> IMPRIMIR PDF</button>
            </div>
        </header>

        <div class="stats-report-grid">
            
            <!-- CRESCIMENTO (Growth) -->
            <div class="intelligence-card wide">
                <div class="card-header-stats">
                    <h3><i class="fas fa-chart-line"></i> Curva de Crescimento (Signups)</h3>
                    <span class="stat-badge-mini">+<?= end($growth_stats)['value'] ?? 0 ?> este mês</span>
                </div>
                <div class="chart-box">
                    <canvas id="growthChart"></canvas>
                </div>
            </div>

            <!-- GÉNERO (Demographics) -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-venus-mars"></i> Mix Social (Género)</h3>
                </div>
                <div class="chart-box">
                    <canvas id="genderChart"></canvas>
                </div>
            </div>

            <!-- FUNIL DE MENTORIA (Efficiency) -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-graduation-cap"></i> Eficiência de Mentoria</h3>
                </div>
                <div class="metric-mini-grid" style="margin-top: 1rem;">
                    <div class="metric-mini-box">
                        <h5>Aprovados</h5>
                        <div class="val"><?= $mentorship_data['approved_mentors'] ?></div>
                    </div>
                    <div class="metric-mini-box">
                        <h5>Candidatos</h5>
                        <div class="val" style="color: var(--chart-orange);"><?= $mentorship_data['pending_mentors'] ?></div>
                    </div>
                </div>
                <div style="margin-top: 1rem; color: rgba(255,255,255,0.4); font-size: 0.75rem; line-height: 1.4;">
                    <i class="fas fa-info-circle"></i> Taxa de conversão de mentores: 
                    <strong><?= ($mentorship_data['total_users'] > 0) ? round(($mentorship_data['approved_mentors'] / $mentorship_data['total_users']) * 100, 1) : 0 ?>%</strong>
                </div>
            </div>

            <!-- FINANCEIRO (Funnel) -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-wallet"></i> Pipeline Financeiro (Status)</h3>
                </div>
                <div class="chart-box" style="height: 200px;">
                    <canvas id="investmentChart"></canvas>
                </div>
            </div>

            <!-- LOCALIZAÇÃO (Heatmap) -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-map-marker-alt"></i> Concentração Geográfica</h3>
                </div>
                <div class="chart-box" style="height: 200px;">
                    <canvas id="locationChart"></canvas>
                </div>
            </div>

            <!-- TOP PUBLISHERS -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-rocket"></i> Maiores Publicadores</h3>
                </div>
                <div class="ranking-list">
                    <?php foreach($top_publishers as $u): 
                        $pic = ($u['profile_pic']) ? $base_url . $u['profile_pic'] : $base_url . 'recursos/images/default_profile.png';
                    ?>
                    <div class="ranking-row">
                        <img src="<?= $pic ?>" class="user-pic" onerror="this.src='../../recursos/images/default_profile.png'">
                        <div class="user-info-name"><?= $u['full_name'] ?></div>
                        <div class="user-info-score"><span class="score-val"><?= $u['project_count'] ?></span> PROJECTOS</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- TOP ENGAGERS -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-fire"></i> Campeões de Engajamento</h3>
                </div>
                <div class="ranking-list">
                    <?php foreach($top_engagers as $u): 
                        $pic = ($u['profile_pic']) ? $base_url . $u['profile_pic'] : $base_url . 'recursos/images/default_profile.png';
                    ?>
                    <div class="ranking-row">
                        <img src="<?= $pic ?>" class="user-pic" onerror="this.src='../../recursos/images/default_profile.png'">
                        <div class="user-info-name"><?= $u['full_name'] ?></div>
                        <div class="user-info-score"><span class="score-val"><?= $u['score'] ?></span> ACÇÕES</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- INSTITUIÇÕES -->
            <div class="intelligence-card">
                <div class="card-header-stats">
                    <h3><i class="fas fa-university"></i> Instituições Dominantes</h3>
                </div>
                <div class="ranking-list">
                    <?php foreach($institution_stats as $stat): ?>
                    <div class="ranking-row">
                        <div class="user-info-name" style="font-size: 0.8rem;"><?= $stat['label'] ?></div>
                        <div class="user-info-score"><span class="score-val"><?= $stat['value'] ?></span> MEMBROS</div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

        </div>
    </main>

    <script>
        // Charts Initialization
        document.addEventListener('DOMContentLoaded', () => {
            const ctxGrowth = document.getElementById('growthChart').getContext('2d');
            new Chart(ctxGrowth, {
                type: 'line',
                data: {
                    labels: <?= json_encode(array_column($growth_stats, 'month')) ?>,
                    datasets: [{
                        label: 'Novos Utilizadores',
                        data: <?= json_encode(array_column($growth_stats, 'count')) ?>,
                        borderColor: '#f7941d', background: 'rgba(247, 148, 29, 0.1)',
                        fill: true, tension: 0.4, borderWidth: 3,
                        pointBackgroundColor: '#fff', pointRadius: 5
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { display: false } },
                    scales: { y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,0.05)' } }, x: { grid: { display: false } } }
                }
            });

            const ctxGender = document.getElementById('genderChart').getContext('2d');
            new Chart(ctxGender, {
                type: 'doughnut',
                data: {
                    labels: <?= json_encode(array_map('ucfirst', array_column($gender_stats, 'label'))) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($gender_stats, 'value')) ?>,
                        backgroundColor: ['#f7941d', '#3b82f6', '#10b981', '#ef4444', '#7c3aed'],
                        borderWidth: 0, hoverOffset: 10
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'bottom', labels: { color: 'white', padding: 20 } } } }
            });

            const ctxInvest = document.getElementById('investmentChart').getContext('2d');
            new Chart(ctxInvest, {
                type: 'pie',
                data: {
                    labels: <?= json_encode(array_map('ucfirst', array_column($investment_stats, 'status'))) ?>,
                    datasets: [{
                        data: <?= json_encode(array_column($investment_stats, 'count')) ?>,
                        backgroundColor: ['#10b981', '#fbbf24', '#f87171', '#94a3b8'],
                        borderWidth: 0
                    }]
                },
                options: { responsive: true, maintainAspectRatio: false, plugins: { legend: { position: 'right', labels: { color: 'rgba(255,255,255,0.6)', font: { size: 10 } } } } }
            });

            const ctxLocation = document.getElementById('locationChart').getContext('2d');
            new Chart(ctxLocation, {
                type: 'bar',
                data: {
                    labels: <?= json_encode(array_column($location_stats, 'label')) ?>,
                    datasets: [{
                        label: 'Utilizadores',
                        data: <?= json_encode(array_column($location_stats, 'value')) ?>,
                        backgroundColor: 'rgba(59, 130, 246, 0.4)', borderColor: '#3b82f6', borderWidth: 1
                    }]
                },
                options: { indexAxis: 'y', responsive: true, maintainAspectRatio: false, 
                    plugins: { legend: { display: false } },
                    scales: { x: { grid: { color: 'rgba(255,255,255,0.05)' } }, y: { grid: { display: false } } }
                }
            });
        });
    </script>
</body>
</html>

