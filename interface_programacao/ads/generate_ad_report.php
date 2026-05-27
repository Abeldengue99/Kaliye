<?php
// servicos/ads/generate_ad_report.php
// API para gerar relatório de anúncio em PDF (Visualização Web Elegante)
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Apenas admins podem gerar relatórios
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;

if ($ad_id <= 0) {
    die("ID de anúncio inválido");
}

try {
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
    // Buscar informações do anúncio
    $ad_query = "SELECT * FROM ads WHERE ad_id = :ad_id";
    $ad_stmt = $db->prepare($ad_query);
    $ad_stmt->execute([':ad_id' => $ad_id]);
    $ad = $ad_stmt->fetch();
    
    if (!$ad) {
        die("Anúncio não encontrado");
    }
    
    // Buscar métricas diárias
    $metrics_query = "SELECT 
                        DATE(created_at) as date,
                        metric_type,
                        COUNT(*) as count
                      FROM ad_metrics 
                      WHERE ad_id = :ad_id 
                      GROUP BY DATE(created_at), metric_type
                      ORDER BY date ASC";
    $metrics_stmt = $db->prepare($metrics_query);
    $metrics_stmt->execute([':ad_id' => $ad_id]);
    $metrics = $metrics_stmt->fetchAll();
    
    // Calcular totais reais a partir de ad_metrics
    $totals_query = "SELECT
                        COUNT(*) FILTER (WHERE metric_type = 'view') AS total_views,
                        COUNT(*) FILTER (WHERE metric_type = 'click') AS total_clicks,
                        COUNT(DISTINCT COALESCE(user_id::text, md5(COALESCE(ip_address, '') || COALESCE(user_agent, '')))) AS reach
                     FROM ad_metrics
                     WHERE ad_id = :ad_id";
    $totals_stmt = $db->prepare($totals_query);
    $totals_stmt->execute([':ad_id' => $ad_id]);
    $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total_views = (int)($totals['total_views'] ?? 0);
    $total_clicks = (int)($totals['total_clicks'] ?? 0);
    $unique_users = (int)($totals['reach'] ?? 0);
    $ctr = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;
    
    // Custos
    $cpv = $total_views > 0 ? round($ad['budget'] / $total_views, 2) : 0;
    $cpc = $total_clicks > 0 ? round($ad['budget'] / $total_clicks, 2) : 0;
    
    // Gerar Visualização
    renderReportPage($ad, $total_views, $total_clicks, $ctr, $unique_users, $cpv, $cpc, $metrics);
    
} catch (Exception $e) {
    die("Erro ao gerar relatório: " . $e->getMessage());
}

