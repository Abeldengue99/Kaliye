<?php
// servicos/social/get_messages.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nao autenticado']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$other_user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;

if ($other_user_id <= 0) {
    echo json_encode(['success' => false, 'error' => 'ID do usuario nao fornecido']);
    exit();
}

$db = (new Database())->getConnection();
ChatSecurity::touchPresence($db, $current_user_id);

$policy = ChatSecurity::canDirectMessage($db, $current_user_id, $other_user_id);
if (!$policy['allowed']) {
    echo json_encode(['success' => false, 'error' => $policy['reason'], 'messages' => []]);
    exit();
}

try {
    $query = "SELECT m.*, u.profile_pic, u.full_name, u.user_type, u.mentorship_status, u.last_activity
              FROM messages m
              JOIN users u ON m.sender_id = u.user_id
              WHERE (m.sender_id = :cuid AND m.receiver_id = :ouid)
                 OR (m.sender_id = :ouid AND m.receiver_id = :cuid)
              ORDER BY m.sent_at ASC";

    $stmt = $db->prepare($query);
    $stmt->execute([':cuid' => $current_user_id, ':ouid' => $other_user_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $update = $db->prepare("UPDATE messages SET is_read = '1' WHERE sender_id = ? AND receiver_id = ? AND CAST(is_read AS INTEGER) = 0");
    $update->execute([$other_user_id, $current_user_id]);

    foreach ($messages as &$msg) {
        $msg['content'] = ChatSecurity::revealContent($msg['content'] ?? '');
        $msg['is_sent'] = ((int)$msg['sender_id'] === $current_user_id);
        $msg['status'] = in_array($msg['is_read'], [true, 1, '1', 't'], true) ? 'read' : 'delivered';
        $msg['profile_pic'] = ChatSecurity::normalizeAvatar($msg);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro no servidor.']);
}
?>
