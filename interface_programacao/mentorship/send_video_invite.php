<?php
// servicos/mentorship/send_video_invite.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$sender_id = $_SESSION['user_id'];
$receiver_id = $_POST['receiver_id'] ?? null;
$room = $_POST['room'] ?? '';

if (!$receiver_id || !$room) {
    echo json_encode(['success' => false, 'error' => 'Dados da chamada incompletos']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Buscar nome do remetente
    $stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $stmt->execute([$sender_id]);
    $sender_name = $stmt->fetchColumn();

    $content = "🎥 **Convite para Videochamada**\n$sender_name está convidando você para uma mentoria por vídeo.\n<a href=\"meeting.php?room=$room\" style=\"color: var(--accent-orange); font-weight: bold;\">CLIQUE AQUI PARA ENTRAR</a>";

    // Enviar mensagem de sistema com o link
    $query = "INSERT INTO messages (sender_id, receiver_id, content, sent_at, is_replyable) 
              VALUES (:sid, :rid, :content, NOW(), 0)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        ':sid' => $sender_id,
        ':rid' => $receiver_id,
        ':content' => ChatSecurity::protectContent($content)
    ]);

    // Notificação persistente
    $notif_query = "INSERT INTO notifications (user_id, sender_id, title, content, type, link) 
                    VALUES (?, ?, 'Convite de Vídeo', ?, 'video_invite', ?)";
    $notif_stmt = $db->prepare($notif_query);
    $notif_stmt->execute([$receiver_id, $sender_id, "$sender_name iniciou uma chamada.", "paginas/mentoria/meeting.php?room=$room"]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

