<?php
// processos/send_end_of_campaign_reports.php
// Script para enviar relatórios finais de campanha automaticamente
// Deve ser executado diariamente via Cron Job (ex: 08:00 AM)

date_default_timezone_set('Africa/Luanda');

require_once dirname(__DIR__) . '/configuracoes/base_dados.php';
require_once dirname(__DIR__) . '/inclusoes/SimpleMailer.php';

// Verificação de segurança CLI
if (php_sapi_name() !== 'cli') {
    session_start();
    require_once dirname(__DIR__) . '/inclusoes/auth_check.php';
    if (!isAdmin()) {
        die("Acesso negado. Execute via CLI.");
    }
}

echo "=== INICIANDO ENVIO DE RELATÃ“RIOS FINAIS ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n";

$database = new Database();
$db = $database->getConnection();
$mailer = new SimpleMailer();

// Buscar anúncios que terminaram (end_date < HOJE) e ainda não receberam relatório final
$query = "SELECT * FROM ads 
          WHERE end_date < CURRENT_DATE 
          AND (final_report_sent_at IS NULL)
          AND client_email IS NOT NULL 
          AND is_active = true"; // Opcional: considerar apenas ativos que expiraram

$stmt = $db->query($query);
$ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

echo "Encontrados " . count($ads) . " anúncios para processar.\n\n";

$sent_count = 0;

foreach ($ads as $ad) {
    echo "Processando ID {$ad['ad_id']} - {$ad['title']}... ";
    
    try {
        // Calcular métricas finais
        $metrics = calculateFinalMetrics($db, $ad['ad_id']);
        
        // Gerar e enviar email
        $subject = "Relatório Final de Campanha - {$ad['title']}";
        $body = generateFinalReportEmailBody($ad, $metrics);
        
        if ($mailer->send($ad['client_email'], $ad['client_name'], $subject, $body)) {
            // Marcar como enviado
            $update = $db->prepare("UPDATE ads SET final_report_sent_at = NOW(), is_active = false WHERE ad_id = :ad_id");
            $update->execute([':ad_id' => $ad['ad_id']]);
            
            echo "âœ… Enviado!\n";
            $sent_count++;
        } else {
            echo "âŒ Falha no envio.\n";
        }
        
    } catch (Exception $e) {
        echo "âŒ Erro: " . $e->getMessage() . "\n";
    }
}

echo "\nTotal de emails enviados: $sent_count\n";
echo "Fim do script.\n";

function calculateFinalMetrics($db, $ad_id) {
    $views = $db->query("SELECT COUNT(*) FROM ad_metrics WHERE ad_id = $ad_id AND metric_type = 'view'")->fetchColumn();
    $clicks = $db->query("SELECT COUNT(*) FROM ad_metrics WHERE ad_id = $ad_id AND metric_type = 'click'")->fetchColumn();
    $unique = $db->query("SELECT COUNT(DISTINCT user_id) FROM ad_metrics WHERE ad_id = $ad_id AND user_id IS NOT NULL")->fetchColumn();
    
    $ctr = $views > 0 ? round(($clicks / $views) * 100, 2) : 0;
    
    return [
        'views' => $views,
        'clicks' => $clicks,
        'unique' => $unique,
        'ctr' => $ctr
    ];
}

function generateFinalReportEmailBody($ad, $metrics) {
    $start = date('d/m/Y', strtotime($ad['start_date']));
    $end = date('d/m/Y', strtotime($ad['end_date']));
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <link rel='icon' type='image/png' sizes='32x32' href='../recursos/images/marca/favicon-k-32x32.png'>
        <style>
            body { font-family: Arial, sans-serif; background: #f4f6f8; color: #333; margin: 0; padding: 20px; }
            .card { background: white; padding: 30px; border-radius: 8px; max-width: 600px; margin: 0 auto; box-shadow: 0 4px 10px rgba(0,0,0,0.1); }
            h1 { color: #f7941d; margin-top: 0; }
            .stat { margin-bottom: 15px; padding: 15px; background: #f9fafb; border-radius: 6px; }
            .stat-label { font-size: 12px; text-transform: uppercase; color: #666; font-weight: bold; }
            .stat-value { font-size: 24px; font-weight: bold; color: #1e293b; }
            .btn { background: #f7941d; color: white; padding: 10px 20px; text-decoration: none; border-radius: 4px; display: inline-block; margin-top: 20px; }
        </style>
    </head>
    <body>
        <div class='card'>
            <h1>Relatório Final: {$ad['title']}</h1>
            <p>Olá, {$ad['client_name']}. Sua campanha foi concluída com sucesso!</p>
            <p style='font-size: 14px; color: #666;'>Período: $start - $end</p>
            
            <hr style='border: 0; border-top: 1px solid #eee; margin: 20px 0;'>
            
            <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px;'>
                <div class='stat'>
                    <div class='stat-label'>Visualizações</div>
                    <div class='stat-value'>{$metrics['views']}</div>
                </div>
                <div class='stat'>
                    <div class='stat-label'>Cliques</div>
                    <div class='stat-value'>{$metrics['clicks']}</div>
                </div>
                <div class='stat'>
                    <div class='stat-label'>CTR</div>
                    <div class='stat-value'>{$metrics['ctr']}%</div>
                </div>
                <div class='stat'>
                    <div class='stat-label'>Alcance Ãšnico</div>
                    <div class='stat-value'>{$metrics['unique']}</div>
                </div>
            </div>
            
            <div style='text-align: center;'>
                <a href='https://aksanti.xyz/contact' class='btn'>Renovar Campanha</a>
            </div>
            
            <p style='margin-top: 30px; font-size: 12px; color: #999; text-align: center;'>&copy; 2026 KALIYE Platform</p>
        </div>
    </body>
    </html>
    ";
}

