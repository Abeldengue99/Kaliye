<?php
/**
 * upload_investment_proof.php - Handles proof upload for project investments.
 */
session_start();
header('Content-Type: application/json');

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/Security.php';

$payments_config = require __DIR__ . '/../../configuracoes/pagamentos.php';
if (!isset($payments_config['payments_enabled']) || $payments_config['payments_enabled'] === false) {
    echo json_encode(['success' => false, 'message' => 'Funcionalidade de investimentos desativada nesta versao.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

if (($_SESSION['user_type'] ?? '') !== 'investor') {
    echo json_encode(['success' => false, 'message' => 'Apenas investidores podem enviar comprovativos.']);
    exit();
}
requireValidCSRFTokenJson();

$db = (new Database())->getConnection();
$user_id = (int)$_SESSION['user_id'];
$investment_id = isset($_POST['investment_id']) ? (int)$_POST['investment_id'] : 0;
$proof_file = $_FILES['proof_doc'] ?? ($_FILES['proof_file'] ?? null);

if ($investment_id <= 0 || !$proof_file) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

try {
    $check = $db->prepare("SELECT investment_id FROM project_investments WHERE investment_id = ? AND investor_id = ?");
    $check->execute([$investment_id, $user_id]);
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Investimento não encontrado ou acesso negado.']);
        exit();
    }

    $stored = Security::storeUploadedFile(
        $proof_file,
        __DIR__ . '/../../carregamentos/investments',
        'carregamentos/investments',
        [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
        ],
        12 * 1024 * 1024,
        'proof_' . $investment_id
    );

    if (!$stored['ok']) {
        echo json_encode(['success' => false, 'message' => $stored['error']]);
        exit();
    }

    $update = $db->prepare("UPDATE project_investments SET proof_document_path = ?, status = 'pending_approval' WHERE investment_id = ?");
    $update->execute([$stored['path'], $investment_id]);

    echo json_encode(['success' => true, 'message' => 'Comprovativo enviado com sucesso. Aguarde a validação.']);
} catch (PDOException $e) {
    error_log('upload_investment_proof error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao guardar comprovativo. Tente novamente.']);
}
