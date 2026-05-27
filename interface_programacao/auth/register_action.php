<?php
/**
 * interface_programacao/auth/register_action.php
 * Motor de registo de novos utilizadores na plataforma KALIYE.
 * Com Rate Limiting progressivo integrado.
 */

session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/RateLimiter.php';
require_once __DIR__ . '/../../inclusoes/SystemSettings.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    
    // Sanitização e Captura de Dados (Garantia de Integridade).
    $full_name      = filter_var($_POST['full_name'], FILTER_SANITIZE_STRING);
    $email          = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $phone          = filter_var($_POST['phone'], FILTER_SANITIZE_STRING);
    $id_number      = filter_var($_POST['id_number'], FILTER_SANITIZE_STRING);
    $birth_date     = $_POST['birth_date'];
    $password       = $_POST['password'];
    $user_type      = $_POST['user_type'];
    $is_peer_mentor = isset($_POST['is_peer_mentor']) ? 1 : 0;
    $accept_terms   = isset($_POST['accept_terms']) ? 1 : 0;
    $accept_privacy = isset($_POST['accept_privacy']) ? 1 : 0;

    // Verificação de Conformidade Legal.
    if (!$accept_terms || !$accept_privacy) {
        header("Location: ../../autenticacao/registar.php?error=terms_not_accepted");
        exit();
    }

    if (empty($full_name) || empty($email) || empty($password) || empty($user_type)) {
        header("Location: ../../autenticacao/registar.php?error=empty_fields");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    if (!systemSettingEnabled($db, 'allow_registrations', true)) {
        header("Location: ../../autenticacao/registar.php?error=registrations_closed");
        exit();
    }

    // ─── RATE LIMITING PROGRESSIVO ───────────────────────────────────────────
    $real_ip = RateLimiter::getRealIP();
    $rl = RateLimiter::check($db, 'register', $real_ip, $real_ip);

    if (!$rl['allowed']) {
        if ($rl['phase'] === 'hard_lock') {
            header("Location: ../../autenticacao/conta_bloqueada.php?action=register");
        } else {
            header("Location: ../../autenticacao/registar.php?error=rate_limited&retry=" . $rl['retry_after']);
        }
        exit();
    }
    // ────────────────────────────────────────────────────────────────────────

    try {
        // Garantia de Unicidade: O e-mail deve ser único no ecossistema.
        $check_email = $db->prepare("SELECT user_id FROM users WHERE email = ?");
        $check_email->execute([$email]);
        if ($check_email->rowCount() > 0) {
            header("Location: ../../autenticacao/registar.php?error=email_exists");
            exit();
        }

        // Cifra de Segurança da Password (BCRYPT).
        $password_hash = password_hash($password, PASSWORD_BCRYPT);
        
        // Inserção do Utilizador no motor PostgreSQL.
        $query = "INSERT INTO users (full_name, email, phone, id_number, birth_date, password_hash, user_type, is_peer_mentor, is_verified, created_at) 
                  VALUES (:name, :email, :phone, :id_num, :birth, :pass, :type, :peer, false, NOW()) RETURNING user_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':name'   => $full_name,
            ':email'  => $email,
            ':phone'  => $phone,
            ':id_num' => $id_number,
            ':birth'  => $birth_date,
            ':pass'   => $password_hash,
            ':type'   => $user_type,
            ':peer'   => $is_peer_mentor
        ]);

        $user_id = $stmt->fetchColumn();

        // Geração do Código de Verificação (OTP) para ativação de conta.
        $otp = sprintf("%06d", mt_rand(1, 999999));
        $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
        $otp_expires = date('Y-m-d H:i:s', strtotime('+30 minutes'));

        // Registo histórico do OTP para validação posterior.
        $ins_otp = $db->prepare("INSERT INTO otp_codes (user_id, code_hash, purpose, expires_at) VALUES (?, ?, 'email_verify', ?)");
        $ins_otp->execute([$user_id, $otp_hash, $otp_expires]);

        // Disparo do E-mail de Boas-vindas.
        if (file_exists(__DIR__ . '/../../inclusoes/SimpleMailer.php')) {
            require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
            $mailer = new SimpleMailer();
            $mailer->send($email, $full_name, "Ativação de Conta - Aksanti", "Bem-vindo! O seu código é: <b>$otp</b>");
        }

        // Estado Temporário de Registo (Pre-Auth).
        $_SESSION['pending_email_verification'] = [
            'user_id'   => $user_id,
            'email'     => $email,
            'user_name' => $full_name,
            'user_type' => $user_type
        ];

        header("Location: ../../autenticacao/verificar_email.php?email=" . urlencode($email));
        exit();

    } catch (PDOException $e) {
        error_log("Erro no Registro Aksanti: " . $e->getMessage());
        header("Location: ../../autenticacao/registar.php?error=db_error");
        exit();
    }
} else {
    header("Location: ../../autenticacao/registar.php");
    exit();
}
?>
