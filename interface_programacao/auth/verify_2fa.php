<?php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/GoogleAuthenticator.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

header('Content-Type: application/json; charset=utf-8');

$code = preg_replace('/\D+/', '', (string)($_POST['code'] ?? ''));
if (strlen($code) !== 6) {
    echo json_encode(['success' => false, 'message' => 'Codigo invalido.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_secret VARCHAR(64)");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS two_factor_enabled BOOLEAN DEFAULT FALSE");
    $ga = new GoogleAuthenticator();

    if (!empty($_SESSION['temp_2fa_secret']) && !empty($_SESSION['user_id'])) {
        $secret = $_SESSION['temp_2fa_secret'];
        if (!$ga->verifyCode($secret, $code, 1)) {
            echo json_encode(['success' => false, 'message' => 'Codigo invalido.']);
            exit;
        }

        $db->prepare('UPDATE users SET two_factor_secret = ?, two_factor_enabled = true WHERE user_id = ?')
            ->execute([$secret, $_SESSION['user_id']]);
        unset($_SESSION['temp_2fa_secret']);
        echo json_encode(['success' => true, 'message' => '2FA ativado com sucesso.']);
        exit;
    }

    if (!empty($_SESSION['2fa_pending_user_id'])) {
        $stmt = $db->prepare('SELECT user_id, full_name, user_type, mentorship_status, verification_status, is_verified, two_factor_secret FROM users WHERE user_id = ?');
        $stmt->execute([$_SESSION['2fa_pending_user_id']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user || !$ga->verifyCode((string)$user['two_factor_secret'], $code, 1)) {
            echo json_encode(['success' => false, 'message' => 'Codigo invalido.']);
            exit;
        }

        Security::hardenAuthenticatedSession((int)$user['user_id']);
        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'];
        $_SESSION['mentorship_status'] = $user['mentorship_status'] ?? 'unsubmitted';
        $_SESSION['verification_status'] = $user['verification_status'] ?? 'unsubmitted';
        $_SESSION['email_verified'] = in_array($user['is_verified'] ?? false, [true, 1, '1', 't'], true);
        $_SESSION['is_email_verified'] = $_SESSION['email_verified'];
        $_SESSION['is_verified'] = (($user['verification_status'] ?? '') === 'verified');
        unset($_SESSION['2fa_pending_user_id']);

        echo json_encode(['success' => true, 'redirect' => $user['user_type'] === 'admin' ? '../../administracao/index.php' : '../../index.php']);
        exit;
    }

    if (!empty($_SESSION['user_id'])) {
        $stmt = $db->prepare('SELECT two_factor_secret FROM users WHERE user_id = ? AND two_factor_enabled = true');
        $stmt->execute([$_SESSION['user_id']]);
        $secret = (string)$stmt->fetchColumn();

        if ($secret === '' || !$ga->verifyCode($secret, $code, 1)) {
            echo json_encode(['success' => false, 'message' => 'Codigo invalido.']);
            exit;
        }

        $db->prepare('UPDATE users SET two_factor_enabled = false, two_factor_secret = NULL WHERE user_id = ?')
            ->execute([$_SESSION['user_id']]);
        echo json_encode(['success' => true, 'message' => '2FA desativado com sucesso.']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Falha ao validar 2FA.']);
}

