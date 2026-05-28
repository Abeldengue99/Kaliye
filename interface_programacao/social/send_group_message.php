<?php
// servicos/social/send_group_message.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$group_id = $_POST['group_id'] ?? null;
$content = $_POST['content'] ?? '';

if (!$group_id) {
    echo json_encode(['success' => false, 'error' => 'Grupo não identificado']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
ChatSecurity::touchPresence($db, (int)$sender_id);

try {
    $member_stmt = $db->prepare("SELECT 1 FROM chat_group_members WHERE group_id = ? AND user_id = ? LIMIT 1");
    $member_stmt->execute([(int)$group_id, (int)$sender_id]);
    if (!$member_stmt->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'Sem permissão para enviar nesta sala.']);
        exit();
    }

    $content = ChatSecurity::normalizeText($content);
    if ($content === '' && empty($_FILES['media'])) {
        echo json_encode(['success' => false, 'error' => 'Mensagem vazia.']);
        exit();
    }

    $rate = ChatSecurity::checkRateLimit($db, (int)$sender_id, 'group');
    if (!$rate['allowed']) {
        echo json_encode(['success' => false, 'error' => $rate['reason']]);
        exit();
    }

    $safety = ChatSecurity::analyzeOutgoingMessage($db, (int)$sender_id, 'group', (int)$group_id, $content);
    if (!$safety['allowed']) {
        echo json_encode(['success' => false, 'error' => $safety['reason']]);
        exit();
    }

    $media_url = null;
    $media_type = null;

    // Handle Media Upload for Group Messages
    if (isset($_FILES['media']) && ($_FILES['media']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_NO_FILE) {
        if ($_FILES['media']['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Falha no upload do anexo.']);
            exit();
        }
        $stored = ChatSecurity::storeChatMedia($_FILES['media'], (int)$sender_id);
        if (!$stored['ok']) {
            echo json_encode(['success' => false, 'error' => $stored['error']]);
            exit();
        }
        $media_url = $stored['path'];
        $media_type = $stored['type'];
    }

    $query = "INSERT INTO group_messages (group_id, sender_id, content, media_url, media_type, sent_at) 
              VALUES (:gid, :sid, :content, :murl, :mtype, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':gid' => $group_id,
        ':sid' => $sender_id,
        ':content' => ChatSecurity::protectContent($content),
        ':murl' => $media_url,
        ':mtype' => $media_type
    ]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

