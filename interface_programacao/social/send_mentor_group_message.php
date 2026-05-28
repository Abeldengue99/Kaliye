<?php
/**
 * Secure multimodal API for mentor VIP groups.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Autenticacao pendente.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $sender_id = (int)$_SESSION['user_id'];
    ChatSecurity::touchPresence($db, $sender_id);

    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $message_type = $_POST['message_type'] ?? 'text';
    $message_type = in_array($message_type, ['text', 'audio', 'meeting'], true) ? $message_type : 'text';
    $message_content = ChatSecurity::normalizeText($_POST['message'] ?? $_POST['content'] ?? '');

    if ($group_id <= 0) {
        throw new Exception('Referencia invalida para a sala de mentoria.');
    }

    $rate = ChatSecurity::checkRateLimit($db, $sender_id, 'group');
    if (!$rate['allowed']) {
        throw new Exception($rate['reason']);
    }

    $access = $db->prepare("
        SELECT mg.mentor_id
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
    $access->execute([':gid' => $group_id, ':uid' => $sender_id]);
    $group_mentor_id = (int)$access->fetchColumn();
    if (!$group_mentor_id) {
        throw new Exception('Sem permissão para publicar nesta sala de mentoria.');
    }

    $file_url = null;

    if ($message_type === 'audio') {
        if (empty($_FILES['audio_file']) || $_FILES['audio_file']['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Nota de voz invalida.');
        }
        $stored = ChatSecurity::storeChatMedia($_FILES['audio_file'], $sender_id);
        if (!$stored['ok']) {
            throw new Exception($stored['error']);
        }
        $file_url = $stored['path'];
    }

    if ($message_type === 'meeting') {
        if ($sender_id !== $group_mentor_id) {
            throw new Exception('Apenas o mentor responsavel pode iniciar videochamadas nesta sala.');
        }

        $title = ChatSecurity::normalizeText($_POST['meeting_title'] ?? 'Sessão de Mentoria Privada');
        $scheduled_at = $_POST['scheduled_at'] ?? date('Y-m-d H:i:s');
        if (!preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $scheduled_at)) {
            $scheduled_at = date('Y-m-d H:i:s');
        }

        $room_name = 'KALIYE-Mentoria-' . bin2hex(random_bytes(12));
        $jitsi_link = 'https://meet.jit.si/' . $room_name;

        $meet_stmt = $db->prepare("INSERT INTO mentor_group_meetings (group_id, mentor_id, title, scheduled_at, meet_link) VALUES (?, ?, ?, ?, ?)");
        $meet_stmt->execute([$group_id, $sender_id, $title, $scheduled_at, $jitsi_link]);

        $message_content = json_encode([
            'title' => $title,
            'time' => $scheduled_at,
            'link' => $jitsi_link
        ]);
        $file_url = $jitsi_link;
    }

    if ($message_type === 'text' && $message_content === '') {
        throw new Exception('Corpo da mensagem em falta.');
    }

    if ($message_type === 'text') {
        $safety = ChatSecurity::analyzeOutgoingMessage($db, $sender_id, 'mentor_group', $group_id, $message_content);
        if (!$safety['allowed']) {
            throw new Exception($safety['reason']);
        }
        $message_content = ChatSecurity::protectContent($message_content);
    }

    $stmt = $db->prepare("INSERT INTO mentor_group_messages (group_id, sender_id, message, message_type, file_url) VALUES (?, ?, ?, ?, ?)");
    $stmt->execute([$group_id, $sender_id, $message_content, $message_type, $file_url]);

    echo json_encode([
        'success' => true,
        'message_id' => $db->lastInsertId(),
        'message_type' => $message_type,
        'content' => ChatSecurity::revealContent($message_content),
        'file_url' => $file_url,
        'timestamp' => date('H:i')
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
