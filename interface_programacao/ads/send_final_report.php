<?php
// servicos/ads/send_final_report.php
// API para enviar relatório final de campanha
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/SimpleMailer.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$ad_id = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : 0;

if ($ad_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de anúncio inválido']);
    exit;
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

try {
    // 1. Buscar dados do anúncio
    $stmt = $db->prepare("SELECT * FROM ads WHERE ad_id = :ad_id");
    $stmt->execute([':ad_id' => $ad_id]);
    $ad = $stmt->fetch();

    if (!$ad) {
        throw new Exception("Anúncio não encontrado.");
    }

    if (empty($ad['client_email'])) {
        throw new Exception("Cliente sem email cadastrado.");
    }

    // 2. Calcular métricas FINAIS
    $metrics_query = "SELECT COUNT(*) as view_count FROM ad_metrics WHERE ad_id = :ad_id AND metric_type = 'view'";
    $views = $db->prepare($metrics_query);
    $views->execute([':ad_id' => $ad_id]);
    $total_views = $views->fetchColumn();

    $clicks_query = "SELECT COUNT(*) as click_count FROM ad_metrics WHERE ad_id = :ad_id AND metric_type = 'click'";
    $clicks = $db->prepare($clicks_query);
    $clicks->execute([':ad_id' => $ad_id]);
    $total_clicks = $clicks->fetchColumn();

    $unique_query = "SELECT COUNT(DISTINCT COALESCE(user_id::text, md5(COALESCE(ip_address, '') || COALESCE(user_agent, '')))) FROM ad_metrics WHERE ad_id = :ad_id";
    $unique = $db->prepare($unique_query);
    $unique->execute([':ad_id' => $ad_id]);
    $unique_users = $unique->fetchColumn();

    $ctr = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;
    
    // 3. Gerar HTML do Email
    $subject = "Relatório Final de Campanha - {$ad['title']}";
    $body = generateFinalReportEmailBody($ad, $total_views, $total_clicks, $ctr, $unique_users);

    // 4. Enviar Email
    $mailer = new SimpleMailer();
    if ($mailer->send($ad['client_email'], $ad['client_name'], $subject, $body)) {
        // 5. Atualizar status no banco
        $update = $db->prepare("UPDATE ads SET final_report_sent_at = NOW() WHERE ad_id = :ad_id");
        $update->execute([':ad_id' => $ad_id]);

        echo json_encode(['success' => true, 'message' => 'Relatório final enviado com sucesso para ' . $ad['client_email']]);
    } else {
        throw new Exception("Falha ao enviar email.");
    }

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

function generateFinalReportEmailBody($ad, $views, $clicks, $ctr, $unique) {
    $start_date = date('d/m/Y', strtotime($ad['start_date']));
    $end_date = date('d/m/Y', strtotime($ad['end_date']));
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <link rel='icon' type='image/png' sizes='32x32' href='../../recursos/images/marca/favicon-k-32x32.png'>
        <style>
            body { font-family: 'Segoe UI', Arial, sans-serif; background-color: #f4f6f8; color: #333; margin: 0; padding: 0; }
            .container { max-width: 600px; margin: 20px auto; background: #ffffff; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
            .header { background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); color: #ffffff; padding: 40px 20px; text-align: center; }
            .header h1 { margin: 0; font-size: 24px; font-weight: 700; color: #fbbf24; }
            .header p { margin: 10px 0 0; font-size: 16px; opacity: 0.9; }
            .content { padding: 30px; }
            .kpi-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin: 20px 0; }
            .kpi-card { background: #f8fafc; padding: 15px; border-radius: 8px; text-align: center; border: 1px solid #e2e8f0; }
            .kpi-label { font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px; font-weight: 600; }
            .kpi-value { font-size: 24px; font-weight: 800; color: #0f172a; }
            .footer { background: #f1f5f9; padding: 20px; text-align: center; font-size: 12px; color: #64748b; border-top: 1px solid #e2e8f0; }
            .btn { display: inline-block; background: #f7941d; color: white; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-weight: 600; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='container'>
            <div class='header'>
                <h1>Relatório Final de Campanha</h1>
                <p>{$ad['title']}</p>
                <div style='margin-top: 10px; font-size: 14px; opacity: 0.8;'>Período: {$start_date} a {$end_date}</div>
            </div>
            
            <div class='content'>
                <p>Olá, <strong>" . htmlspecialchars($ad['client_name']) . "</strong>,</p>
                <p>Sua campanha chegou ao fim! Abaixo apresentamos o resumo consolidado de desempenho.</p>
                
                <h3 style='color: #1e293b; border-bottom: 2px solid #f7941d; padding-bottom: 10px; margin-top: 30px;'>Resultados Consolidados</h3>
                
                <div class='kpi-grid'>
                    <div class='kpi-card'>
                        <div class='kpi-label'>Total Visualizações</div>
                        <div class='kpi-value' style='color: #3b82f6;'>{$views}</div>
                    </div>
                    <div class='kpi-card'>
                        <div class='kpi-label'>Total Cliques</div>
                        <div class='kpi-value' style='color: #f97316;'>{$clicks}</div>
                    </div>
                    <div class='kpi-card'>
                        <div class='kpi-label'>CTR Médio</div>
                        <div class='kpi-value' style='color: #fbbf24;'>{$ctr}%</div>
                    </div>
                    <div class='kpi-card'>
                        <div class='kpi-label'>Pessoas Alcançadas</div>
                        <div class='kpi-value' style='color: #10b981;'>{$unique}</div>
                    </div>
                </div>
                
                <p>Obrigado por confiar na <strong>KALIYE</strong> para divulgar sua marca/oportunidade.</p>
                
                <div style='text-align: center;'>
                    <a href='https://aksanti.xyz/contact' class='btn'>Iniciar Nova Campanha</a>
                </div>
            </div>
            
            <div class='footer'>
                <p>&copy; 2026 KALIYE Platform. Todos os direitos reservados.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

