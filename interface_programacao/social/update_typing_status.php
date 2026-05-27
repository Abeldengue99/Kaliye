<?php
// servicos/social/update_typing_status.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nao autenticado']);
    exit();
}

$uid = (int)$_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;

try {
    $db = (new Database())->getConnection();
    ChatSecurity::touchPresence($db, $uid);
    ChatSecurity::ensureTypingTable($db);

    if ($receiver_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Destinatario invalido']);
        exit();
    }

    $policy = ChatSecurity::canDirectMessage($db, $uid, $receiver_id);
    if (!$policy['allowed']) {
        echo json_encode(['success' => false, 'error' => $policy['reason']]);
        exit();
    }

    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
        $stmt = $db->prepare("
            INSERT INTO chat_typing_status (user_id, receiver_id, updated_at)
            VALUES (?, ?, NOW())
            ON CONFLICT (user_id, receiver_id) DO UPDATE SET updated_at = NOW()
        ");
        $stmt->execute([$uid, $receiver_id]);
    } else {
        $stmt = $db->prepare("
            INSERT INTO chat_typing_status (user_id, receiver_id, updated_at)
            VALUES (?, ?, NOW())
            ON DUPLICATE KEY UPDATE updated_at = NOW()
        ");
        $stmt->execute([$uid, $receiver_id]);
    }

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Falha ao atualizar digitacao.']);
}
?>
