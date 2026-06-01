<?php
/**
 * API: Corrigir Links de Notificações de Dúvida
 * Endpoint de limpeza de dados administrativo
 */

// Segurança: apenas localhost ou IP autorizado
$allowed_ips = ['127.0.0.1', 'localhost', '::1', '192.168.0.195'];
$client_ip = $_SERVER['REMOTE_ADDR'] ?? $_SERVER['HTTP_X_FORWARDED_FOR'] ?? 'unknown';

// Remover IPv6 prefix se presente
$client_ip = str_replace('::ffff:', '', $client_ip);

if (!in_array($client_ip, $allowed_ips)) {
    http_response_code(403);
    die(json_encode(['error' => 'Acesso negado de ' . $client_ip]));
}

require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json; charset=utf-8');

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Contar notificações com link incorreto
    $check_old = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $old_count = $check_old->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    $result = [
        'status' => 'success',
        'old_count' => $old_count,
        'updated' => 0,
        'remaining' => 0,
        'message' => ''
    ];

    if ($old_count > 0) {
        // 2. Atualizar links
        $updated = $db->exec("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
        
        // 3. Verificar se todas foram corrigidas
        $verify = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
        $remaining = $verify->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

        $result['updated'] = $updated;
        $result['remaining'] = $remaining;
        $result['message'] = "Notificações atualizadas com sucesso: $updated";
    } else {
        $result['message'] = "Sem correções necessárias - Todas as notificações já estão corretas";
    }

    echo json_encode($result, JSON_UNESCAPED_UNICODE);

} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'error' => $e->getMessage()
    ]);
}
?>
