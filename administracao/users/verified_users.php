<?php
// admin/verified_users.php
session_start();
$admin_base = '../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Auth check
if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch approved/verified users
$query_verified = "SELECT * FROM users WHERE verification_status = 'verified' ORDER BY full_name ASC";
$verified_users = $db->query($query_verified)->fetchAll();

$query_mentors = "SELECT * FROM users WHERE mentorship_status = 'approved' ORDER BY full_name ASC";
$approved_mentors = $db->query($query_mentors)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Comunidade de Elite - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .premium-tabs { display: flex; gap: 1rem; margin-bottom: 2rem; background: rgba(0,0,0,0.2); padding: 0.5rem; border-radius: 14px; border: 1px solid rgba(255,255,255,0.05); width: fit-content; }
        .p-tab { padding: 0.75rem 1.5rem; border-radius: 10px; cursor: pointer; font-size: 0.85rem; font-weight: 800; color: rgba(255,255,255,0.4); transition: 0.3s; border: none; background: transparent; }
        .p-tab.active { background: #f7941d; color: #000; box-shadow: 0 4px 15px rgba(247, 148, 29, 0.3); }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Comunidade de Elite</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Gestão exclusiva de utilizadores verificados e mentores certificados.</p>
            </div>
            <div style="background: rgba(247, 148, 29, 0.1); border: 1px solid rgba(247, 148, 29, 0.2); padding: 0.6rem 1.25rem; border-radius: 12px; font-size: 0.75rem; color: #f7941d; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-crown"></i> MEMBROS TRUSTED
            </div>
        </header>

        <div class="premium-tabs">
            <button class="p-tab active" onclick="switchTab('verified', this)">
                <i class="fas fa-user-check"></i> VERIFICADOS (<?= count($verified_users) ?>)
            </button>
            <button class="p-tab" onclick="switchTab('mentors', this)">
                <i class="fas fa-graduation-cap"></i> MENTORES (<?= count($approved_mentors) ?>)
            </button>
        </div>

        <!-- Verified Users Section -->
        <div id="verified-section" class="admin-card-premium" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Membro</th>
                            <th>Email Institucional</th>
                            <th>Estado KYC</th>
                            <th>Destaque</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($verified_users)): ?>
                            <tr><td colspan="4" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);">Nenhum membro verificado.</td></tr>
                        <?php endif; ?>
                        <?php foreach($verified_users as $u): ?>
                        <tr>
                            <td>
                                <?php 
                                    $final_pic = '../../' . getUserAvatarUrl($u['user_type'], $u['mentorship_status'] ?? 'unsubmitted');
                                ?>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="<?= $final_pic ?>" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(247, 148, 29, 0.3);">
                                    <div>
                                        <div style="font-weight: 800; color: #fff; font-size: 0.9rem;"><?= htmlspecialchars($u['full_name']) ?></div>
                                        <span style="font-size: 0.6rem; color: #f7941d; font-weight: 800; text-transform: uppercase;">ID #<?= $u['user_id'] ?></span>
                                    </div>
                                </div>
                            </td>
                            <td><div style="font-size: 0.85rem; color: rgba(255,255,255,0.5);"><?= htmlspecialchars($u['email']) ?></div></td>
                            <td><span style="font-size: 0.65rem; background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 4px 10px; border-radius: 6px; font-weight: 900; border: 1px solid rgba(16, 185, 129, 0.2);"><i class="fas fa-check-circle"></i> VERIFICADO</span></td>
                            <td>
                                <a href="../profile.php?user_id=<?= $u['user_id'] ?>" class="btn-action" title="Ver Perfil Público" target="_blank">
                                    <i class="fas fa-external-link-alt"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Mentors Section -->
        <div id="mentors-section" class="admin-card-premium" style="padding: 0; display: none;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Mentor Certificado</th>
                            <th>Área Ténica</th>
                            <th>Experiência</th>
                            <th>Consultar</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($approved_mentors)): ?>
                            <tr><td colspan="4" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);">Nenhum mentor de referência certificado.</td></tr>
                        <?php endif; ?>
                        <?php foreach($approved_mentors as $m): ?>
                        <tr>
                            <td>
                                <?php 
                                    $final_pic = '../../' . getUserAvatarUrl($m['user_type'], $m['mentorship_status'] ?? 'unsubmitted');
                                ?>
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <img src="<?= $final_pic ?>" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(247, 148, 29, 0.3);">
                                    <div>
                                        <div style="font-weight: 800; color: #fff; font-size: 0.9rem;"><?= htmlspecialchars($m['full_name']) ?></div>
                                        <span style="font-size: 0.6rem; color: #fbbf24; font-weight: 800; text-transform: uppercase;">PREMIUM MENTOR</span>
                                    </div>
                                </div>
                            </td>
                            <td><div style="font-size: 0.85rem; color: #f7941d; font-weight: 700;"><?= htmlspecialchars($m['specialization_tags'] ?? 'Especialista') ?></div></td>
                            <td><div style="font-size: 0.85rem; color: rgba(255,255,255,0.5);"><?= $m['years_of_experience'] ?? '0' ?> Anos</div></td>
                            <td>
                                <a href="../profile.php?user_id=<?= $m['user_id'] ?>" class="btn-action" title="Ver Perfil Mentor" target="_blank" style="color: #fbbf24;">
                                    <i class="fas fa-eye"></i>
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function switchTab(tab, btn) {
        const vSec = document.getElementById('verified-section');
        const mSec = document.getElementById('mentors-section');
        const tabs = document.querySelectorAll('.p-tab');

        tabs.forEach(t => t.classList.remove('active'));
        btn.classList.add('active');
        
        if(tab === 'verified') {
            vSec.style.display = 'block';
            mSec.style.display = 'none';
        } else {
            vSec.style.display = 'none';
            mSec.style.display = 'block';
        }
    }
    </script>
</body>
</html>





