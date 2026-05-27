<?php
session_start();
require_once '../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Not authenticated']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();
    
    $user_id = $_SESSION['user_id'];
    
    // Contagem de mensagens não lidas (Sintaxe Universal via CAST com filtro de data)
    $msg_stmt = $db->prepare("SELECT COUNT(*) FROM messages m JOIN users u ON m.receiver_id = u.user_id WHERE m.receiver_id = ? AND CAST(m.is_read AS INTEGER) = 0 AND m.sent_at >= u.created_at");
    $msg_stmt->execute([$user_id]);
    $unread_messages = (int)$msg_stmt->fetchColumn();
    
    // Contagem de notificações não lidas (Filtramos para que novos usuários não recebam lixo histórico)
    $notif_stmt = $db->prepare("SELECT COUNT(*) FROM notifications n JOIN users u ON n.user_id = u.user_id WHERE n.user_id = ? AND CAST(n.is_read AS INTEGER) = 0 AND n.created_at >= u.created_at AND COALESCE(n.type, '') <> 'message'");
    $notif_stmt->execute([$user_id]);
    $unread_notifications = (int)$notif_stmt->fetchColumn();

    // Contagem de dúvidas abertas da comunidade publicadas por outros utilizadores.
    // Filtramos para mostrar apenas o que foi publicado APÓS o registro do usuário para evitar poluição imediata.
    $db->exec("CREATE TABLE IF NOT EXISTS user_doubt_views (
        user_id INTEGER PRIMARY KEY,
        last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $doubt_stmt = $db->prepare("
        SELECT COUNT(*)
        FROM doubts d
        JOIN users u ON u.user_id = ?
        LEFT JOIN user_doubt_views v ON v.user_id = u.user_id
        WHERE d.status = 'open'
          AND d.user_id != u.user_id
          AND d.created_at >= u.created_at
          AND d.created_at > COALESCE(v.last_seen_at, u.created_at)
    ");
    $doubt_stmt->execute([$user_id]);
    $open_doubts = (int)$doubt_stmt->fetchColumn();

    $_SESSION['header_counts'] = [
        'messages' => $unread_messages,
        'notifications' => $unread_notifications,
        'doubts' => $open_doubts
    ];
    
    // Check if user is admin to optionally return admin metrics
    $is_admin = isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'admin';
    $admin_counts = [];
    
    if ($is_admin) {
        $admin_counts['kyc'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'")->fetchColumn();
        $admin_counts['mentors'] = (int)$db->query("SELECT COUNT(*) FROM users WHERE mentor_status = 'pending'")->fetchColumn();
        $admin_counts['investments'] = (int)$db->query("SELECT COUNT(*) FROM project_investments WHERE status = 'pending'")->fetchColumn();
        $admin_counts['support'] = (int)$db->query("SELECT COUNT(*) FROM support_messages WHERE CAST(is_read AS INTEGER) = 0")->fetchColumn();
        
        try {
            $admin_counts['moderation'] = (int)$db->query("SELECT COUNT(*) FROM projects WHERE approval_status = 'pending'")->fetchColumn();
        } catch (Exception $e) {
            $admin_counts['moderation'] = (int)$db->query("SELECT COUNT(*) FROM projects WHERE is_public = false")->fetchColumn();
        }
    }
    
    echo json_encode([
        'success'       => true, // Indicador de sucesso da operação para o JavaScript de polling.
        'messages'      => $unread_messages, // Total de mensagens privadas não lidas pelo utilizador activo.
        'notifications' => $unread_notifications, // Total de notificações do sistema não lidas.
        'doubts'        => $open_doubts, // Total de dúvidas abertas por outros membros da comunidade.
        'is_admin'      => $is_admin, // Indicador de perfil administrativo para mostrar métricas adicionais.
        'admin_counts'  => $admin_counts // Métricas exclusivas do painel de administração.
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
