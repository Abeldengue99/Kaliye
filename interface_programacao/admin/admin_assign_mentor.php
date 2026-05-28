<?php
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('mentor_assignment')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

$project_id = (int)($_POST['project_id'] ?? 0);
$mentor_id = (int)($_POST['mentor_id'] ?? 0);
$contract_type = trim($_POST['contract_type'] ?? 'premium_invested');
$contract_terms = trim($_POST['contract_terms'] ?? '');
$admin_notes = trim($_POST['admin_notes'] ?? '');
$admin_id = (int)($_SESSION['user_id'] ?? 0);

if ($project_id <= 0 || $mentor_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Projeto ou mentor invalido.']);
    exit;
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_contracts (
        contract_id SERIAL PRIMARY KEY,
        project_id INT NOT NULL,
        mentor_id INT NOT NULL,
        admin_id INT NULL,
        contract_type VARCHAR(80) DEFAULT 'premium_invested',
        contract_terms TEXT,
        admin_notes TEXT,
        admin_signed_file VARCHAR(255),
        status VARCHAR(50) DEFAULT 'pending_mentor_acceptance',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $project_stmt = $db->prepare("SELECT project_id, title, owner_id FROM projects WHERE project_id = ?");
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$project) {
        throw new Exception('Projeto não encontrado.');
    }

    $mentor_stmt = $db->prepare("SELECT user_id, full_name FROM users WHERE user_id = ? AND (user_type = 'mentor' OR mentorship_status = 'approved')");
    $mentor_stmt->execute([$mentor_id]);
    $mentor = $mentor_stmt->fetch(PDO::FETCH_ASSOC);
    if (!$mentor) {
        throw new Exception('Mentor não encontrado ou não aprovado.');
    }

    $admin_signed_file = null;
    if (!empty($_FILES['admin_signed_file']['name']) && is_uploaded_file($_FILES['admin_signed_file']['tmp_name'])) {
        $allowed = ['pdf', 'jpg', 'jpeg', 'png'];
        $ext = strtolower(pathinfo($_FILES['admin_signed_file']['name'], PATHINFO_EXTENSION));
        if (!in_array($ext, $allowed, true)) {
            throw new Exception('Formato de contrato invalido.');
        }
        $dir = __DIR__ . '/../../carregamentos/legal';
        if (!is_dir($dir)) {
            mkdir($dir, 0775, true);
        }
        $filename = 'mentor_contract_' . $project_id . '_' . $mentor_id . '_' . time() . '.' . $ext;
        $target = $dir . '/' . $filename;
        if (!move_uploaded_file($_FILES['admin_signed_file']['tmp_name'], $target)) {
            throw new Exception('Não foi possível guardar o contrato.');
        }
        $admin_signed_file = 'carregamentos/legal/' . $filename;
    }

    $insert = $db->prepare("INSERT INTO mentorship_contracts
        (project_id, mentor_id, admin_id, contract_type, contract_terms, admin_notes, admin_signed_file, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'pending_mentor_acceptance', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $insert->execute([$project_id, $mentor_id, $admin_id ?: null, $contract_type, $contract_terms, $admin_notes, $admin_signed_file]);

    $update_project = $db->prepare("UPDATE projects
        SET assigned_mentor_id = ?, mentorship_status = 'mentor_assigned'
        WHERE project_id = ?");
    $update_project->execute([$mentor_id, $project_id]);

    try {
        $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at)
            VALUES (?, ?, ?, ?, 'system', ?, CURRENT_TIMESTAMP)");
        $notif->execute([
            $mentor_id,
            $admin_id ?: null,
            'Nova atribuicao de mentoria',
            'Foi atribuido ao projeto "' . $project['title'] . '". Consulte os termos na plataforma.',
            'paginas/mentoria/mentorship.php'
        ]);
    } catch (Exception $ignored) {}

    try {
        $log = $db->prepare("INSERT INTO audit_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $log->execute([$admin_id ?: null, 'assign_mentor', 'Mentor #' . $mentor_id . ' atribuido ao projeto #' . $project_id]);
    } catch (Exception $ignored) {}

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Mentor atribuido com sucesso.']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
