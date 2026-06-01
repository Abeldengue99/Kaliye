<?php
/**
 * Maintenance: Fix Doubt Notification Links
 * URI: /kaliye/manutenacao_duvidas.php
 * Secret key required: ?key=ADMIN_FIX_KEY
 */

// Define secret key (change this to something secure)
define('MAINTENANCE_KEY', 'aksanti_fix_doubts_2026');

header('Content-Type: application/json; charset=utf-8');

// Check if secret key is provided
$provided_key = $_GET['key'] ?? $_POST['key'] ?? '';

if (empty($provided_key)) {
    http_response_code(401);
    echo json_encode(['error' => 'Chave de manutenção necessária']);
    exit;
}

if ($provided_key !== MAINTENANCE_KEY) {
    http_response_code(403);
    echo json_encode(['error' => 'Chave inválida']);
    exit;
}

// Try to execute the fix
try {
    require_once 'configuracoes/base_dados.php';
    
    $database = new Database();
    $db = $database->getConnection();

    // Count incorrect links
    $check = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $row = $check->fetch(PDO::FETCH_ASSOC);
    $old_count = $row['cnt'] ?? 0;

    $response = [
        'status' => 'success',
        'found' => $old_count,
        'updated' => 0,
        'remaining' => 0,
        'message' => ''
    ];

    if ($old_count > 0) {
        // Fix the links
        $updated = $db->exec("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
        
        // Verify
        $verify = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
        $row = $verify->fetch(PDO::FETCH_ASSOC);
        $remaining = $row['cnt'] ?? 0;

        $response['updated'] = $updated;
        $response['remaining'] = $remaining;
        $response['message'] = "✅ Notificações corrigidas com sucesso! " . $updated . " links foram atualizados.";
    } else {
        $response['message'] = "ℹ️ Sem correções necessárias - Todas as notificações já estão corretas.";
    }

    echo json_encode($response, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage(),
        'message' => 'Erro ao conectar à base de dados'
    ]);
}
?>
