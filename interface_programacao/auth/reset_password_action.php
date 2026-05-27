<?php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../autenticacao/entrar.php');
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
$token = (string)($_POST['token'] ?? '');
$password = (string)($_POST['password'] ?? '');
$confirm = (string)($_POST['confirm_password'] ?? '');

if (!$email || $token === '') {
    header('Location: ../../autenticacao/entrar.php');
    exit;
}

if (strlen($password) < 8 || $password !== $confirm) {
    header('Location: ../../autenticacao/redefinir_senha.php?email=' . urlencode($email) . '&token=' . urlencode($token) . '&error=mismatch');
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        SELECT u.user_id, o.code_hash, o.expires_at
        FROM users u
        JOIN otp_codes o ON o.user_id = u.user_id
        WHERE u.email = ? AND o.purpose = 'password_reset'
        ORDER BY o.expires_at DESC
        LIMIT 1
    ");
    $stmt->execute([$email]);
    $reset = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$reset || strtotime($reset['expires_at']) < time() || !password_verify($token, $reset['code_hash'])) {
        header('Location: ../../autenticacao/redefinir_senha.php?email=' . urlencode($email) . '&token=' . urlencode($token) . '&error=invalid_token');
        exit;
    }

    $db->beginTransaction();
    $db->prepare('UPDATE users SET password_hash = ?, updated_at = NOW() WHERE user_id = ?')
        ->execute([password_hash($password, PASSWORD_BCRYPT), $reset['user_id']]);
    $db->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'password_reset'")
        ->execute([$reset['user_id']]);
    $db->commit();

    header('Location: ../../autenticacao/entrar.php?success=password_changed');
} catch (Throwable $e) {
    if (isset($db) && $db instanceof PDO && $db->inTransaction()) {
        $db->rollBack();
    }
    header('Location: ../../autenticacao/redefinir_senha.php?email=' . urlencode((string)$email) . '&token=' . urlencode($token) . '&error=db_error');
}
exit;
