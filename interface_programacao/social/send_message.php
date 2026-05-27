<?php
/**
 * interface_programacao/social/send_message.php - secure direct message sender.
 */
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sessao expirada.']);
    exit();
}
requireValidCSRFTokenJson();

$sender_id = (int)$_SESSION['user_id'];
$receiver_id = isset($_POST['receiver_id']) ? (int)$_POST['receiver_id'] : 0;
$content = ChatSecurity::normalizeText($_POST['content'] ?? '');

if ($receiver_id <= 0 || ($content === '' && empty($_FILES['media']))) {
    echo json_encode(['success' => false, 'error' => 'Dossier incompleto para envio.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    ChatSecurity::touchPresence($db, $sender_id);

    $policy = ChatSecurity::canDirectMessage($db, $sender_id, $receiver_id);
    if (!$policy['allowed']) {
        echo json_encode(['success' => false, 'error' => $policy['reason']]);
        exit();
    }

    $rate = ChatSecurity::checkRateLimit($db, $sender_id, 'direct');
    if (!$rate['allowed']) {
        echo json_encode(['success' => false, 'error' => $rate['reason']]);
        exit();
    }

    $safety = ChatSecurity::analyzeOutgoingMessage($db, $sender_id, 'direct', $receiver_id, $content);
    if (!$safety['allowed']) {
        echo json_encode(['success' => false, 'error' => $safety['reason']]);
        exit();
    }

    $media_url = null;
    $media_type = null;
    if (isset($_FILES['media']) && ($_FILES['media']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Falha no upload do anexo.']);
            exit();
        }

        $stored = ChatSecurity::storeChatMedia($_FILES['media'], $sender_id);
        if (!$stored['ok']) {
            echo json_encode(['success' => false, 'error' => $stored['error']]);
            exit();
        }

        $media_url = $stored['path'];
        $media_type = $stored['type'];
    }

    $returning = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' RETURNING message_id' : '';
    $query = "INSERT INTO messages (sender_id, receiver_id, content, media_url, media_type, sent_at, is_read)
              VALUES (:sid, :rid, :content, :murl, :mtype, CURRENT_TIMESTAMP, false)" . $returning;
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':sid' => $sender_id,
        ':rid' => $receiver_id,
        ':content' => ChatSecurity::protectContent($content),
        ':murl' => $media_url,
        ':mtype' => $media_type
    ]);
    $message_id = $returning ? $stmt->fetchColumn() : $db->lastInsertId();

    $sender_query = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $sender_query->execute([$sender_id]);
    $sender_name = $sender_query->fetchColumn() ?: 'Membro KALIYE';

    $notif_body = $content !== '' ? mb_substr($content, 0, 80) : '[Ficheiro enviado]';
    try {
        $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link)
                                   VALUES (?, ?, ?, ?, 'message', ?)");
        $notif_stmt->execute([
            $receiver_id,
            $sender_id,
            'Mensagem de ' . $sender_name,
            $notif_body,
            'paginas/social/messages.php?start=' . $sender_id
        ]);
    } catch (Throwable $e) {}

    echo json_encode([
        'success' => true,
        'message_id' => $message_id,
        'media_url' => $media_url,
        'type' => $media_type
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Falha no motor de chat.']);
}
?>
