<?php
ob_start();
// servicos/mentorship/book_mentorship_slot.php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
error_reporting(0);
ini_set('display_errors', 0);

header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id'])) {
    ob_clean();
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$slot_id = $_POST['slot_id'] ?? null;

if (!$slot_id) {
    echo json_encode(['success' => false, 'error' => 'Missing slot ID']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Check Slot
    $query = "SELECT mentor_id, title, status, participant_id FROM mentorship_slots WHERE slot_id = ? FOR UPDATE";
    $stmt = $db->prepare($query);
    $stmt->execute([$slot_id]);
    $slot = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$slot) {
        throw new Exception('Horário não encontrado.');
    }

    $mentor_id = $slot['mentor_id'];
    $slot_title = $slot['title'] ?: 'Sessão de Mentoria';

    if ((int)$mentor_id === (int)$user_id) {
        throw new Exception('Nao pode reservar o seu proprio horario.');
    }

    if ($slot['status'] !== 'available' || !empty($slot['participant_id'])) {
        throw new Exception('Este horario ja nao esta disponivel.');
    }

    // Book
    $update = "UPDATE mentorship_slots SET participant_id = ?, status = 'booked' WHERE slot_id = ?";
    $stmt = $db->prepare($update);
    $stmt->execute([$user_id, $slot_id]);

    // Notification for Mentor
    $notif_query = "INSERT INTO notifications (user_id, sender_id, title, content, type, link) 
                    VALUES (?, ?, 'Nova Reserva de Mentoria', ?, 'mentorship_booking', 'paginas/mentoria/mentorship.php?view=mentor&tab=scheduler')";
    $notif_stmt = $db->prepare($notif_query);
    $notif_content = ($_SESSION['user_name'] ?? 'Um estudante') . " reservou o horário: " . $slot_title . ". Por favor, confirme a sessão.";
    $notif_stmt->execute([$mentor_id, $user_id, $notif_content]);

    $db->commit();
    ob_clean();
    echo json_encode(['success' => true, 'message' => 'Reserva solicitada! O mentor será notificado.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    ob_clean();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>

