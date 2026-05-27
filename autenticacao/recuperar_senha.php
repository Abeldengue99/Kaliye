<?php
// forgot_password.php
session_start();
require_once __DIR__ . '/../configuracoes/base_dados.php';
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
    <title>Recuperar Senha | KALIYE</title>
    <!-- Favicon Oficial KALIYE â€” completo e local -->
    <link rel="icon" type="image/png" sizes="32x32" href="../recursos/images/marca/favicon-k-32x32.png">
    <link rel="icon" type="image/png" sizes="16x16" href="../recursos/images/marca/favicon-k-16x16.png">
    <link rel="apple-touch-icon" sizes="180x180" href="../recursos/images/marca/apple-touch-icon-k.png">
    <link rel="manifest" href="../manifest.json">
    <meta name="theme-color" content="#f7941d">

    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700&family=Outfit:wght@400;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="../recursos/css/style.css?v=<?php echo time(); ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
</head>
<body class="auth-bg" style="min-height: 100vh; overflow-x: hidden; display: flex; align-items: center; justify-content: center; padding: 0.5rem;">
    
    <!-- Animated Orbs removed -->
    
    <div class="login-card glass glow-card" data-aos="zoom-in" style="position: relative; z-index: 10;">
        
        <!-- Logo Section -->
        <div class="text-center" style="margin-bottom: 2rem;">
            <div style="display: flex; align-items: center; justify-content: center; margin-bottom: 1rem;">
                <img src="../recursos/images/marca/YALIYE.png" alt="KALIYE" style="width: 145px; height: auto; border-radius: 10px;">
            </div>
            <h2 class="text-gradient" style="font-size: 1.5rem; margin-bottom: 0.5rem;">Recuperar Senha</h2>
            <p style="color: var(--text-secondary); font-size: 0.85rem;">Insira o seu email para receber instruções de recuperação.</p>
        </div>

        <?php if(isset($_GET['error'])): ?>
            <div style="background: rgba(239, 68, 68, 0.1); color: #ef4444; padding: 0.8rem; border-radius: 8px; margin-bottom: 1.5rem; font-size: 0.85rem; text-align: center; border: 1px solid rgba(239, 68, 68, 0.2);">
                <?php 
                    if($_GET['error'] == 'email_not_found') echo "Email não encontrado.";
                    else if($_GET['error'] == 'db_error') echo "Erro no sistema.";
                    else echo "Ocorreu um erro.";
                ?>
            </div>
        <?php endif; ?>

        <form action="../interface_programacao/auth/forgot_password_action.php" method="POST">
            <div class="input-group">
                <label for="email" style="color: var(--text-primary); font-weight: 600; font-size: 0.9rem;">Endereço de Email</label>
                <input type="email" id="email" name="email" placeholder="nome@exemplo.com" style="padding: 0.8rem 1rem; font-size: 0.95rem; background: rgba(255,255,255,0.03); border: 1px solid rgba(255,255,255,0.1);" required>
            </div>
            
            <button type="submit" class="btn-primary pulse" style="padding: 1rem; font-size: 1rem; border-radius: 12px; margin-top: 1.5rem; width: 100%;">
                Enviar Link de Recuperação <i class="fas fa-paper-plane" style="margin-left: 0.5rem;"></i>
            </button>
        </form>

        <div class="text-center mt-4" style="margin-top: 1.5rem;">
            <a href="entrar.php" style="font-size: 0.9rem; color: var(--text-secondary); text-decoration: none;">
                <i class="fas fa-arrow-left"></i> Voltar para Login
            </a>
        </div>
    </div>

    <script src="https://unpkg.com/aos@next/dist/aos.js"></script>
    <script>
        AOS.init({ duration: 1000, once: true });
    </script>
</body>
</html>


