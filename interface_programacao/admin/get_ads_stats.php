<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('ads')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

try {
    $db = (new Database())->getConnection();
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

    $stmt = $db->query("SELECT
            a.ad_id,
            COUNT(m.*) FILTER (WHERE m.metric_type = 'view') AS views,
            COUNT(m.*) FILTER (WHERE m.metric_type = 'click') AS clicks
        FROM ads a
        LEFT JOIN ad_metrics m ON m.ad_id = a.ad_id
        GROUP BY a.ad_id
        ORDER BY a.ad_id ASC");
    $ads = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $total_views = 0;
    $total_clicks = 0;
    $ads_map = [];

    foreach ($ads as $ad) {
        $v = (int)($ad['views'] ?? 0);
        $c = (int)($ad['clicks'] ?? 0);
        $total_views += $v;
        $total_clicks += $c;
        $ads_map[$ad['ad_id']] = ['views' => $v, 'clicks' => $c];
    }

    echo json_encode([
        'success' => true,
        'ads' => $ads_map,
        'total_views' => $total_views,
        'total_clicks' => $total_clicks,
        'total_ads' => count($ads),
        'timestamp' => time(),
    ]);
} catch (Exception $e) {
    error_log('get_ads_stats error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar estatisticas']);
}
