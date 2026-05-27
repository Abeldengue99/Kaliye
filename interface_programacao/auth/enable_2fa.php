<?php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/GoogleAuthenticator.php';

header('Content-Type: application/json; charset=utf-8');

if (empty($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sessao expirada.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(64)");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE");

    $ga = new GoogleAuthenticator();
    $secret = $ga->createSecret();
    $_SESSION['temp_2fa_secret'] = $secret;

    $name = ($_SESSION['user_name'] ?? 'KALIYE') . ':' . ($_SESSION['user_id'] ?? '');
    echo json_encode([
        'success' => true,
        'secret' => $secret,
        'qr_code_url' => $ga->getQRCodeGoogleUrl($name, $secret, 'KALIYE')
    ]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Nao foi possivel iniciar o 2FA.']);
}
