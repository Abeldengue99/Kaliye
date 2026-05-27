<?php
session_start();

require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/GoogleOAuth.php';

try {
    $db = (new Database())->getConnection();
    $google = new GoogleOAuth($db);
    $mode = ($_GET['mode'] ?? 'login') === 'register' ? 'register' : 'login';

    header('Location: ' . $google->authorizationUrl($mode));
    exit();
} catch (Throwable $e) {
    error_log('Google OAuth start error: ' . $e->getMessage());
    header('Location: entrar.php?error=google_unavailable');
    exit();
}