function renderReportPage($ad, $views, $clicks, $ctr, $unique_users, $cpv, $cpc, $metrics) {
    $client_name = htmlspecialchars($ad['client_name'] ?? 'Cliente não especificado');
    $title = htmlspecialchars($ad['title']);
    $type = htmlspecialchars($ad['type']);
    $budget = number_format($ad['budget'], 2, ',', '.');
    $start_date = $ad['start_date'] ? date('d/m/Y', strtotime($ad['start_date'])) : 'N/A';
    $end_date = $ad['end_date'] ? date('d/m/Y', strtotime($ad['end_date'])) : 'Sem limite';
    $generated_at = date('d/m/Y - H:i');
    
    // Calcular dias restantes
    $status_class = 'status-active';
    $status_text = 'Ativo';
    
    if ($ad['end_date']) {
        $end = new DateTime($ad['end_date']);
        $now = new DateTime();
        if ($now > $end) {
            $status_class = 'status-expired';
            $status_text = 'Finalizado';
        }
    }
    
    // Preparar dados reais para o gráfico JS
    $daily_map = [];
    foreach ($metrics as $m) {
        $d = date('d/m', strtotime($m['date']));
        if (!isset($daily_map[$d])) {
            $daily_map[$d] = ['views' => 0, 'clicks' => 0];
        }
        if ($m['metric_type'] === 'view') {
            $daily_map[$d]['views'] = (int)$m['count'];
        } elseif ($m['metric_type'] === 'click') {
            $daily_map[$d]['clicks'] = (int)$m['count'];
        }
    }
    $chart_labels = json_encode(array_keys($daily_map));
    $chart_views = json_encode(array_column($daily_map, 'views'));
    $html = <<<HTML
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Relatório de Campanha - {$title}</title>
    <link rel="icon" type="image/png" href="../../recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --bg-dark: #0f172a;
            --bg-card: #1e293b;
            --accent-orange: #f7941d;
            --accent-gold: #fbbf24;
            --text-primary: #f8fafc;
            --text-secondary: #94a3b8;
            --success: #10b981;
            --danger: #ef4444;
        }
        
        * { box-sizing: border-box; margin: 0; padding: 0; }
        
        body {
            font-family: 'Segoe UI', system-ui, sans-serif;
            background-color: var(--bg-dark);
            color: var(--text-primary);
            padding: 2rem;
            min-height: 100vh;
        }

        .container {
            max-width: 1000px;
            margin: 0 auto;
            background: var(--bg-card);
            border-radius: 16px;
            box-shadow: 0 10px 40px rgba(0,0,0,0.5);
            overflow: hidden;
            border: 1px solid rgba(255,255,255,0.05);
        }

        /* Header Premium */
        .report-header {
            background: linear-gradient(135deg, rgba(15, 23, 42, 1) 0%, rgba(30, 41, 59, 1) 100%);
            padding: 2.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .logo {
            width: 50px;
            height: 50px;
            background: var(--accent-orange);
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .logo img { width: 30px; }

        .report-meta {
            text-align: right;
        }
        
        .status-badge {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            display: inline-block;
            margin-bottom: 0.5rem;
        }

        .status-active { background: rgba(16, 185, 129, 0.2); color: var(--success); }
        .status-expired { background: rgba(239, 68, 68, 0.2); color: var(--danger); }

        /* Grid Layout */
        .grid-content {
            padding: 2.5rem;
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 2rem;
        }

        .full-width { grid-column: span 2; }

        .section-title {
            font-size: 1.1rem;
            color: var(--text-secondary);
            margin-bottom: 1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-left: 3px solid var(--accent-orange);
            padding-left: 10px;
        }

        /* Cards */
        .stat-card {
            background: rgba(255,255,255,0.03);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255,255,255,0.05);
            transition: transform 0.2s;
        }
        
        .stat-card:hover { border-color: var(--accent-orange); transform: translateY(-2px); }

        .stat-label { font-size: 0.85rem; color: var(--text-secondary); margin-bottom: 0.5rem; text-transform: uppercase; }
        .stat-value { font-size: 2rem; font-weight: 700; color: white; }
        .stat-sub { font-size: 0.85rem; margin-top: 0.5rem; opacity: 0.7; }

        .text-orange { color: var(--accent-orange); }
        .text-gold { color: var(--accent-gold); }
        .text-blue { color: #3b82f6; }
        .text-green { color: var(--success); }

        /* Table Styled */
        .metrics-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1rem;
        }

        .metrics-table th {
            text-align: left;
            padding: 1rem;
            color: var(--text-secondary);
            font-weight: 600;
            border-bottom: 1px solid rgba(255,255,255,0.1);
        }

        .metrics-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }

        /* Buttons & Actions */
        .actions-bar {
            padding: 2rem 2.5rem;
            background: rgba(0,0,0,0.2);
            border-top: 1px solid rgba(255,255,255,0.05);
            display: flex;
            justify-content: flex-end;
            gap: 1rem;
        }

        .btn {
            border: none;
            padding: 12px 24px;
            border-radius: 8px;
            font-weight: 600;
            cursor: pointer;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            transition: all 0.2s;
            font-size: 0.95rem;
        }

        .btn-primary { background: var(--accent-orange); color: white; }
        .btn-primary:hover { background: #e08210; box-shadow: 0 4px 15px rgba(247, 148, 29, 0.4); }

        .btn-secondary { background: rgba(255,255,255,0.1); color: white; }
        .btn-secondary:hover { background: rgba(255,255,255,0.2); }

        /* Print Styles */
        @media print {
            body { background: white; color: black; padding: 0; }
            .container { box-shadow: none; border: none; width: 100%; max-width: 100%; margin: 0; border-radius: 0; }
            .report-header, .stat-card, .grid-content, .metrics-table th, .metrics-table td { 
                background: white !important; 
                color: black !important; 
                border-color: #ddd !important; 
            }
            .actions-bar { display: none; }
            .stat-value { color: black !important; }
            .logo { background: #f7941d !important; -webkit-print-color-adjust: exact; }
            .text-orange, .text-gold, .text-blue, .text-green { color: black !important; }
            .section-title { color: #333 !important; border-color: #f7941d !important; }
        }
    </style>
</head>
<body>

<div class="container">
    <div class="report-header">
        <div class="brand">
            <div class="logo">
                <img src="../../recursos/images/marca/favicon-k-32x32.png" alt="A">
            </div>
            <div>
                <h1 style="font-size: 1.5rem; margin: 0;">Relatório de Performance</h1>
                <p style="color: var(--text-secondary); font-size: 0.9rem;">KALIYE Platform</p>
            </div>
        </div>
        <div class="report-meta">
            <div class="status-badge {$status_class}">{$status_text}</div>
            <div style="font-size: 0.9rem; color: var(--text-secondary);">Gerado em: {$generated_at}</div>
        </div>
    </div>

    <div class="grid-content">
        <!-- Info Principal -->
        <div class="full-width">
            <h2 style="font-size: 2rem; margin-bottom: 0.5rem;">{$title}</h2>
            <p style="color: var(--text-secondary); font-size: 1.1rem;">Cliente: <span style="color: white;">{$client_name}</span></p>
            <div style="margin-top: 1rem; display: flex; gap: 2rem; color: var(--text-secondary); font-size: 0.9rem;">
                <span><i class="fas fa-calendar"></i> {$start_date} - {$end_date}</span>
                <span><i class="fas fa-tag"></i> {$type}</span>
                <span><i class="fas fa-coins"></i> {$budget} AOA</span>
            </div>
        </div>

        <!-- KPIs -->
        <div class="stat-card">
            <div class="stat-label">Total Visualizações</div>
            <div class="stat-value text-blue">{$views}</div>
            <div class="stat-sub">CPV Estimado: {$cpv} AOA</div>
        </div>
        
        <div class="stat-card">
            <div class="stat-label">Total Cliques</div>
            <div class="stat-value text-orange">{$clicks}</div>
            <div class="stat-sub">CPC Estimado: {$cpc} AOA</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Taxa de Clique (CTR)</div>
            <div class="stat-value text-gold">{$ctr}%</div>
            <div class="stat-sub">Engajamento Médio</div>
        </div>

        <div class="stat-card">
            <div class="stat-label">Alcance Real</div>
            <div class="stat-value text-green">{$unique_users}</div>
            <div class="stat-sub">Usuários distintos impactados</div>
        </div>

        <!-- Gráfico -->
        <div class="full-width stat-card">
            <div class="stat-label">Evolução Diária</div>
            <div style="height: 300px; margin-top: 1rem;">
                <canvas id="evolutionChart"></canvas>
            </div>
        </div>
    </div>

    <div class="actions-bar">
        <button onclick="window.print()" class="btn btn-secondary">
            <i class="fas fa-save"></i> Guardar PDF
        </button>
        <button onclick="sendReport()" class="btn btn-primary">
            <i class="fas fa-paper-plane"></i> Enviar Email ao Cliente
        </button>
    </div>
</div>

<script>
    // Inicializar Gráfico
    // Extraindo dados do PHP para JS de forma segura (simplificada para este contexto)
    const chartData = {
        labels: [], // Preencher via PHP se possível ou adaptar
        datasets: []
    };

    // Dados injetados
    // Nota: Em um cenário real, usaria json_encode, mas aqui vou simular com os dados disponíveis
    
    // Configuração do ChartJS
    const ctx = document.getElementById('evolutionChart').getContext('2d');
    new Chart(ctx, {
        type: 'line',
        data: {
            labels: {$chart_labels},
            datasets: [{
                label: 'Visualizações',
                data: {$chart_views},
                borderColor: '#f7941d',
                backgroundColor: 'rgba(247, 148, 29, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: { legend: { display: false } },
            scales: {
                y: { grid: { color: 'rgba(255,255,255,0.05)' }, ticks: { color: '#94a3b8' } },
                x: { grid: { display: false }, ticks: { color: '#94a3b8' } }
            }
        }
    });

    // Função para enviar relatório
    function sendReport() {
        Swal.fire({
            title: 'Enviar Relatório?',
            text: "O cliente receberá um email com este resumo consolidado.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#f7941d',
            cancelButtonColor: '#333',
            confirmButtonText: 'Sim, enviar!',
            background: '#1e293b',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                // Chama API
                const formData = new FormData();
                formData.append('ad_id', {$ad['ad_id']});
                
                fetch('send_final_report.php', {
                    method: 'POST',
                    body: formData
                })
                .then(response => response.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({
                            title: 'Enviado!',
                            text: data.message,
                            icon: 'success',
                            background: '#1e293b',
                            color: '#fff'
                        });
                    } else {
                        Swal.fire({
                            title: 'Erro!',
                            text: data.message,
                            icon: 'error',
                            background: '#1e293b',
                            color: '#fff'
                        });
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    Swal.fire('Erro!', 'Falha na conexão.', 'error');
                });
            }
        });
    }
</script>

</body>
</html>
HTML;
    
    echo $html;
}

