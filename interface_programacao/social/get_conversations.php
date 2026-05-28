<?php
// servicos/social/get_conversations.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$uid = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();
ChatSecurity::touchPresence($db, $uid);

try {
    $query = "SELECT DISTINCT
                u.user_id, u.full_name as name, u.profile_pic, u.user_type, u.mentorship_status, u.last_activity,
                (SELECT content FROM messages WHERE (sender_id = u.user_id AND receiver_id = :uid) OR (sender_id = :uid AND receiver_id = u.user_id) ORDER BY sent_at DESC LIMIT 1) as last_message,
                (SELECT sent_at FROM messages WHERE (sender_id = u.user_id AND receiver_id = :uid) OR (sender_id = :uid AND receiver_id = u.user_id) ORDER BY sent_at DESC LIMIT 1) as last_message_time,
                (SELECT COUNT(*) FROM messages WHERE sender_id = u.user_id AND receiver_id = :uid AND CAST(is_read AS INTEGER) = 0) as unread_count
              FROM users u
              JOIN messages m ON (m.sender_id = u.user_id OR m.receiver_id = u.user_id)
              WHERE (m.sender_id = :uid OR m.receiver_id = :uid) AND u.user_id != :uid
              ORDER BY last_message_time DESC";

    $stmt = $db->prepare($query);
    $stmt->execute([':uid' => $uid]);
    $conversations = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($conversations as &$conv) {
        $conv['last_message'] = ChatSecurity::revealContent($conv['last_message'] ?? '');
        $conv['profile_pic'] = ChatSecurity::normalizeAvatar($conv);
        $presence = ChatSecurity::onlineMeta($conv['last_activity'] ?? null);
        $conv['is_online'] = $presence['is_online'];
        $conv['presence_label'] = $presence['label'];
        $conv['is_typing'] = false;
    }

    echo json_encode(['success' => true, 'conversations' => $conversations]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao carregar conversas.']);
}
?>
