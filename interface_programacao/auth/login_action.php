<?php
/**
 * interface_programacao/auth/login_action.php
 * Script central de autenticação e gestão de sessões.
 * Com Rate Limiting progressivo integrado.
 */

session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/RateLimiter.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $email = filter_var($_POST['email'], FILTER_SANITIZE_EMAIL);
    $password = $_POST['password'];

    if (empty($email) || empty($password)) {
        header("Location: ../../autenticacao/entrar.php?error=empty_fields");
        exit();
    }

    $database = new Database();
    $db = $database->getConnection();

    // ─── RATE LIMITING PROGRESSIVO ───────────────────────────────────────────
    // Identificador: combinação de IP + email (bloqueia por IP E por email)
    $real_ip    = RateLimiter::getRealIP();
    $rl_by_ip   = RateLimiter::check($db, 'login', $real_ip, $real_ip);
    $rl_by_email = RateLimiter::check($db, 'login', $email, $real_ip);

    // Verificar bloqueio por IP
    if (!$rl_by_ip['allowed']) {
        if ($rl_by_ip['phase'] === 'hard_lock') {
            header("Location: ../../autenticacao/conta_bloqueada.php?action=login&reason=ip");
        } else {
            header("Location: ../../autenticacao/entrar.php?error=rate_limited&retry=" . $rl_by_ip['retry_after']);
        }
        exit();
    }

    // Verificar bloqueio por email
    if (!$rl_by_email['allowed']) {
        if ($rl_by_email['phase'] === 'hard_lock') {
            header("Location: ../../autenticacao/conta_bloqueada.php?action=login&reason=email");
        } else {
            header("Location: ../../autenticacao/entrar.php?error=rate_limited&retry=" . $rl_by_email['retry_after']);
        }
        exit();
    }

    // Aviso de aproximação ao limite (fase 1)
    if ($rl_by_ip['phase'] === 'warning' || $rl_by_email['phase'] === 'warning') {
        $remaining = min($rl_by_ip['remaining'], $rl_by_email['remaining']);
        $_SESSION['rate_limit_warning'] = "Atenção: apenas {$remaining} tentativa(s) restante(s) antes de ser bloqueado temporariamente.";
    }
    // ─────────────────────────────────────────────────────────────────────────

    try {
        // Busca do utilizador pelo e-mail único.
        $query = "SELECT user_id, full_name, password_hash, user_type, mentorship_status, verification_status, is_verified FROM users WHERE email = :email LIMIT 1";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':email', $email);
        $stmt->execute();

        if ($stmt->rowCount() > 0) {
            $row = $stmt->fetch();
            
            // Verificação segura da password usando o algoritmo BCRYPT.
            if (password_verify($password, $row['password_hash'])) {
                
                // Validação de e-mail confirmado (OTP Check).
                // NOTA: PostgreSQL retorna booleanos como string 't'/'f' — usar filter_var para normalizar.
                $check_ver_stmt = $db->prepare("SELECT is_verified FROM users WHERE user_id = ?");
                $check_ver_stmt->execute([$row['user_id']]);
                $is_verified_raw = $check_ver_stmt->fetchColumn();
                $is_verified = filter_var($is_verified_raw, FILTER_VALIDATE_BOOLEAN);

                if (!$is_verified) {
                    // Geração de código de verificação se a conta ainda não estiver ativa.
                    $otp = sprintf("%06d", mt_rand(1, 999999));
                    $otp_hash = password_hash($otp, PASSWORD_BCRYPT);
                    $otp_expires = date('Y-m-d H:i:s', strtotime('+15 minutes'));
                    
                    $ins = $db->prepare("INSERT INTO otp_codes (user_id, code_hash, purpose, expires_at) VALUES (?, ?, 'email_verify', ?)");
                    $ins->execute([$row['user_id'], $otp_hash, $otp_expires]);

                    // Envio de e-mail transacional de boas-vindas.
                    require_once __DIR__ . '/../../inclusoes/SimpleMailer.php';
                    $mailer = new SimpleMailer();
                    $mailer->send($email, $row['full_name'], "Verificação Necessária - Aksanti", "Seu código: <b>$otp</b>");

                    $_SESSION['pending_email_verification'] = [
                        'user_id' => $row['user_id'],
                        'email' => $email,
                        'user_name' => $row['full_name'],
                        'user_type' => $row['user_type']
                    ];
                    
                    header("Location: ../../autenticacao/verificar_email.php?email=" . urlencode($email));
                    exit();
                }

                // Verificação de Segundo Fator de Autenticação (2FA).
                $check_2fa_stmt = $db->prepare("SELECT two_factor_enabled FROM users WHERE user_id = ?");
                $check_2fa_stmt->execute([$row['user_id']]);
                $user_2fa = $check_2fa_stmt->fetchColumn();

                if ($user_2fa) {
                    $_SESSION['2fa_pending_user_id'] = $row['user_id'];
                    header("Location: ../../autenticacao/verify_2fa_entrar.php");
                    exit();
                }

                // INICIALIZAÇÃO DA SESSÃO PRINCIPAL (CORRETO: 'mentorship_status')
                Security::hardenAuthenticatedSession((int)$row['user_id']);

                $_SESSION['user_id'] = $row['user_id'];
                $_SESSION['user_name'] = $row['full_name'];
                $_SESSION['user_type'] = $row['user_type'];
                $_SESSION['mentorship_status'] = $row['mentorship_status'];
                $_SESSION['verification_status'] = $row['verification_status'] ?? 'unsubmitted';
                $_SESSION['email_verified'] = filter_var($row['is_verified'] ?? false, FILTER_VALIDATE_BOOLEAN);
                $_SESSION['is_email_verified'] = $_SESSION['email_verified'];
                $_SESSION['is_verified'] = ($row['verification_status'] === 'verified');
                $_SESSION['last_activity_at'] = time();

                // Recolha de telemetria e informações do dispositivo para segurança.
                require_once __DIR__ . '/../../inclusoes/DeviceDetector.php';
                $deviceInfo = DeviceDetector::getInfo();
                $geoInfo = DeviceDetector::getLocation($deviceInfo['ip']);
                $risk = Security::assessLoginRisk($db, (int)$row['user_id'], $deviceInfo, $geoInfo);
                $_SESSION['login_risk_score'] = $risk['score'];
                $_SESSION['login_risk_signals'] = $risk['signals'];

                // Atualização do status online e último IP no registro do utilizador.
                $update_user = $db->prepare("
                    UPDATE users 
                    SET last_login_ip = ?, last_device_type = ?, last_device_brand = ?, last_login_at = CURRENT_TIMESTAMP 
                    WHERE user_id = ?
                ");
                $update_user->execute([$deviceInfo['ip'], $deviceInfo['type'], $deviceInfo['brand'], $row['user_id']]);

                // Registro histórico da sessão (Logs de Transparência).
                $log_stmt = $db->prepare("
                    INSERT INTO login_logs (user_id, ip_address, device_type, device_brand, user_agent, country, city, region, ISP, latitude, longitude) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $log_stmt->execute([
                    $row['user_id'], $deviceInfo['ip'], $deviceInfo['type'], $deviceInfo['brand'], $deviceInfo['user_agent'],
                    $geoInfo['country'], $geoInfo['city'], $geoInfo['region'], $geoInfo['isp'], $geoInfo['lat'], $geoInfo['lon']
                ]);

                // Log de atividade geral do utilizador.
                $activity_stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, device_type, device_brand, details) VALUES (?, 'login', ?, ?, ?, 'Login bem-sucedido')");
                $activity_stmt->execute([$row['user_id'], $deviceInfo['ip'], $deviceInfo['type'], $deviceInfo['brand']]);

                if ($risk['score'] >= 60) {
                    Security::logActivity(
                        $db,
                        (int)$row['user_id'],
                        'login_risk_high',
                        'Score: ' . $risk['score'] . '; sinais: ' . implode(',', $risk['signals']),
                        'warning'
                    );
                }

                // Roteamento Final baseado no Nível de Acesso.
                if ($row['user_type'] == 'admin') {
                    header("Location: ../../administracao/index.php");
                } elseif ($row['user_type'] == 'investor') {
                    header("Location: ../../paginas/plataforma/investor_dashboard.php");
                } else {
                    header("Location: ../../index.php");
                }
                exit();
            } else {
                header("Location: ../../autenticacao/entrar.php?error=invalid_credentials");
                exit();
            }
        } else {
            header("Location: ../../autenticacao/entrar.php?error=invalid_credentials");
            exit();
        }
    } catch (PDOException $e) {
        header("Location: ../../autenticacao/entrar.php?error=db_error");
        exit();
    }
} else {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}
?>
