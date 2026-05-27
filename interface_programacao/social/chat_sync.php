<?php
// servicos/social/chat_sync.php - direct chat API with server-side policy.
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Nao autenticado']);
    exit();
}

$current_user_id = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();
ChatSecurity::touchPresence($db, $current_user_id);

function chat_bool($value): bool {
    return $value === true || $value === 1 || $value === '1' || $value === 't';
}

if (isset($_GET['get_user_name'])) {
    $uid = (int)$_GET['get_user_name'];
    $policy = ChatSecurity::canDirectMessage($db, $current_user_id, $uid);
    if (!$policy['allowed']) {
        echo json_encode(['success' => false, 'error' => $policy['reason']]);
        exit();
    }

    $stmt = $db->prepare("SELECT full_name, profile_pic, user_type, mentorship_status, last_activity FROM users WHERE user_id = ?");
    $stmt->execute([$uid]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$user) {
        echo json_encode(['success' => false, 'error' => 'Utilizador nao encontrado']);
        exit();
    }

    $presence = ChatSecurity::onlineMeta($user['last_activity'] ?? null);
    $isTyping = false;
    try {
        ChatSecurity::ensureTypingTable($db);
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $typingSql = "SELECT 1 FROM chat_typing_status WHERE user_id = ? AND receiver_id = ? AND updated_at >= NOW() - INTERVAL '6 seconds' LIMIT 1";
        } else {
            $typingSql = "SELECT 1 FROM chat_typing_status WHERE user_id = ? AND receiver_id = ? AND updated_at >= DATE_SUB(NOW(), INTERVAL 6 SECOND) LIMIT 1";
        }
        $typingStmt = $db->prepare($typingSql);
        $typingStmt->execute([$uid, $current_user_id]);
        $isTyping = (bool)$typingStmt->fetchColumn();
    } catch (Throwable $e) {}

    echo json_encode([
        'success' => true,
        'full_name' => $user['full_name'],
        'profile_pic' => ChatSecurity::normalizeAvatar($user),
        'is_online' => $presence['is_online'],
        'presence_label' => $isTyping ? 'A escrever...' : $presence['label'],
        'is_typing' => $isTyping
    ]);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
    $content = ChatSecurity::normalizeText($_POST['content'] ?? '');

    if ($receiver_id <= 0) {
        echo json_encode(['success' => false, 'error' => 'Destinatario nao informado']);
        exit();
    }

    $policy = ChatSecurity::canDirectMessage($db, $current_user_id, $receiver_id);
    if (!$policy['allowed']) {
        echo json_encode(['success' => false, 'error' => $policy['reason']]);
        exit();
    }

    $rate = ChatSecurity::checkRateLimit($db, $current_user_id, 'direct');
    if (!$rate['allowed']) {
        echo json_encode(['success' => false, 'error' => $rate['reason']]);
        exit();
    }

    $safety = ChatSecurity::analyzeOutgoingMessage($db, $current_user_id, 'direct', $receiver_id, $content);
    if (!$safety['allowed']) {
        echo json_encode(['success' => false, 'error' => $safety['reason']]);
        exit();
    }

    $media_url = null;
    $media_type = null;
    if (isset($_FILES['media']) && $_FILES['media']['error'] !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Falha no upload do anexo.']);
            exit();
        }
        $stored = ChatSecurity::storeChatMedia($_FILES['media'], $current_user_id);
        if (!$stored['ok']) {
            echo json_encode(['success' => false, 'error' => $stored['error']]);
            exit();
        }
        $media_url = $stored['path'];
        $media_type = $stored['type'];
    }

    if ($content === '' && !$media_url) {
        echo json_encode(['success' => false, 'error' => 'Mensagem vazia.']);
        exit();
    }

    try {
        $query = "INSERT INTO messages (sender_id, receiver_id, content, media_url, media_type, sent_at, is_read)
                  VALUES (:sid, :rid, :content, :murl, :mtype, NOW(), '0')";
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':sid' => $current_user_id,
            ':rid' => $receiver_id,
            ':content' => ChatSecurity::protectContent($content),
            ':murl' => $media_url,
            ':mtype' => $media_type
        ]);

        $senderStmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $senderStmt->execute([$current_user_id]);
        $senderName = $senderStmt->fetchColumn() ?: 'Membro KALIYE';
        $notifBody = $content !== '' ? mb_substr($content, 0, 80) : '[Anexo enviado]';

        try {
            $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'message', ?)");
            $notif->execute([
                $receiver_id,
                $current_user_id,
                'Mensagem de ' . $senderName,
                $notifBody,
                'paginas/social/messages.php?start=' . $current_user_id
            ]);
        } catch (Throwable $e) {}

        echo json_encode(['success' => true]);
        exit();
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'error' => 'Erro ao gravar mensagem.']);
        exit();
    }
}

$receiver_id = isset($_GET['receiver_id']) ? (int)$_GET['receiver_id'] : 0;
if ($receiver_id <= 0) {
    echo json_encode([]);
    exit();
}

$policy = ChatSecurity::canDirectMessage($db, $current_user_id, $receiver_id);
if (!$policy['allowed']) {
    echo json_encode(['success' => false, 'error' => $policy['reason'], 'messages' => []]);
    exit();
}

try {
    $mark_read = $db->prepare("UPDATE messages SET is_read = '1' WHERE sender_id = ? AND receiver_id = ? AND CAST(is_read AS INTEGER) = 0");
    $mark_read->execute([$receiver_id, $current_user_id]);

    $query = "SELECT m.*, u.full_name, u.profile_pic, u.user_type, u.mentorship_status, u.last_activity
              FROM messages m
              JOIN users u ON m.sender_id = u.user_id
              WHERE (m.sender_id = :cuid AND m.receiver_id = :rid)
                 OR (m.sender_id = :rid AND m.receiver_id = :cuid)
              ORDER BY m.sent_at ASC";
    $stmt = $db->prepare($query);
    $stmt->execute([':cuid' => $current_user_id, ':rid' => $receiver_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($messages as &$msg) {
        $msg['content'] = ChatSecurity::revealContent($msg['content'] ?? '');
        $msg['is_sent'] = ((int)$msg['sender_id'] === $current_user_id);
        $msg['status'] = chat_bool($msg['is_read']) ? 'read' : 'delivered';
        $msg['profile_pic'] = ChatSecurity::normalizeAvatar($msg);
    }

    echo json_encode($messages);
} catch (PDOException $e) {
    echo json_encode([]);
}
?>
