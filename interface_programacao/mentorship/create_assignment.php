<?php
/**
 * interface_programacao/mentorship/create_assignment.php
 * Cria uma atribuição de tarefa ao mentor com 7 dias de expiração
 */
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilizador não autenticado']);
    exit;
}

// Only admins can create assignments
if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Apenas administradores podem criar atribuições.']);
    exit;
}

$mentor_id = isset($_POST['mentor_id']) ? (int)$_POST['mentor_id'] : 0;
$student_id = isset($_POST['student_id']) ? (int)$_POST['student_id'] : 0;
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : null;
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if (!$mentor_id || !$title) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Ensure expires_at column exists
    $db->exec("ALTER TABLE mentor_assignments ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");

    // Insert assignment with 7-day expiration
    $stmt = $db->prepare("
        INSERT INTO mentor_assignments
            (mentor_id, student_id, project_id, title, description, status, expires_at)
        VALUES
            (?, ?, ?, ?, ?, 'pending', NOW() + (7 * INTERVAL '1 day'))
        RETURNING assignment_id
    ");
    
    $stmt->execute([
        $mentor_id,
        $student_id ?: null,
        $project_id ?: null,
        mb_substr($title, 0, 255),
        $description
    ]);
    
    $assignment_id = (int)$stmt->fetchColumn();

    // Notify mentor about assignment
    try {
        $admin_name = $_SESSION['user_name'] ?? 'Admin';
        $notif_stmt = $db->prepare("
            INSERT INTO notifications (user_id, sender_id, title, content, type, link)
            VALUES (?, ?, 'Nova Atribuição', ?, 'assignment', 'paginas/mentoria/mentorship.php?view=mentor&tab=assignments')
        ");
        $notif_content = $admin_name . ' atribuiu-te uma nova tarefa: "' . $title . '"';
        $notif_stmt->execute([$mentor_id, $_SESSION['user_id'], $notif_content]);
    } catch (Exception $e) {
        // Notification failed, but assignment was created
        error_log("Notification error: " . $e->getMessage());
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Atribuição criada com sucesso', 'assignment_id' => $assignment_id]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("Create Assignment Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao criar atribuição: ' . $e->getMessage()]);
}
?>
