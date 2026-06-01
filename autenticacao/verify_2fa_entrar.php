<?php
session_start();
if (empty($_SESSION['2fa_pending_user_id'])) {
    header('Location: entrar.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Verificar 2FA | KALIYE</title>
    <link rel="stylesheet" href="../recursos/css/style.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="auth-bg" style="min-height:100vh;display:flex;align-items:center;justify-content:center;padding:1rem;">
    <div class="login-card glass glow-card" style="max-width:420px;width:100%;">
        <div class="text-center" style="margin-bottom:1.5rem;">
            <img src="../recursos/images/marca/YALIYE.png" alt="KALIYE" style="width:145px;border-radius:10px;">
            <h2 class="text-gradient" style="font-size:1.5rem;margin-top:1rem;">Codigo de segurança</h2>
            <p style="color:var(--text-secondary);font-size:.9rem;">Digite o codigo de 6 digitos do seu autenticador.</p>
        </div>
        <form id="twoFactorForm">
            <input type="text" name="code" maxlength="6" inputmode="numeric" pattern="[0-9]{6}" required
                   style="width:100%;padding:1rem;text-align:center;letter-spacing:6px;font-size:1.35rem;border-radius:12px;background:rgba(255,255,255,.04);border:1px solid rgba(255,255,255,.1);color:#fff;">
            <button type="submit" class="btn-primary" style="width:100%;margin-top:1rem;padding:1rem;border-radius:12px;">Entrar</button>
        </form>
    </div>
    <script>
    document.getElementById('twoFactorForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        const res = await fetch('../interface_programacao/auth/verify_2fa.php', { method: 'POST', body: new FormData(this) });
        const data = await res.json();
        if (data.success) {
            window.location.href = data.redirect || '../index.php';
            return;
        }
        Swal.fire({ icon: 'error', title: 'Codigo invalido', text: data.message || 'Tente novamente.', background: '#111827', color: '#fff' });
    });
    </script>
</body>
</html>

