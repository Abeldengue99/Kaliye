<?php
/**
 * admin/manage_users.php - User Management
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

if (!hasPermission('users')) {
    header("Location: index.php"); 
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$role_filter = $_GET['role'] ?? null;
$where = $role_filter ? "WHERE user_type = " . $db->quote($role_filter) : "";

$users = $db->query("SELECT * FROM users $where ORDER BY created_at DESC")->fetchAll();

// Instituições desativadas (Fase 1)
$institutions = [];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Utilizadores - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">

    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <a href="../index.php" style="color: var(--aksanti-orange); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Painel
                </a>
                <h1><?= $role_filter == 'admin' ? 'Equipa Administrativa' : 'Gestão de Utilizadores' ?></h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Total de <?= count($users) ?> contas no ecossistema.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <?php if ($role_filter != 'admin'): ?>
                <div style="display: flex; gap: 0.5rem; background: rgba(255,255,255,0.03); padding: 0.4rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                    <a href="manage_users.php" class="btn-admin <?= !$role_filter ? 'btn-admin-primary' : '' ?>" style="background: <?= !$role_filter ? '' : 'transparent' ?>; color: <?= !$role_filter ? '' : '#94a3b8' ?>;">Todos</a>
                    <a href="manage_users.php?role=mentor" class="btn-admin <?= $role_filter == 'mentor' ? 'btn-admin-primary' : '' ?>" style="background: <?= $role_filter == 'mentor' ? '' : 'transparent' ?>; color: <?= $role_filter == 'mentor' ? '' : '#94a3b8' ?>;">Mentores</a>
                    <a href="manage_users.php?role=investor" class="btn-admin <?= $role_filter == 'investor' ? 'btn-admin-primary' : '' ?>" style="background: <?= $role_filter == 'investor' ? '' : 'transparent' ?>; color: <?= $role_filter == 'investor' ? '' : '#94a3b8' ?>;">Investidores</a>
                </div>
                <?php endif; ?>
                <div style="display: flex; gap: 0.75rem;">
                    <a href="export_users.php?format=view&role=<?= $role_filter ?>" target="_blank" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.85rem;">
                        <i class="fas fa-file-pdf"></i> PDF
                    </a>
                    <a href="export_users.php?format=csv&role=<?= $role_filter ?>" class="btn-admin btn-admin-primary" style="padding: 0.75rem 1.5rem; text-decoration: none;">
                        <i class="fas fa-file-csv"></i> CSV
                    </a>
                </div>
                <button onclick="inviteAdmin()" class="btn-admin btn-admin-primary">
                    <i class="fas fa-plus-circle"></i> NOVO ADMINISTRADOR
                </button>
            </div>
        </header>

        <!-- User Table Component -->
        <div class="admin-card-premium">
            <?php include '../../inclusoes/components/admin/user_table.php'; ?>
        </div>
    </main>

    <!-- Scripts Component -->
    <?php include '../../inclusoes/components/admin/user_management_scripts.php'; ?>

</body>
</html>




