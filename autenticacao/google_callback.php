<?php
session_start();

require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/GoogleOAuth.php';

if (isset($_GET['error'])) {
    header('Location: entrar.php?error=google_cancelled');
    exit();
}

$code = (string)($_GET['code'] ?? '');
$state = (string)($_GET['state'] ?? '');

if ($code === '' || $state === '') {
    header('Location: entrar.php?error=google_invalid');
    exit();
}

try {
    $db = (new Database())->getConnection();
    $google = new GoogleOAuth($db);
    $user = $google->handleCallback($code, $state);

    if ($google->profileNeedsCompletion($user)) {
        header('Location: ../paginas/conta/completar_perfil.php');
        exit();
    }

    if (($user['user_type'] ?? '') === 'admin') {
        header('Location: ../administracao/index.php');
    } elseif (($user['user_type'] ?? '') === 'investor') {
        header('Location: ../paginas/plataforma/investor_dashboard.php');
    } else {
        header('Location: ../index.php');
    }
    exit();
} catch (Throwable $e) {
    error_log('Google OAuth callback error: ' . $e->getMessage());
    header('Location: entrar.php?error=google_failed');
    exit();
}
