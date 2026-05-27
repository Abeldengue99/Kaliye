<?php
// servicos/social/get_group_messages.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$group_id = $_GET['group_id'] ?? null;

if (!$group_id) {
    echo json_encode(['success' => false, 'error' => 'Grupo não identificado']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
ChatSecurity::touchPresence($db, (int)$_SESSION['user_id']);

try {
    $member_stmt = $db->prepare("SELECT 1 FROM chat_group_members WHERE group_id = ? AND user_id = ? LIMIT 1");
    $member_stmt->execute([(int)$group_id, (int)$_SESSION['user_id']]);
    if (!$member_stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'Sem permissao para ler esta sala.']);
        exit();
    }

    $query = "SELECT gm.*, u.full_name, u.profile_pic, u.user_type, u.mentorship_status
              FROM group_messages gm 
              JOIN users u ON gm.sender_id = u.user_id 
              WHERE gm.group_id = :gid 
              ORDER BY gm.sent_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':gid' => $group_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $msg['content'] = ChatSecurity::revealContent($msg['content'] ?? '');
        $msg['profile_pic'] = ChatSecurity::normalizeAvatar($msg);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

