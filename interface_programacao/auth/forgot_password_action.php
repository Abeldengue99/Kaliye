<?php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../../autenticacao/recuperar_senha.php');
    exit;
}

$email = filter_var($_POST['email'] ?? '', FILTER_VALIDATE_EMAIL);
if (!$email) {
    header('Location: ../../autenticacao/recuperar_senha.php?error=invalid_email');
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare('SELECT user_id, full_name FROM users WHERE email = ? LIMIT 1');
    $stmt->execute([$email]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if ($user) {
        $token = bin2hex(random_bytes(32));
        $hash = password_hash($token, PASSWORD_BCRYPT);
        $expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        $db->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'password_reset'")->execute([$user['user_id']]);
        $db->prepare("INSERT INTO otp_codes (user_id, code_hash, purpose, expires_at) VALUES (?, ?, 'password_reset', ?)")
            ->execute([$user['user_id'], $hash, $expires]);

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $link = $scheme . '://' . $host . '/autenticacao/redefinir_senha.php?email=' . urlencode($email) . '&token=' . urlencode($token);

        $body = "<p>Recebemos um pedido para redefinir a sua senha.</p><p><a href=\"$link\">Clique aqui para escolher uma nova senha</a></p><p>O link expira em 30 minutos.</p>";
        (new SimpleMailer())->send($email, $user['full_name'], 'Redefinicao de Senha - KALIYE', $body);
    }

    header('Location: ../../autenticacao/entrar.php?success=password_reset_sent');
} catch (Throwable $e) {
    header('Location: ../../autenticacao/recuperar_senha.php?error=db_error');
}
exit;
