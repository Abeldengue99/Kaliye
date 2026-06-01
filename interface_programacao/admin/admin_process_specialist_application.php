<?php
/**
 * interface_programacao/admin/admin_process_specialist_application.php
 * API para processar candidaturas de especialistas
 */
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

// Check admin permission
require_once '../../inclusoes/auth_check.php';
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
    exit;
}

$application_id = $_POST['application_id'] ?? null;
$new_status = $_POST['status'] ?? null;

if (!$application_id || !$new_status) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    // Validar status
    $valid_statuses = ['submitted', 'under_review', 'shortlisted', 'approved', 'rejected'];
    if (!in_array($new_status, $valid_statuses)) {
        echo json_encode(['success' => false, 'message' => 'Estado inválido.']);
        exit;
    }

    // Get application details
    $app_stmt = $db->prepare("SELECT * FROM project_specialist_applications WHERE application_id = ?");
    $app_stmt->execute([$application_id]);
    $application = $app_stmt->fetch();

    if (!$application) {
        echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada.']);
        exit;
    }

    // Update application
    $update_stmt = $db->prepare("
        UPDATE project_specialist_applications 
        SET status = ?, reviewed_by = ?, reviewed_at = CURRENT_TIMESTAMP 
        WHERE application_id = ?
    ");
    $update_stmt->execute([$new_status, $_SESSION['user_id'], $application_id]);

    // Get specialist and project info for notification
    $spec_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $spec_stmt->execute([$application['specialist_id']]);
    $specialist = $spec_stmt->fetch();

    $proj_stmt = $db->prepare("SELECT title FROM projects WHERE project_id = ?");
    $proj_stmt->execute([$application['project_id']]);
    $project = $proj_stmt->fetch();

    // Notify specialist
    $status_messages = [
        'under_review' => 'A sua candidatura está a ser analisada',
        'shortlisted' => 'Parabéns! Foi pré-selecionado',
        'approved' => 'Parabéns! Sua candidatura foi aprovada',
        'rejected' => 'Sua candidatura foi rejeitada'
    ];

    $notif_stmt = $db->prepare("
        INSERT INTO notifications (user_id, sender_id, title, content, type) 
        VALUES (?, ?, ?, ?, 'application_update')
    ");
    $notif_stmt->execute([
        $application['specialist_id'],
        $_SESSION['user_id'],
        'Atualização de Candidatura',
        $status_messages[$new_status] . " - " . htmlspecialchars($project['title'])
    ]);

    echo json_encode([
        'success' => true,
        'message' => 'Candidatura atualizada com sucesso.',
        'data' => ['application_id' => $application_id, 'new_status' => $new_status]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
    exit;
}
?>
