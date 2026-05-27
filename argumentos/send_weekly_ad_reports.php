<?php
// processos/send_weekly_ad_reports.php
// Script para enviar relatórios semanais automáticos aos clientes de anúncios

// 1. Configurações iniciais e dependências
// Defino o fuso horário para garantir datas corretas
date_default_timezone_set('Africa/Luanda');

// Incluo as configurações de banco de dados e mailer
require_once dirname(__DIR__) . '/configuracoes/base_dados.php';
require_once dirname(__DIR__) . '/inclusoes/SimpleMailer.php';

// Verifico se está rodando via CLI para segurança (opcional, mas recomendado)
if (php_sapi_name() !== 'cli') {
    // Permito apenas se for admin logado, caso contrário bloqueio
    session_start();
    require_once dirname(__DIR__) . '/inclusoes/auth_check.php';
    if (!isAdmin()) {
        die("Acesso negado. Este script deve ser executado via linha de comando ou por admin.");
    }
}

echo "=== INICIANDO ENVIO DE RELATÃ“RIOS SEMANAIS ===\n";
echo "Data: " . date('Y-m-d H:i:s') . "\n\n";

// 2. Conexão com banco de dados
$database = new Database();
$db = $database->getConnection();
$mailer = new SimpleMailer();

// 3. Definir o período do relatório (últimos 7 dias)
$end_date = date('Y-m-d');
$start_date = date('Y-m-d', strtotime('-7 days'));
echo "Período do relatório: $start_date a $end_date\n";

// 4. Buscar anúncios ativos ou recém-finalizados que têm email de cliente
// Busco anúncios que estão ativos OU que terminaram nesta semana
$query = "SELECT * FROM ads 
          WHERE (is_active = true OR end_date >= :start_date)
          AND client_email IS NOT NULL 
          AND client_email != ''";

$stmt = $db->prepare($query);
$stmt->execute([':start_date' => $start_date]);
$ads = $stmt->fetchAll();

echo "Encontrados " . count($ads) . " anúncios para processar.\n\n";

$sent_count = 0;
$error_count = 0;

foreach ($ads as $ad) {
    echo "Processando anúncio: ID {$ad['ad_id']} - {$ad['title']}... ";

    try {
        // 5. Calcular métricas da semana
        // Busco visualizações desta semana
        $views_query = "SELECT COUNT(*) FROM ad_metrics 
                        WHERE ad_id = :ad_id 
                        AND metric_type = 'view' 
                        AND created_at BETWEEN :start AND :end";
        $stmt_views = $db->prepare($views_query);
        $stmt_views->execute([
            ':ad_id' => $ad['ad_id'],
            ':start' => "$start_date 00:00:00",
            ':end' => "$end_date 23:59:59"
        ]);
        $weekly_views = $stmt_views->fetchColumn();

        // Busco cliques desta semana
        $clicks_query = "SELECT COUNT(*) FROM ad_metrics 
                         WHERE ad_id = :ad_id 
                         AND metric_type = 'click' 
                         AND created_at BETWEEN :start AND :end";
        $stmt_clicks = $db->prepare($clicks_query);
        $stmt_clicks->execute([
            ':ad_id' => $ad['ad_id'],
            ':start' => "$start_date 00:00:00",
            ':end' => "$end_date 23:59:59"
        ]);
        $weekly_clicks = $stmt_clicks->fetchColumn();

        // Calculo usuários únicos da semana
        $unique_query = "SELECT COUNT(DISTINCT user_id) FROM ad_metrics 
                         WHERE ad_id = :ad_id 
                         AND created_at BETWEEN :start AND :end 
                         AND user_id IS NOT NULL";
        $stmt_unique = $db->prepare($unique_query);
        $stmt_unique->execute([
            ':ad_id' => $ad['ad_id'],
            ':start' => "$start_date 00:00:00",
            ':end' => "$end_date 23:59:59"
        ]);
        $weekly_unique = $stmt_unique->fetchColumn();

        // Calculo CTR semanal
        $weekly_ctr = $weekly_views > 0 ? round(($weekly_clicks / $weekly_views) * 100, 2) : 0;

        // Se não houve atividade, pulo o envio (ou envio relatório zerado, decido enviar zerado para manter informado)
        /* if ($weekly_views == 0 && $weekly_clicks == 0) {
            echo "Sem atividade na semana. Pulando.\n";
            continue;
        } */

        // 6. Gerar conteúdo do email
        $html_content = generateEmailBody($ad, $weekly_views, $weekly_clicks, $weekly_ctr, $weekly_unique, $start_date, $end_date);
        
        // 7. Enviar email
        $subject = "Relatório Semanal de Desempenho - {$ad['title']}";
        $to = $ad['client_email'];
        $name = $ad['client_name'] ?? 'Cliente';

        if ($mailer->send($to, $name, $subject, $html_content)) {
            // 8. Registrar envio no banco de dados
            $log_query = "INSERT INTO ad_weekly_reports 
                          (ad_id, week_start, week_end, total_views, total_clicks, ctr, unique_users, report_sent, sent_at) 
                          VALUES (:ad_id, :start, :end, :views, :clicks, :ctr, :unique, true, NOW())";
            
            $stmt_log = $db->prepare($log_query);
            $stmt_log->execute([
                ':ad_id' => $ad['ad_id'],
                ':start' => $start_date,
                ':end' => $end_date,
                ':views' => $weekly_views,
                ':clicks' => $weekly_clicks,
                ':ctr' => $weekly_ctr,
                ':unique' => $weekly_unique
            ]);

            echo "âœ… Email enviado para $to\n";
            $sent_count++;
        } else {
            echo "âŒ Erro ao enviar email para $to\n";
            $error_count++;
        }

    } catch (Exception $e) {
        echo "âŒ Erro crítico: " . $e->getMessage() . "\n";
        $error_count++;
    }
}

