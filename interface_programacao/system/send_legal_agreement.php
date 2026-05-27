<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Nao autorizado.']);
    exit;
}

$user_id = (int)($_POST['user_id'] ?? 0);
$project_id = trim($_POST['project_id'] ?? '');
$agreement_type = trim($_POST['agreement_type'] ?? '');
$contract_terms = trim($_POST['contract_terms'] ?? '');
$admin_id = (int)($_SESSION['user_id'] ?? 0);

if ($user_id <= 0 || $agreement_type === '') {
    echo json_encode(['success' => false, 'message' => 'Destinatario e tipo de acordo sao obrigatorios.']);
    exit;
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    $db->exec("CREATE TABLE IF NOT EXISTS legal_agreements (
        agreement_id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        project_id INT NULL,
        admin_id INT NULL,
        agreement_type VARCHAR(80) NOT NULL,
        contract_terms TEXT,
        admin_signed_file VARCHAR(255),
        user_signed_file VARCHAR(255),
        status VARCHAR(30) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signed_at TIMESTAMP NULL
    )");

    $check = $db->prepare("SELECT user_id FROM users WHERE user_id = ?");
    $check->execute([$user_id]);
    if (!$check->fetch()) {
        throw new Exception('Utilizador nao encontrado.');
    }

    $admin_signed_file = null;
    if (!empty($_FILES['admin_signed_file']['name'])) {
        $stored = Security::storeUploadedFile(
            $_FILES['admin_signed_file'],
            __DIR__ . '/../../carregamentos/legal',
            'carregamentos/legal',
            [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ],
            12 * 1024 * 1024,
            'agreement_admin_' . $user_id
        );

        if (!$stored['ok']) {
            throw new Exception($stored['error']);
        }

        $admin_signed_file = $stored['path'];
    }

    $pid = $project_id !== '' ? (int)$project_id : null;
    $insert = $db->prepare("INSERT INTO legal_agreements
        (user_id, project_id, admin_id, agreement_type, contract_terms, admin_signed_file, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, 'pending', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)");
    $insert->execute([$user_id, $pid, $admin_id ?: null, $agreement_type, $contract_terms, $admin_signed_file]);

    try {
        $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at)
            VALUES (?, ?, 'Contrato pendente de assinatura', 'Tem um novo documento legal para rever e assinar.', 'system', 'paginas/legal/legal.php', CURRENT_TIMESTAMP)");
        $notif->execute([$user_id, $admin_id ?: null]);
    } catch (Exception $ignored) {}

    try {
        $log = $db->prepare("INSERT INTO audit_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP)");
        $log->execute([$admin_id ?: null, 'send_legal_agreement', 'Contrato enviado ao utilizador #' . $user_id]);
    } catch (Exception $ignored) {}

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Contrato enviado com sucesso.']);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
