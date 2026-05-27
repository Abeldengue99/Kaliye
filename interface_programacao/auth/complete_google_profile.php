<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/GoogleOAuth.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessao expirada.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit();
}

requireValidCSRFTokenJson();

try {
    $db = (new Database())->getConnection();
    $google = new GoogleOAuth($db);
    $google->completeGoogleProfile((int)$_SESSION['user_id'], $_POST);

    echo json_encode([
        'success' => true,
        'message' => 'Perfil atualizado. Agora conclua a verificacao documental.',
        'redirect' => '../../paginas/social/profile.php?tab=kyc'
    ]);
} catch (InvalidArgumentException $e) {
    http_response_code(422);
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Throwable $e) {
    error_log('complete_google_profile error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro ao completar perfil. Tente novamente.']);
}
