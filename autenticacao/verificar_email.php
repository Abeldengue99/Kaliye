<?php
/**
 * verificar_email.php - Página de Verificação de E-mail (OTP)
 * KALIYE 
 * 
 * Exibe campos para inserir o código OTP de 6 dígitos enviado por email.
 * Processa a verificação contra a tabela otp_codes.
 */
session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/auth_check.php';

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$email = $_GET['email'] ?? $_SESSION['pending_email_verification']['email'] ?? '';
$error = '';
$success = '';

// --- Processar verificação do OTP ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['otp'])) {
    $submitted_otp = trim($_POST['otp']);
    $verify_email = $_POST['email'] ?? $email;

    if (empty($submitted_otp) || strlen($submitted_otp) !== 6) {
        $error = 'Por favor, insira o código completo de 6 dígitos.';
    } else {
        try {
            // Buscar o utilizador
            $user_stmt = $db->prepare("SELECT user_id, full_name, user_type, mentorship_status, verification_status FROM users WHERE email = ?");
            $user_stmt->execute([$verify_email]);
            $user = $user_stmt->fetch(PDO::FETCH_ASSOC);

            if (!$user) {
                $error = 'Utilizador não encontrado. Tente registar-se novamente.';
            } else {
                // Buscar OTP válido (não expirado)  usa SELECT * para compatibilidade
                $otp_stmt = $db->prepare("SELECT code_hash, expires_at FROM otp_codes WHERE user_id = ? AND purpose = 'email_verify' ORDER BY expires_at DESC LIMIT 1");
                $otp_stmt->execute([$user['user_id']]);
                $otp_record = $otp_stmt->fetch(PDO::FETCH_ASSOC);

                if (!$otp_record) {
                    $error = 'Nenhum código de verificação encontrado. Solicite um novo código.';
                } elseif (strtotime($otp_record['expires_at']) < time()) {
                    $error = 'O código expirou. Solicite um novo código.';
                } elseif (!password_verify($submitted_otp, $otp_record['code_hash'])) {
                    $error = 'Código de verificação incorreto. Verifique e tente novamente.';
                } else {
                    // ? OTP Correto  Verificar a conta
                    $db->prepare("UPDATE users SET is_verified = true WHERE user_id = ?")->execute([$user['user_id']]);
                    
                    // Limpar OTPs usados
                    $db->prepare("DELETE FROM otp_codes WHERE user_id = ? AND purpose = 'email_verify'")->execute([$user['user_id']]);
                    
                    // Criar sessão completa
                    $_SESSION['user_id'] = $user['user_id'];
                    $_SESSION['user_name'] = $user['full_name'];
                    $_SESSION['user_type'] = $user['user_type'];
                    $_SESSION['mentor_status'] = $user['mentorship_status'] ?? 'unsubmitted';
                    $_SESSION['mentorship_status'] = $user['mentorship_status'] ?? 'unsubmitted';
                    $_SESSION['verification_status'] = $user['verification_status'] ?? 'unsubmitted';
                    $_SESSION['email_verified'] = true;
                    $_SESSION['is_email_verified'] = true;
                    $_SESSION['is_verified'] = hasVerifiedIdentity($user);
                    unset($_SESSION['pending_email_verification']);
                    unset($_SESSION['debug_last_otp']);

                    // Redirecionar para o dashboard
                    if ($user['user_type'] === 'admin') {
                        header("Location: ../administracao/index.php");
                    } elseif ($user['user_type'] === 'investor') {
                        header("Location: ../paginas/plataforma/investor_dashboard.php");
                    } else {
                        header("Location: ../index.php?success=registered");
                    }
                    exit();
                }
            }
        } catch (PDOException $e) {
            error_log("Erro Verificação OTP: " . $e->getMessage() . " | SQL State: " . $e->getCode());
            $error = 'Erro interno do servidor. Detalhe: ' . $e->getMessage();
        }
    }
}