echo "\n=== RESUMO ===\n";
echo "Enviados: $sent_count\n";
echo "Erros: $error_count\n";
echo "Fim script.\n";

/**
 * Função auxiliar para gerar o corpo do email
 * Utilizo estilos inline para compatibilidade com clientes de email
 */
function generateEmailBody($ad, $views, $clicks, $ctr, $unique, $start, $end) {
    // Formato as datas para exibição amigável
    $start_fmt = date('d/m/Y', strtotime($start));
    $end_fmt = date('d/m/Y', strtotime($end));
    
    // Calculo totais gerais para contexto
    $total_views = $ad['views'];
    $total_clicks = $ad['clicks'];
    
    // Defino as cores da marca
    $bg_color = "#f4f6f8";
    $primary_color = "#3b82f6";
    $text_color = "#334155";
    
    return "
    <!DOCTYPE html>
    <html>
    <head>
        <meta charset='UTF-8'>
        <link rel='icon' type='image/png' sizes='32x32' href='../recursos/images/marca/favicon-k-32x32.png'>
        <title>Relatório Semanal</title>
    </head>
    <body style='font-family: Arial, sans-serif; background-color: $bg_color; margin: 0; padding: 20px; color: $text_color;'>
        <div style='max-width: 600px; margin: 0 auto; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px rgba(0,0,0,0.1);'>
            <!-- Cabeçalho -->
            <div style='background: linear-gradient(135deg, #1e293b 0%, #0f172a 100%); padding: 30px 20px; text-align: center; color: white;'>
                <h1 style='margin: 0; font-size: 24px;'>Relatório Semanal</h1>
                <p style='margin: 10px 0 0; opacity: 0.9;'>{$ad['title']}</p>
                <p style='margin: 5px 0 0; font-size: 14px; opacity: 0.7;'>Período: $start_fmt a $end_fmt</p>
            </div>
            
            <!-- Conteúdo Principal -->
            <div style='padding: 30px 20px;'>
                <p style='margin-bottom: 20px;'>Olá, <strong>" . htmlspecialchars($ad['client_name'] ?? 'Cliente') . "</strong>,</p>
                <p style='margin-bottom: 30px; line-height: 1.5;'>Aqui está o desempenho do seu anúncio nesta semana. Continuamos monitorando e otimizando a entrega para alcançar os melhores resultados.</p>
                
                <!-- Grid de Métricas -->
                <div style='display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 30px;'>
                    <div style='background: #f8fafc; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px;'>Visualizações</div>
                        <div style='font-size: 24px; font-weight: bold; color: #3b82f6;'>$views</div>
                    </div>
                    <div style='background: #f8fafc; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px;'>Cliques</div>
                        <div style='font-size: 24px; font-weight: bold; color: #f97316;'>$clicks</div>
                    </div>
                    <div style='background: #f8fafc; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px;'>CTR</div>
                        <div style='font-size: 24px; font-weight: bold; color: #eab308;'>$ctr%</div>
                    </div>
                    <div style='background: #f8fafc; padding: 15px; border-radius: 6px; text-align: center; border: 1px solid #e2e8f0;'>
                        <div style='font-size: 12px; text-transform: uppercase; color: #64748b; margin-bottom: 5px;'>Usuários Ãšnicos</div>
                        <div style='font-size: 24px; font-weight: bold; color: #10b981;'>$unique</div>
                    </div>
                </div>
                
                <div style='border-top: 1px solid #e2e8f0; margin: 20px 0; padding-top: 20px;'>
                    <h3 style='margin: 0 0 15px; font-size: 16px; color: #1e293b;'>ACUMULADO TOTAL</h3>
                    <p style='margin: 0; color: #64748b;'>Desde o início da campanha, seu anúncio já acumulou <strong>$total_views visualizações</strong> e <strong>$total_clicks cliques</strong>.</p>
                </div>
                
                <div style='text-align: center; margin-top: 30px;'>
                    <a href='https://aksanti.xyz/contact' style='background: #3b82f6; color: white; text-decoration: none; padding: 12px 25px; border-radius: 6px; font-weight: bold; display: inline-block;'>Fale com um Consultor</a>
                </div>
            </div>
            
            <!-- Rodapé -->
            <div style='background: #f8fafc; padding: 20px; text-align: center; font-size: 12px; color: #94a3b8; border-top: 1px solid #e2e8f0;'>
                <p style='margin: 0 0 10px;'>KALIYE Platform &copy; " . date('Y') . "</p>
                <p style='margin: 0;'>Você está recebendo este email porque possui uma campanha ativa conosco.</p>
            </div>
        </div>
    </body>
    </html>
    ";
}

