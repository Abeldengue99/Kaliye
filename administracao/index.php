<?php
/**
 * admin/index.php - Admin Overview
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = './';
$base_url = '../';
require_once '../configuracoes/base_dados.php';
require_once '../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../autenticacao/entrar.php");
    exit();
}

// Redirect if dashboard is not allowed
if (!hasPermission('dashboard')) {
    $target = getFirstAllowedAdminPage();
    if ($target !== 'index.php') {
        header("Location: " . $target);
        exit();
    }
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Painel Admin - KALIYE</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">

    <link rel="stylesheet" href="../recursos/css/style.css">
    <link rel="stylesheet" href="../recursos/css/mobile-elite.css?v=<?= filemtime(__DIR__ . '/../recursos/css/mobile-elite.css') ?>">
    <link rel="stylesheet" href="../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    
    <script>
        // Ponto Central de Variáveis JS para a Área Administrativa
        window.BASE_URL = (function() {
            const link = document.createElement('a');
            link.href = '<?= $base_url ?>';
            return link.href.endsWith('/') ? link.href : link.href + '/';
        })();
    </script>
    
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="admin-dashboard-layout">
    
    <!-- Top Navigation Admin -->
    <?php include 'barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Painel de Controlo</h1>
                <p>Ecossistema KALIYE</p>
            </div>
            <!-- Quick Actions -->
            <div class="admin-quick-actions">
                <a href="users/manage_users.php" class="btn-admin btn-admin-blue">
                    <i class="fas fa-user-plus"></i> Novo Utilizador
                </a>
                <a href="users/kyc_requests.php" class="btn-admin btn-admin-green">
                    <i class="fas fa-shield-check"></i> Validar KYC
                </a>
                <a href="system/settings.php" class="btn-admin btn-admin-primary">
                    <i class="fas fa-sliders"></i> Configurações
                </a>
            </div>
        </header>

        <?php if (isAdmin()): ?>
            <!-- Stats Grid -->
            <?php include '../inclusoes/components/admin/admin_stats_cards.php'; ?>

            <!-- Charts Section -->
            <?php include '../inclusoes/components/admin/admin_dashboard_charts.php'; ?>

            <!-- Community Highlights -->
            <?php include '../inclusoes/components/admin/admin_community_tables.php'; ?>
        <?php else: ?>
            <div class="admin-card-glass" style="padding: 2.5rem; border-left: 5px solid var(--accent-orange);">
                <h2>Gestão Restrita</h2>
                <p style="color: var(--text-secondary);">O seu acesso é limitado às ferramentas específicas atribuídas à sua função.</p>
                <div style="margin-top: 1.5rem; display: flex; gap: 1rem;">
                    <button onclick="window.location.href='users/manage_users.php'" class="btn-primary" style="width: auto;">Ver Utilizadores</button>
                    <button onclick="window.location.href='moderation/support.php'" class="btn-secondary" style="width: auto;">Abrir Suporte</button>
                </div>
            </div>
        <?php endif; ?>
    </main>

    <!-- Scripts -->
    <?php if (isAdmin()): ?>
        <?php include '../inclusoes/components/admin/admin_dashboard_scripts.php'; ?>
    <?php endif; ?>

</body>
</html>
