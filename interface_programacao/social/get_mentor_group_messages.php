<?php
/**
 * Secure polling endpoint for mentor VIP chat groups.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $uid = (int)$_SESSION['user_id'];
    ChatSecurity::touchPresence($db, $uid);

    $group_id = isset($_GET['group_id']) ? (int)$_GET['group_id'] : 0;
    if ($group_id <= 0) {
        throw new Exception('Referencia de grupo invalida.');
    }

    $access = $db->prepare("
        SELECT 1
        FROM mentor_chat_groups mg
        WHERE mg.id = :gid
          AND (
              mg.mentor_id = :uid
              OR mg.mentor_id IN (
                  SELECT mentor_id FROM mentorship_contracts WHERE student_id = :uid AND status = 'active'
                  UNION
                  SELECT mentor_id FROM mentorships WHERE mentee_id = :uid AND status = 'active'
              )
          )
        LIMIT 1
    ");
    $access->execute([':gid' => $group_id, ':uid' => $uid]);
    if (!$access->fetchColumn()) {
        throw new Exception('Sem permissao para ler esta sala de mentoria.');
    }

    $query = "
        SELECT m.*, u.full_name, u.profile_pic, u.user_type, u.mentorship_status
        FROM mentor_group_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.group_id = :gid
        ORDER BY m.created_at ASC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute([':gid' => $group_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $formatted = [];
    foreach ($messages as $msg) {
        $formatted[] = [
            'id' => (int)$msg['id'],
            'sender_id' => (int)$msg['sender_id'],
            'sender_name' => htmlspecialchars($msg['full_name']),
            'sender_type' => $msg['user_type'],
            'profile_pic' => ChatSecurity::normalizeAvatar($msg),
            'message' => ($msg['message_type'] ?: 'text') === 'text' ? ChatSecurity::revealContent($msg['message'] ?? '') : $msg['message'],
            'message_type' => $msg['message_type'] ?: 'text',
            'file_url' => $msg['file_url'],
            'time' => date('H:i', strtotime($msg['created_at'])),
            'timestamp' => $msg['created_at']
        ];
    }

    echo json_encode(['success' => true, 'messages' => $formatted]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
