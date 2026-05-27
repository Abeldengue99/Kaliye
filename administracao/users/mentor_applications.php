<?php
// admin/mentor_applications.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';

// Auth check
require_once '../../inclusoes/auth_check.php';
if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

if (!hasPermission('mentor_approval')) {
    header("Location: index.php"); 
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch pending mentorship applications
$query = "SELECT * FROM users WHERE mentorship_status = 'pending' ORDER BY updated_at ASC";
$apps = $db->query($query)->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Aprovação de Mentores - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .mentor-app-card { display: grid; grid-template-columns: 100px 1fr 280px; gap: 2rem; align-items: center; }
        @media (max-width: 992px) { .mentor-app-card { grid-template-columns: 1fr; text-align: center; } .mentor-app-card img { margin: 0 auto; } }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Candidaturas de Mentores</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Validando a excelência académica e profissional da nossa rede.</p>
            </div>
            <div style="background: rgba(247, 148, 29, 0.1); border: 1px solid rgba(247, 148, 29, 0.2); padding: 0.6rem 1.25rem; border-radius: 12px; font-size: 0.75rem; color: #f7941d; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-graduation-cap"></i> <?= count($apps) ?> CANDIDATURAS PENDENTES
            </div>
        </header>

        <section style="display: flex; flex-direction: column; gap: 1.5rem;">
            <?php if(count($apps) == 0): ?>
                <div class="admin-card-premium" style="padding: 4rem; text-align: center;">
                    <div style="width: 80px; height: 80px; background: rgba(16, 185, 129, 0.1); color: #10b981; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem;">
                        <i class="fas fa-check-double"></i>
                    </div>
                    <h3 style="color: #fff; font-weight: 800; margin-bottom: 0.5rem;">Tudo em Dia!</h3>
                    <p style="color: rgba(255,255,255,0.4);">Não existem candidaturas de mentores pendentes de revisão.</p>
                </div>
            <?php else: ?>
                <?php foreach($apps as $app): ?>
                    <div class="admin-card-premium mentor-app-card" id="app-<?= $app['user_id'] ?>">
                        <div style="position: relative;">
                            <img src="../<?= ($app['profile_pic'] && $app['profile_pic'] != 'default_profile.png') ? $app['profile_pic'] : 'recursos/images/default_profile.png' ?>" 
                                 style="width: 100px; height: 100px; border-radius: 20px; object-fit: cover; border: 2px solid rgba(247, 148, 29, 0.3);">
                        </div>
                        
                        <div>
                            <div style="display: flex; align-items: center; gap: 0.75rem; margin-bottom: 0.5rem;">
                                <h3 style="margin: 0; font-size: 1.25rem; font-weight: 900; color: #fff;"><?= htmlspecialchars($app['full_name']) ?></h3>
                                <span style="font-size: 0.6rem; background: rgba(255,255,255,0.05); color: #94a3b8; padding: 3px 8px; border-radius: 4px; font-weight: 700; text-transform: uppercase;">ID: #<?= $app['user_id'] ?></span>
                            </div>
                            <p style="color: #f7941d; font-weight: 800; margin-bottom: 1rem; font-size: 0.95rem;"><?= htmlspecialchars($app['specialization_tags'] ?? 'Especialização não definida') ?></p>
                            
                            <div style="display: flex; gap: 1.5rem; flex-wrap: wrap; font-size: 0.85rem;">
                                <div style="color: rgba(255,255,255,0.4); display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-history" style="color: var(--aksanti-orange);"></i>
                                    <span><?= $app['years_of_experience'] ?? 0 ?> Anos de Experiência</span>
                                </div>
                                <a href="<?= $app['linkedin_url'] ?>" target="_blank" style="color: #60a5fa; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fab fa-linkedin"></i> LinkedIn
                                </a>
                                <a href="../<?= $app['cv_path'] ?>" target="_blank" style="color: #f87171; text-decoration: none; font-weight: 700; display: flex; align-items: center; gap: 0.5rem;">
                                    <i class="fas fa-file-pdf"></i> Curriculum Vitae (PDF)
                                </a>
                            </div>
                        </div>

                        <div style="display: flex; flex-direction: column; gap: 0.75rem;">
                            <button onclick="processApp(<?= $app['user_id'] ?>, 'approve')" class="btn-admin btn-admin-primary" style="width: 100%;">
                                <i class="fas fa-certificate"></i> APROVAR CERTIFICAÇÃO
                            </button>
                            <button onclick="processApp(<?= $app['user_id'] ?>, 'reject')" class="btn-admin" style="width: 100%; border-color: rgba(239, 68, 68, 0.2); color: #f87171; background: rgba(239, 68, 68, 0.05);">
                                <i class="fas fa-ban"></i> RECUSAR CANDIDATURA
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script>
    function processApp(userId, action) {
        Swal.fire({
            title: action === 'approve' ? 'Validar Mentor?' : 'Recusar Candidatura?',
            text: action === 'approve' 
                ? 'Esta ação elevará o utilizador ao estatuto de Mentor de Referência certificado.' 
                : 'O candidato será notificado sobre a não conformidade da sua candidatura.',
            icon: action === 'approve' ? 'question' : 'warning',
            showCancelButton: true,
            confirmButtonText: action === 'approve' ? 'SIM, CERTIFICAR' : 'SIM, RECUSAR',
            cancelButtonText: 'VOLTAR',
            confirmButtonColor: action === 'approve' ? '#10b981' : '#ef4444',
            background: '#050a15',
            color: '#fff',
            customClass: {
                popup: 'admin-swal-premium'
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const formData = new FormData();
                formData.append('user_id', userId);
                formData.append('action', action);

                fetch('../../interface_programacao/admin/admin_process_mentor.php', {
                    method: 'POST',
                    body: formData
                })
                .then(res => res.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({ icon: 'success', title: 'Operação Concluída', background: '#0f172a', color: '#fff' }).then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro de Sistema', text: data.message, background: '#0f172a', color: '#fff' });
                    }
                });
            }
        });
    }
    </script>
</body>
</html>