// Obter nome para exibir
$display_name = $_SESSION['pending_email_verification']['user_name'] ?? '';
$masked_email = '';
if ($email) {
    $parts = explode('@', $email);
    if (count($parts) === 2) {
        $name = $parts[0];
        $masked_name = substr($name, 0, 2) . str_repeat('', max(0, strlen($name) - 4)) . substr($name, -2);
        $masked_email = $masked_name . '@' . $parts[1];
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar E-mail | KALIYE</title>
    <meta name="description" content="Insira o código de verificação enviado para o seu e-mail para ativar a sua conta KALIYE.">

    <!-- Favicon -->
    <link rel="icon" type="image/png" sizes="32x32" href="../recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../recursos/images/marca/apple-touch-icon-k.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#f7941d">

    <!-- Fontes -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    
    <!-- SweetAlert2 -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --cor-fundo-principal: #070d1a;
            --cor-fundo-cartao: #0f172a;
            --cor-destaque-laranja: #f7941d;
            --cor-destaque-dourado: #fbbf24;
            --cor-destaque-azul: #3b82f6;
            --cor-texto-titulo: #ffffff;
            --cor-texto-paragrafo: #94a3b8;
            --cor-texto-discreto: #64748b;
            --cor-vidro: rgba(255,255,255,0.04);
            --cor-bordas-vidro: rgba(255,255,255,0.08);
            --cor-input-fundo: rgba(255,255,255,0.05);
            --cor-input-borda: rgba(255,255,255,0.1);
            --brilho-laranja: rgba(247,148,29,0.25);
            --cor-sucesso: #10b981;
            --cor-erro: #ef4444;
        }

        *, *::before, *::after { margin: 0; padding: 0; box-sizing: border-box; }
        html { height: 100%; }
        body {
            font-family: 'Inter', sans-serif;
            background: var(--cor-fundo-principal);
            color: var(--cor-texto-titulo);
            min-height: 100vh;
            overflow-x: hidden;
            display: flex;
            justify-content: center;
            align-items: center;
        }

        /* ===== FUNDO ANIMADO ===== */
        .bg-effects {
            position: fixed; inset: 0; z-index: 0; pointer-events: none;
        }
        .bg-effects::before {
            content: '';
            position: absolute; inset: 0;
            background-image: radial-gradient(rgba(255,255,255,0.06) 1px, transparent 1px);
            background-size: 35px 35px;
            mask-image: radial-gradient(ellipse at center, black 30%, transparent 75%);
        }
        .orb {
            position: absolute;
            border-radius: 50%;
            filter: blur(100px);
            animation: float 8s ease-in-out infinite;
        }
        .orb-1 {
            width: 400px; height: 400px;
            background: rgba(247, 148, 29, 0.08);
            top: -10%; left: -5%;
        }
        .orb-2 {
            width: 350px; height: 350px;
            background: rgba(59, 130, 246, 0.06);
            bottom: -10%; right: -5%;
            animation-delay: -4s;
        }
        @keyframes float {
            0%, 100% { transform: translateY(0) scale(1); }
            50% { transform: translateY(-20px) scale(1.05); }
        }

        /* ===== CARTÃO PRINCIPAL ===== */
        .verify-card {
            position: relative; z-index: 1;
            width: 100%; max-width: 400px;
            padding: 2.5rem;
            background: var(--cor-fundo-cartao);
            border: 1px solid var(--cor-bordas-vidro);
            border-radius: 28px;
            box-shadow: 0 30px 80px rgba(0,0,0,0.5);
            margin: 1rem;
        }

        /* ===== LOGO ===== */
        .logo-auth {
            display: flex; align-items: center; gap: 0.8rem;
            margin-bottom: 2rem; text-decoration: none;
            transition: 0.3s;
        }
        .logo-auth:hover { transform: scale(1.02); }
        .logo-icon {
            width: 145px; height: auto; border-radius: 10px;
            background: transparent;
            box-shadow: 0 4px 15px rgba(0,0,0,0.3);
            display: flex; align-items: center; justify-content: center;
            overflow: hidden; flex-shrink: 0;
        }
        .logo-icon img { width: 100%; height: auto; display: block; border-radius: 10px; }
        .logo-text { display: flex; flex-direction: column; line-height: 1; }
        .logo-text span:first-child {
            font-family: 'Outfit', sans-serif;
            font-weight: 800; font-size: 1.2rem; color: white;
        }
        .logo-text span:last-child {
            font-size: 0.7rem; color: var(--cor-texto-discreto);
            font-weight: 500; margin-top: 2px;
        }

        /* ===== ÍCONE DE VERIFICAÇÃO ===== */
        .verify-icon-wrap {
            width: 90px; height: 90px;
            background: linear-gradient(135deg, rgba(247,148,29,0.1), rgba(251,191,36,0.05));
            border: 2px solid rgba(247,148,29,0.2);
            border-radius: 28px;
            display: flex; align-items: center; justify-content: center;
            margin: 0 auto 1.8rem;
            animation: pulse-glow 3s ease-in-out infinite;
        }
        .verify-icon-wrap i {
            font-size: 2.2rem;
            background: linear-gradient(135deg, var(--cor-destaque-laranja), var(--cor-destaque-dourado));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
        }
        @keyframes pulse-glow {
            0%, 100% { box-shadow: 0 0 0 0 rgba(247,148,29,0.1); }
            50% { box-shadow: 0 0 25px 5px rgba(247,148,29,0.08); }
        }

        /* ===== TEXTOS ===== */
        .verify-title {
            font-family: 'Outfit', sans-serif;
            font-weight: 800; font-size: 1.8rem;
            text-align: center; margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
        }
        .verify-subtitle {
            text-align: center; font-size: 0.9rem;
            color: var(--cor-texto-paragrafo);
            line-height: 1.6; margin-bottom: 2.5rem;
        }
        .verify-subtitle strong {
            color: var(--cor-destaque-laranja);
            font-weight: 700;
        }

        /* ===== CAMPOS OTP ===== */
        .otp-container {
            display: flex;
            gap: 12px;
            justify-content: center;
            margin-bottom: 2rem;
        }
        .otp-input {
            width: 54px; height: 64px;
            text-align: center;
            font-size: 1.6rem;
            font-weight: 800;
            font-family: 'Outfit', sans-serif;
            background: var(--cor-input-fundo);
            border: 2px solid var(--cor-input-borda);
            border-radius: 16px;
            color: white;
            outline: none;
            transition: all 0.3s ease;
            caret-color: var(--cor-destaque-laranja);
        }
        .otp-input:focus {
            border-color: var(--cor-destaque-laranja);
            background: rgba(247,148,29,0.05);
            box-shadow: 0 0 20px rgba(247,148,29,0.1);
            transform: translateY(-2px);
        }
        .otp-input.filled {
            border-color: rgba(247,148,29,0.4);
            background: rgba(247,148,29,0.05);
        }
        .otp-input.error-state {
            border-color: var(--cor-erro);
            background: rgba(239,68,68,0.05);
            animation: shake 0.5s ease;
        }
        @keyframes shake {
            0%, 100% { transform: translateX(0); }
            20% { transform: translateX(-5px); }
            40% { transform: translateX(5px); }
            60% { transform: translateX(-3px); }
            80% { transform: translateX(3px); }
        }

        /* ===== BOTÃO PRINCIPAL ===== */
        .btn-verify {
            width: 100%;
            height: 60px;
            background: linear-gradient(135deg, var(--cor-destaque-laranja), var(--cor-destaque-dourado));
            border: none;
            border-radius: 18px;
            color: black;
            font-weight: 900;
            font-size: 1rem;
            cursor: pointer;
            display: flex; align-items: center; justify-content: center; gap: 10px;
            transition: all 0.3s ease;
            box-shadow: 0 10px 30px rgba(247,148,29,0.2);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .btn-verify:hover {
            transform: translateY(-3px);
            box-shadow: 0 15px 40px rgba(247,148,29,0.3);
        }
        .btn-verify:disabled {
            opacity: 0.5;
            cursor: not-allowed;
            transform: none;
        }

        /* ===== REENVIAR CÓDIGO ===== */
        .resend-section {
            text-align: center;
            margin-top: 2rem;
            padding-top: 1.5rem;
            border-top: 1px solid var(--cor-bordas-vidro);
        }
        .resend-text {
            font-size: 0.85rem;
            color: var(--cor-texto-discreto);
            margin-bottom: 0.8rem;
        }
        .btn-resend {
            background: none;
            border: 1px solid var(--cor-bordas-vidro);
            color: var(--cor-destaque-laranja);
            padding: 0.7rem 1.5rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s ease;
        }
        .btn-resend:hover {
            background: rgba(247,148,29,0.08);
            border-color: rgba(247,148,29,0.3);
        }
        .btn-resend:disabled {
            opacity: 0.4;
            cursor: not-allowed;
        }

        /* ===== MENSAGENS DE ERRO / SUCESSO ===== */
        .msg-box {
            padding: 1rem;
            border-radius: 14px;
            font-size: 0.85rem;
            font-weight: 600;
            margin-bottom: 1.5rem;
            display: flex; align-items: center; gap: 10px;
        }
        .msg-error {
            background: rgba(239,68,68,0.08);
            border: 1px solid rgba(239,68,68,0.2);
            color: #fca5a5;
        }
        .msg-success {
            background: rgba(16,185,129,0.08);
            border: 1px solid rgba(16,185,129,0.2);
            color: #6ee7b7;
        }

        /* ===== TEMPORIZADOR ===== */
        .timer-badge {
            display: inline-flex; align-items: center; gap: 6px;
            padding: 0.5rem 1rem;
            background: rgba(59,130,246,0.08);
            border: 1px solid rgba(59,130,246,0.15);
            border-radius: 10px;
            color: #60a5fa;
            font-size: 0.8rem;
            font-weight: 700;
            margin-top: 0.8rem;
        }

        /* ===== INFORMATIVO DE SEGURANÇA ===== */
        .security-note {
            display: flex; gap: 10px; align-items: flex-start;
            margin-top: 2rem;
            padding: 1rem;
            background: rgba(59,130,246,0.04);
            border: 1px solid rgba(59,130,246,0.1);
            border-radius: 14px;
        }
        .security-note i { color: var(--cor-destaque-azul); margin-top: 2px; }
        .security-note p {
            font-size: 0.75rem;
            color: var(--cor-texto-discreto);
            line-height: 1.5;
        }

        /* ===== DEBUG HELPER (only in dev) ===== */
        .debug-otp {
            margin-top: 1rem;
            padding: 0.8rem;
            background: rgba(251,191,36,0.05);
            border: 1px dashed rgba(251,191,36,0.3);
            border-radius: 10px;
            text-align: center;
            font-size: 0.75rem;
            color: var(--cor-destaque-dourado);
        }

        /* ===== RESPONSIVO ===== */
        @media (max-width: 520px) {
            .verify-card { padding: 2rem 1.5rem; margin: 0.5rem; border-radius: 24px; }
            .otp-input { width: 46px; height: 56px; font-size: 1.3rem; border-radius: 12px; }
            .otp-container { gap: 8px; }
            .verify-title { font-size: 1.4rem; }
        }
    </style>
</head>
<body>

<!-- Efeitos de fundo -->
<div class="bg-effects">
    <div class="orb orb-1"></div>
    <div class="orb orb-2"></div>
</div>

<!-- Cartão de Verificação -->
<div class="verify-card">

    <!-- Sem Logo -->

    <!-- Ícone Central -->
    <div class="verify-icon-wrap">
        <i class="fas fa-envelope-open-text"></i>
    </div>

    <!-- Título -->
    <h1 class="verify-title">Verificação de E-mail</h1>
    <p class="verify-subtitle">
        Enviámos um código de 6 dígitos para<br>
        <strong><?php echo htmlspecialchars($masked_email ?: $email); ?></strong>
    </p>

    <!-- Mensagens de erro -->
    <?php if ($error): ?>
        <div class="msg-box msg-error">
            <i class="fas fa-exclamation-circle"></i>
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <!-- Formulário OTP -->
    <form id="otpForm" method="POST" action="">
        <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
        <input type="hidden" name="otp" id="otpHidden" value="">

        <div class="otp-container" id="otpContainer">
            <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" autocomplete="one-time-code" data-index="0">
            <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="1">
            <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="2">
            <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="3">
            <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="4">
            <input type="text" class="otp-input" maxlength="1" inputmode="numeric" pattern="[0-9]" data-index="5">
        </div>

        <button type="submit" class="btn-verify" id="btnVerify" disabled>
            <i class="fas fa-shield-alt"></i>
            VERIFICAR CONTA
        </button>
    </form>

    <!-- Reenviar Código -->
    <div class="resend-section">
        <p class="resend-text">Não recebeu o código?</p>
        <button class="btn-resend" id="btnResend" onclick="resendOTP()">
            <i class="fas fa-redo"></i> Reenviar Código
        </button>
        <div class="timer-badge" id="timerBadge" style="display: none;">
            <i class="fas fa-clock"></i>
            <span id="timerText">Aguarde 60s</span>
        </div>
    </div>

    <!-- Nota de segurança -->
    <div class="security-note">
        <i class="fas fa-lock"></i>
        <p>O código expira em 30 minutos. Se não encontrar o e-mail, verifique a sua pasta de spam ou lixo eletrónico.</p>
    </div>

    <?php
    // Mostrar OTP para debug em ambiente local (REMOVER EM PRODUÇÃO)
    if (isset($_SESSION['debug_last_otp']) && in_array($_SERVER['HTTP_HOST'], ['localhost', '127.0.0.1', 'localhost:8080'])):
    ?>
    <div class="debug-otp">
        <i class="fas fa-bug"></i> <strong>DEBUG (apenas dev):</strong> OTP = <strong><?php echo $_SESSION['debug_last_otp']; ?></strong>
    </div>
    <?php endif; ?>
</div>

<script>
// ===== LÓGICA DOS CAMPOS OTP =====
const inputs = document.querySelectorAll('.otp-input');
const hiddenInput = document.getElementById('otpHidden');
const btnVerify = document.getElementById('btnVerify');

// Focar no primeiro campo ao carregar
window.addEventListener('DOMContentLoaded', () => inputs[0].focus());

inputs.forEach((input, index) => {
    // Ao digitar
    input.addEventListener('input', (e) => {
        const val = e.target.value;
        
        // Aceitar apenas números
        if (!/^\d$/.test(val)) {
            e.target.value = '';
            return;
        }

        e.target.classList.add('filled');
        e.target.classList.remove('error-state');

        // Avançar para o próximo campo
        if (val && index < inputs.length - 1) {
            inputs[index + 1].focus();
        }

        updateOTP();
    });

    // Tratar Backspace
    input.addEventListener('keydown', (e) => {
        if (e.key === 'Backspace') {
            if (!input.value && index > 0) {
                inputs[index - 1].focus();
                inputs[index - 1].value = '';
                inputs[index - 1].classList.remove('filled');
            } else {
                input.classList.remove('filled');
            }
            updateOTP();
        }
    });

    // Tratar Paste (colar código completo)
    input.addEventListener('paste', (e) => {
        e.preventDefault();
        const paste = (e.clipboardData || window.clipboardData).getData('text').replace(/\D/g, '');
        
        if (paste.length === 6) {
            inputs.forEach((inp, i) => {
                inp.value = paste[i] || '';
                if (paste[i]) inp.classList.add('filled');
            });
            inputs[5].focus();
            updateOTP();
        }
    });
});

function updateOTP() {
    let code = '';
    inputs.forEach(inp => code += inp.value);
    hiddenInput.value = code;
    btnVerify.disabled = code.length !== 6;
}

// ===== REENVIAR CÓDIGO =====
function resendOTP() {
    const btn = document.getElementById('btnResend');
    const timer = document.getElementById('timerBadge');
    const timerText = document.getElementById('timerText');
    
    btn.disabled = true;
    btn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';

    const formData = new FormData();
    formData.append('email', '<?php echo htmlspecialchars($email); ?>');

    fetch('../interface_programacao/auth/resend_otp.php', {
        method: 'POST',
        body: formData
    })
    .then(res => res.json())
    .then(data => {
        if (data.success) {
            Swal.fire({
                icon: 'success',
                title: 'Código Reenviado!',
                text: 'Verifique o seu e-mail para o novo código.',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#f7941d',
                timer: 3000,
                showConfirmButton: false
            });

            // Iniciar countdown
            btn.style.display = 'none';
            timer.style.display = 'inline-flex';
            let seconds = 60;
            
            const interval = setInterval(() => {
                seconds--;
                timerText.innerText = `Aguarde ${seconds}s`;
                
                if (seconds <= 0) {
                    clearInterval(interval);
                    timer.style.display = 'none';
                    btn.style.display = 'inline-flex';
                    btn.disabled = false;
                    btn.innerHTML = '<i class="fas fa-redo"></i> Reenviar Código';
                }
            }, 1000);
        } else {
            Swal.fire({
                icon: 'error',
                title: 'Erro',
                text: data.message || 'Não foi possível reenviar o código.',
                background: '#0f172a',
                color: '#fff',
                confirmButtonColor: '#f7941d'
            });
            btn.disabled = false;
            btn.innerHTML = '<i class="fas fa-redo"></i> Reenviar Código';
        }
    })
    .catch(() => {
        Swal.fire({
            icon: 'error',
            title: 'Erro de Conexão',
            text: 'Não foi possível conectar ao servidor.',
            background: '#0f172a',
            color: '#fff'
        });
        btn.disabled = false;
        btn.innerHTML = '<i class="fas fa-redo"></i> Reenviar Código';
    });
}

// ===== VALIDAÇÃO NO SUBMIT =====
document.getElementById('otpForm').addEventListener('submit', function(e) {
    const code = hiddenInput.value;
    if (code.length !== 6) {
        e.preventDefault();
        inputs.forEach(inp => inp.classList.add('error-state'));
        return;
    }
    
    // Desabilitar botão para evitar duplo clique
    btnVerify.disabled = true;
    btnVerify.innerHTML = '<i class="fas fa-spinner fa-spin"></i> VERIFICANDO...';
});
</script>

</body>
</html>
