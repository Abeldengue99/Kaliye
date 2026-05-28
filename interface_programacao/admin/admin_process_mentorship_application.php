<?php
/**
 * Resposta administrativa a candidaturas de mentoria por projecto.
 */
header('Content-Type: application/json; charset=utf-8');
@session_start();

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/ProjectWorkflowSchema.php';

if (!isAdmin() || !hasPermission('mentor_assignment')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$application_id = (int)($_POST['application_id'] ?? 0);
$status = trim((string)($_POST['status'] ?? ''));
$admin_response = trim((string)($_POST['admin_response'] ?? ''));
$admin_id = (int)($_SESSION['user_id'] ?? 0);

if ($application_id <= 0 || !in_array($status, ['under_review', 'shortlisted', 'rejected'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parametros invalidos.']);
    exit;
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();
    ensureProjectMentorshipApplicationsSchema($db);

    $stmt = $db->prepare("
        SELECT pma.*, p.title as project_title, u.full_name as mentor_name
        FROM project_mentorship_applications pma
        JOIN projects p ON p.project_id = pma.project_id
        JOIN users u ON u.user_id = pma.mentor_id
        WHERE pma.application_id = ?
        FOR UPDATE
    ");
    $stmt->execute([$application_id]);
    $app = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$app) {
        throw new Exception('Candidatura não encontrada.');
    }

    if (in_array((string)$app['status'], ['approved', 'withdrawn'], true)) {
        throw new Exception('Esta candidatura ja não pode ser alterada neste fluxo.');
    }

    $upd = $db->prepare("UPDATE project_mentorship_applications
        SET status = ?, admin_response = ?, reviewed_by = ?, reviewed_at = NOW(), updated_at = NOW()
        WHERE application_id = ?");
    $upd->execute([$status, $admin_response, $admin_id ?: null, $application_id]);

    $status_labels = [
        'under_review' => 'em analise',
        'shortlisted' => 'em lista curta',
        'rejected' => 'rejeitada'
    ];

    $content = 'A sua candidatura para mentorar o projecto "' . $app['project_title'] . '" esta ' . ($status_labels[$status] ?? $status) . '.';
    if ($admin_response !== '') {
        $content .= ' Resposta KALIYE: ' . $admin_response;
    }

    $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link)
        VALUES (?, ?, ?, ?, 'mentorship', ?)");
    $notif->execute([
        (int)$app['mentor_id'],
        $admin_id ?: null,
        'Actualizacao da candidatura de mentoria',
        $content,
        'paginas/mentoria/mentorship.php'
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Candidatura actualizada com sucesso.']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>

