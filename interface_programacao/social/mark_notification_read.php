<?php
// servicos/social/mark_notification_read.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
requireValidCSRFTokenJson();
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = [];
}
$notif_id = $_POST['notification_id'] ?? ($input['notification_id'] ?? null);
$project_id = $_POST['project_id'] ?? ($input['project_id'] ?? null);

if (!$notif_id && !$project_id) {
    echo json_encode(['success' => false, 'error' => 'ID da notificação ausente']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    if ($project_id) {
        $query = "UPDATE notifications SET is_read = '1' WHERE user_id = :user_id AND reference_id = :project_id AND type = 'investment'";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
        $stmt->bindParam(':project_id', $project_id, PDO::PARAM_INT);
    } elseif ($notif_id === 'all') {
        // Usamos 'true' (string) que o PDO/PostgreSQL converte correctamente para Boolean ou 1
        $query = "UPDATE notifications SET is_read = '1' WHERE user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':user_id', $user_id);
    } else {
        $query = "UPDATE notifications SET is_read = '1' WHERE notification_id = :notif_id AND user_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':notif_id', $notif_id, PDO::PARAM_INT);
        $stmt->bindParam(':user_id', $user_id, PDO::PARAM_INT);
    }
    
    $stmt->execute();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao processar']);
}

