<?php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

$ad_id = isset($_GET['ad_id']) ? (int)$_GET['ad_id'] : 0;
if ($ad_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalido']);
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

    $ad_stmt = $db->prepare("SELECT * FROM ads WHERE ad_id = :ad_id");
    $ad_stmt->execute([':ad_id' => $ad_id]);
    $ad = $ad_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$ad) {
        echo json_encode(['success' => false, 'message' => 'Anuncio nao encontrado']);
        exit;
    }

    $totals_stmt = $db->prepare("SELECT
            COUNT(*) FILTER (WHERE metric_type = 'view') AS total_views,
            COUNT(*) FILTER (WHERE metric_type = 'click') AS total_clicks,
            COUNT(DISTINCT COALESCE(user_id::text, md5(COALESCE(ip_address, '') || COALESCE(user_agent, '')))) AS reach
        FROM ad_metrics
        WHERE ad_id = :ad_id");
    $totals_stmt->execute([':ad_id' => $ad_id]);
    $totals = $totals_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $total_views = (int)($totals['total_views'] ?? 0);
    $total_clicks = (int)($totals['total_clicks'] ?? 0);
    $reach = (int)($totals['reach'] ?? 0);
    $ctr = $total_views > 0 ? round(($total_clicks / $total_views) * 100, 2) : 0;

    $daily_stmt = $db->prepare("SELECT
            created_at::DATE AS date,
            metric_type,
            COUNT(*) AS count
        FROM ad_metrics
        WHERE ad_id = :ad_id
          AND created_at >= NOW() - INTERVAL '30 days'
        GROUP BY created_at::DATE, metric_type
        ORDER BY date ASC");
    $daily_stmt->execute([':ad_id' => $ad_id]);
    $daily_data_map = [];
    foreach ($daily_stmt->fetchAll(PDO::FETCH_ASSOC) as $metric) {
        $date = $metric['date'];
        if (!isset($daily_data_map[$date])) {
            $daily_data_map[$date] = ['date' => $date, 'views' => 0, 'clicks' => 0];
        }
        if ($metric['metric_type'] === 'view') {
            $daily_data_map[$date]['views'] = (int)$metric['count'];
        } elseif ($metric['metric_type'] === 'click') {
            $daily_data_map[$date]['clicks'] = (int)$metric['count'];
        }
    }

    $hourly_stmt = $db->prepare("SELECT
            EXTRACT(HOUR FROM created_at)::int AS hour,
            COUNT(*) AS interactions
        FROM ad_metrics
        WHERE ad_id = :ad_id
        GROUP BY hour
        ORDER BY hour ASC");
    $hourly_stmt->execute([':ad_id' => $ad_id]);
    $hours_map = [];
    foreach ($hourly_stmt->fetchAll(PDO::FETCH_ASSOC) as $h) {
        $hours_map[(int)$h['hour']] = (int)$h['interactions'];
    }
    $peak_hours = [];
    for ($i = 0; $i < 24; $i++) {
        $peak_hours[] = [
            'hour' => str_pad((string)$i, 2, '0', STR_PAD_LEFT),
            'interactions' => $hours_map[$i] ?? 0,
        ];
    }

    $days_remaining = null;
    if (!empty($ad['end_date'])) {
        $end = new DateTime($ad['end_date']);
        $now = new DateTime();
        $diff = $now->diff($end);
        $days_remaining = $diff->days * ($diff->invert ? -1 : 1);
    }

    $budget = (float)($ad['budget'] ?? 0);
    echo json_encode([
        'success' => true,
        'ad' => $ad,
        'metrics' => [
            'total_views' => $total_views,
            'total_clicks' => $total_clicks,
            'ctr' => $ctr,
            'unique_users' => $reach,
            'reach' => $reach,
            'cpc' => $total_clicks > 0 ? round($budget / $total_clicks, 2) : 0,
            'cpv' => $total_views > 0 ? round($budget / $total_views, 2) : 0,
            'days_remaining' => $days_remaining,
        ],
        'daily_data' => array_values($daily_data_map),
        'peak_hours' => $peak_hours,
        'campaign_status' => [
            'is_active' => (bool)$ad['is_active'],
            'start_date' => $ad['start_date'],
            'end_date' => $ad['end_date'],
            'budget' => $budget,
            'payment_status' => $ad['payment_status'],
        ],
    ]);
} catch (Exception $e) {
    error_log('Erro ao buscar analytics: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar dados']);
}
