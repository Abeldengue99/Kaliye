<?php
ob_start(); // Buffer output to prevent any warnings from breaking JSON
// servicos/mentorship/confirm_mentorship_booking.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', 0);

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit;
}

$user_id = $_SESSION['user_id'];
$full_name = $_SESSION['user_name'] ?? 'Um Mentor';
$slot_id = $_POST['slot_id'] ?? null;

if (!$slot_id) {
    echo json_encode(['success' => false, 'error' => 'ID do horário ausente']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Validate slot belongs to this mentor
    $query = "SELECT ms.*, u.full_name as mentee_name, u.email as mentee_email 
              FROM mentorship_slots ms
              JOIN users u ON ms.participant_id = u.user_id
              WHERE ms.slot_id = ? AND ms.mentor_id = ? AND ms.status = 'booked' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$slot_id, $user_id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slot) {
        throw new Exception('Reserva não encontrada ou já confirmada.');
    }

    $mentee_id = $slot['participant_id'];
    $mentee_email = $slot['mentee_email'];
    $room_name = $slot['meeting_room'];
    $title = $slot['title'] ?: 'Sessão de Mentoria';

    // 2. Update Status to Confirmed
    $update = "UPDATE mentorship_slots SET status = 'confirmed' WHERE slot_id = ?";
    $stmt = $db->prepare($update);
    $stmt->execute([$slot_id]);

    // 3. Notify Student (System Notification)
    $notif_query = "INSERT INTO notifications (user_id, sender_id, title, content, type, link) 
                    VALUES (?, ?, 'Mentoria Confirmada!', ?, 'mentorship_confirmed', ?)";
    $notif_stmt = $db->prepare($notif_query);
    $meeting_link = "paginas/mentoria/meeting.php?room=" . $room_name;
    $notif_message = $full_name . " confirmou a mentoria: " . $title . ". Clique para acessar a sala.";
    $notif_stmt->execute([$mentee_id, $user_id, $notif_message, $meeting_link]);

    // 4. Send Message to Student (Optional but requested: "mensagem com o link")
    $msg_query = "INSERT INTO messages (sender_id, receiver_id, content, sent_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
    $msg_stmt = $db->prepare($msg_query);
    $msg_text = "Olá! Confirmei nossa mentoria formatada para " . $slot['start_time'] . ". Você pode acessar a sala aqui: " . $meeting_link;
    $msg_stmt->execute([$user_id, $mentee_id, ChatSecurity::protectContent($msg_text)]);

    // 5. Send Email
    try {
        require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
        $mailer = new SimpleMailer();
        $email_subject = "Mentoria Confirmada! - Aksanti";
        $email_body = "
            <div style='font-family: Arial, sans-serif; color: #333;'>
                <h2 style='color: #f7941d;'>Olá, {$slot['mentee_name']}!</h2>
                <p>Sua mentoria <strong>\"{$title}\"</strong> foi confirmada por <strong>{$full_name}</strong>.</p>
                <p><strong>Horário:</strong> " . date('d/m/Y H:i', strtotime($slot['start_time'])) . "</p>
                <p><strong>Como acessar:</strong> Você já pode entrar na sala através do link abaixo ou pelo seu painel na Aksanti.</p>
                <a href=\"https://aksanti.com/{$meeting_link}\" style='display: inline-block; padding: 10px 20px; background: #f7941d; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px;'>ACESSAR SALA DE REUNIÃO</a>
                <br><br>
                <p>Equipa Aksanti</p>
            </div>
        ";
        $mailer->send($mentee_email, $slot['mentee_name'], $email_subject, $email_body);
    } catch (Exception $e) {
        error_log("Failed to send mentorship confirmation email: " . $e->getMessage());
    }

    $db->commit();
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Reserva confirmada com sucesso!']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

