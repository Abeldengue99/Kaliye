<?php
// interface_programacao/auth/resend_otp.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
require_once __DIR__ . '/../../inclusoes/RateLimiter.php';

header('Content-Type: application/json');

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);

    if (empty($email)) {
        echo json_encode(['success' => false, 'message' => 'Email é obrigatório']);
        exit();
    }

    $database = new Database();
    /** @var PDO $db */
    $db = $database->getConnection();

    // ─── RATE LIMITING PROGRESSIVO (resposta JSON) ─────────────────────────────
    $real_ip  = RateLimiter::getRealIP();
    $rl_email = RateLimiter::check($db, 'resend_otp', $email, $real_ip);
    $rl_ip    = RateLimiter::check($db, 'resend_otp', $real_ip, $real_ip);

    if (!$rl_email['allowed'] || !$rl_ip['allowed']) {
        $blocked = !$rl_email['allowed'] ? $rl_email : $rl_ip;
        if ($blocked['phase'] === 'hard_lock') {
            http_response_code(429);
            echo json_encode(['success' => false, 'rate_limited' => true, 'hard_lock' => true,
                'message' => 'Operação bloqueada por segurança. Contacta a equipa Aksanti.']);
        } else {
            http_response_code(429);
            echo json_encode(['success' => false, 'rate_limited' => true, 'hard_lock' => false,
                'retry_after' => $blocked['retry_after'],
                'message' => 'Demasiados reenvios. Aguarda ' . ceil($blocked['retry_after'] / 60) . ' minuto(s).']);
        }
        exit();
    }
    // ─────────────────────────────────────────────────────────────────────────

    try {
        $stmt = $db->prepare("SELECT user_id, full_name, is_verified FROM users WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$user) {
            echo json_encode(['success' => false, 'message' => 'Usuário não encontrado']);
            exit();
        }

        if ($user['is_verified']) {
            echo json_encode(['success' => false, 'message' => 'Sua conta já está verificada.']);
            exit();
        }

        // 1. Gerar Novo OTP
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $otp_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // 2. Inativar códigos antigos e inserir novo
        $db->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'email_verify'")->execute([$user['user_id']]);
        
        $ins = $db->prepare("INSERT INTO otp_codes (user_id, code_hash, purpose, expires_at) VALUES (?, ?, 'email_verify', ?)");
        $ins->execute([$user['user_id'], $otp_hash, $otp_expires]);

        // 3. Enviar E-mail
        $mailer = new SimpleMailer();
        $mailed = $mailer->send($email, $user['full_name'], "Novo Código de Verificação - Aksanti", "Seu novo código é: <b style='font-size: 24px;'>$otp</b>");

        $_SESSION['debug_last_otp'] = $otp;

        echo json_encode(['success' => true, 'message' => 'Novo código enviado com sucesso!']);

    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => 'Erro interno no servidor']);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
}

