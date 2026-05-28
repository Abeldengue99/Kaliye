<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit;
}

$agreement_id = (int)($_POST['agreement_id'] ?? 0);
if ($agreement_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Contrato invalido.']);
    exit;
}

$db = (new Database())->getConnection();

try {
    $stmt = $db->prepare("SELECT * FROM legal_agreements WHERE agreement_id = ? AND user_id = ?");
    $stmt->execute([$agreement_id, (int)$_SESSION['user_id']]);
    $agreement = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$agreement) {
        throw new Exception('Contrato não encontrado.');
    }

    $user_signed_file = null;
    if (!empty($_FILES['user_signed_file']['name'])) {
        $stored = Security::storeUploadedFile(
            $_FILES['user_signed_file'],
            __DIR__ . '/../../carregamentos/legal',
            'carregamentos/legal',
            [
                'application/pdf' => 'pdf',
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
            ],
            12 * 1024 * 1024,
            'agreement_user_' . $agreement_id
        );

        if (!$stored['ok']) {
            throw new Exception($stored['error']);
        }

        $user_signed_file = $stored['path'];
    }

    $update = $db->prepare("UPDATE legal_agreements
        SET status = 'signed', user_signed_file = COALESCE(?, user_signed_file), signed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP
        WHERE agreement_id = ? AND user_id = ?");
    $update->execute([$user_signed_file, $agreement_id, (int)$_SESSION['user_id']]);

    echo json_encode(['success' => true, 'message' => 'Contrato assinado com sucesso.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
