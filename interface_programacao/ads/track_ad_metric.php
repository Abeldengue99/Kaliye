<?php
// servicos/ads/track_ad_metric.php
// API para rastrear visualizações e cliques de anúncios
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método não permitido']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$ad_id = isset($_POST['ad_id']) ? (int)$_POST['ad_id'] : 0;
$metric_type = isset($_POST['metric_type']) ? $_POST['metric_type'] : 'view'; // 'view' ou 'click'

if ($ad_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de anúncio inválido']);
    exit;
}

// Validar tipo de métrica
if (!in_array($metric_type, ['view', 'click'])) {
    echo json_encode(['success' => false, 'message' => 'Tipo de métrica inválido']);
    exit;
}

try {
    // Garante o livro de eventos real usado em relatorios pagos.
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
    $db->exec("ALTER TABLE ads ADD COLUMN IF NOT EXISTS views INT DEFAULT 0");
    $db->exec("ALTER TABLE ads ADD COLUMN IF NOT EXISTS clicks INT DEFAULT 0");
    // Verificar se o anúncio existe e está ativo
    $check_query = "SELECT ad_id, is_active, start_date, end_date FROM ads WHERE ad_id = :ad_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':ad_id' => $ad_id]);
    $ad = $check_stmt->fetch();
    
    if (!$ad) {
        echo json_encode(['success' => false, 'message' => 'Anúncio não encontrado']);
        exit;
    }
    
    // Verificar se está ativo e dentro do período
    if (!$ad['is_active']) {
        echo json_encode(['success' => false, 'message' => 'Anúncio inativo']);
        exit;
    }
    
    $today = date('Y-m-d');
    if ($ad['start_date'] && $today < $ad['start_date']) {
        echo json_encode(['success' => false, 'message' => 'Campanha ainda não iniciou']);
        exit;
    }
    
    if ($ad['end_date'] && $today > $ad['end_date']) {
        echo json_encode(['success' => false, 'message' => 'Campanha já encerrou']);
        exit;
    }
    
    // Obter informações do usuário
    $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
    $ip_address = $_SERVER['REMOTE_ADDR'] ?? null;
    $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;
    $referrer = $_SERVER['HTTP_REFERER'] ?? null;
    
    // Registrar métrica detalhada
    $metric_query = "INSERT INTO ad_metrics (ad_id, metric_type, user_id, ip_address, user_agent, referrer) 
                     VALUES (:ad_id, :metric_type, :user_id, :ip_address, :user_agent, :referrer)";
    $metric_stmt = $db->prepare($metric_query);
    $metric_stmt->execute([
        ':ad_id' => $ad_id,
        ':metric_type' => $metric_type,
        ':user_id' => $user_id,
        ':ip_address' => $ip_address,
        ':user_agent' => $user_agent,
        ':referrer' => $referrer
    ]);
    
    // Atualizar contador na tabela principal
    if ($metric_type === 'view') {
        $update_query = "UPDATE ads SET views = views + 1 WHERE ad_id = :ad_id";
    } else {
        $update_query = "UPDATE ads SET clicks = clicks + 1 WHERE ad_id = :ad_id";
    }
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([':ad_id' => $ad_id]);
    
    echo json_encode([
        'success' => true, 
        'message' => 'Métrica registrada com sucesso',
        'metric_type' => $metric_type
    ]);
    
} catch (Exception $e) {
    error_log("Erro ao rastrear métrica: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar métrica']);
}

