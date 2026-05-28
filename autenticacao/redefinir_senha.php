<?php
// auth/reset_password.php
session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';

$token = $_GET['token'] ?? '';
$email = $_GET['email'] ?? '';

if (empty($token) || empty($email)) {
    header("Location: entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
$site_name_stmt = $db->query("SELECT setting_value FROM settings WHERE setting_key = 'site_name'");
$site_name = $site_name_stmt->fetchColumn() ?: 'KALIYE';
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Redefinir Senha | KALIYE</title>
    <!-- Favicon Oficial KALIYE -->
    <link rel="icon" type="image/png" sizes="32x32" href="../recursos/images/marca/favicon-k-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../recursos/images/marca/favicon-k-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../recursos/images/marca/apple-touch-icon-k.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#f7941d">

    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="../recursos/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <style>
        /* CSS adicional para deixar o card menor e elegante */
        .login-card-compact {
            max-width: 400px; /* Reduz a largura maxima */
            padding: 2.5rem; /* Ajusta o espacamento interno */
            margin: auto;
        }
    </style>
</head>
<body class="auth-bg" style="min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 0.5rem;">
    <div class="login-card glass glow-card login-card-compact" style="position: relative; z-index: 10;">
        <div class="text-center" style="margin-bottom: 2rem;">
            <h2 class="text-gradient" style="font-size: 1.5rem; margin-bottom: 0.5rem;">Redefinir Senha</h2>
            <p style="color: var(--text-secondary); font-size: 0.85rem;">Escolha uma nova senha segura para a sua conta.</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.8rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.2);">
                <?php 
                    if($_GET['error'] == 'invalid_token') echo "Token inválido ou expirado.";
                    else if($_GET['error'] == 'mismatch') echo "As senhas não coincidem.";
                    else echo "Ocorreu um erro.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../interface_programacao/auth/reset_password_action.php" method="POST">
            <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">
            <input type="hidden" name="email" value="<?php echo htmlspecialchars($email); ?>">
            
            <div class="input-group">
                <label for="password" style="color: var(--text-primary); font-weight: 600; font-size: 0.9rem;">Nova Senha</label>
                <input type="password" id="password" name="password" placeholder="••••••••" style="padding: 0.8rem 1rem; font-size: 0.95rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);" required minlength="8">
            </div>

            <div class="input-group mt-3">
                <label for="confirm_password" style="color: var(--text-primary); font-weight: 600; font-size: 0.9rem;">Confirmar Nova Senha</label>
                <input type="password" id="confirm_password" name="confirm_password" placeholder="••••••••" style="padding: 0.8rem 1rem; font-size: 0.95rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);" required minlength="8">
            </div>
            
            <button type="submit" class="btn-primary" style="padding: 1rem; font-size: 1rem; border-radius: 12px; margin-top: 1.5rem; width: 100%;">
                Alterar Senha <i class="fas fa-key" style="margin-left: 0.5rem;"></i>
            </button>
        </form>
    </div>
</body>
</html>