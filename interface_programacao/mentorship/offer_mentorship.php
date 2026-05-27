<?php
ob_start();
// servicos/mentorship/offer_mentorship.php
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

$mentor_id = $_SESSION['user_id'];
$full_name = $_SESSION['user_name'] ?? 'Um Mentor';
$student_id = $_POST['student_id'] ?? null;
$slot_id = $_POST['slot_id'] ?? null;
$custom_message = $_POST['message'] ?? '';

if (!$student_id || !$slot_id) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // 1. Verify slot ownership and availability
    $query = "SELECT * FROM mentorship_slots WHERE slot_id = ? AND mentor_id = ? AND status = 'available' LIMIT 1";
    $stmt = $db->prepare($query);
    $stmt->execute([$slot_id, $mentor_id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slot) {
        throw new Exception('Horário não disponível ou não pertence a você.');
    }

    // 2. Assign slot to student (booked status)
    $update = "UPDATE mentorship_slots SET participant_id = ?, status = 'booked' WHERE slot_id = ?";
    $stmt = $db->prepare($update);
    $stmt->execute([$student_id, $slot_id]);

    // 3. Notify Student
    $notif_query = "INSERT INTO notifications (user_id, sender_id, title, content, type, link) 
                    VALUES (?, ?, 'Proposta de Mentoria!', ?, 'mentorship_offer', 'paginas/mentoria/mentorship.php?view=mentee&tab=scheduler')";
    $notif_stmt = $db->prepare($notif_query);
    $notif_content = $full_name . " ofereceu mentoria para você. Por favor, aceite o convite.";
    $notif_stmt->execute([$student_id, $mentor_id, $notif_content]);

    // 4. Send Chat Message
    $msg_query = "INSERT INTO messages (sender_id, receiver_id, content, sent_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)";
    $msg_stmt = $db->prepare($msg_query);
    $msg_text = "Olá! Ofereci uma mentoria para você no horário " . $slot['start_time'] . ". " . $custom_message;
    $msg_stmt->execute([$mentor_id, $student_id, ChatSecurity::protectContent($msg_text)]);

    // 5. Send Email
    try {
        $stud_query = "SELECT full_name, email FROM users WHERE user_id = ?";
        $stud_stmt = $db->prepare($stud_query);
        $stud_stmt->execute([$student_id]);
        $student = $stud_stmt->fetch(PDO::FETCH_ASSOC);

        if ($student) {
            require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
            $mailer = new SimpleMailer();
            $email_subject = "Novo Convite de Mentoria - Aksanti";
            $email_body = "
                <div style='font-family: Arial, sans-serif; color: #333;'>
                    <h2 style='color: #f7941d;'>Olá, {$student['full_name']}!</h2>
                    <p>Você recebeu um convite de mentoria de <strong>{$full_name}</strong>.</p>
                    <p><strong>Horário proposto:</strong> " . date('d/m/Y H:i', strtotime($slot['start_time'])) . "</p>
                    <p>Acesse o seu painel de mentoria na Aksanti para aceitar este convite.</p>
                    <a href='https://aksanti.com/paginas/mentoria/mentorship.php' style='display: inline-block; padding: 10px 20px; background: #f7941d; color: white; text-decoration: none; border-radius: 5px; margin-top: 15px;'>VER PAINEL DE MENTORIA</a>
                    <br><br>
                    <p>Equipa Aksanti</p>
                </div>
            ";
            $mailer->send($student['email'], $student['full_name'], $email_subject, $email_body);
        }
    } catch (Exception $e) {
        error_log("Failed to send mentorship offer email: " . $e->getMessage());
    }

    $db->commit();
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Oferta enviada com sucesso!']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

