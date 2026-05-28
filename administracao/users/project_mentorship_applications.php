<?php
// administracao/users/project_mentorship_applications.php
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

if (!hasPermission('mentor_assignment')) {
    header("Location: ../index.php"); 
    exit();
}

$database = new Database();
$db = $database->getConnection();

require_once '../../inclusoes/ProjectWorkflowSchema.php';
ensureProjectMentorshipApplicationsSchema($db);

// Fetch all project mentorship applications
$query = "
    SELECT pma.*, p.title as project_title, u.full_name as mentor_name, u.profile_pic, u.specialization_tags
    FROM project_mentorship_applications pma
    JOIN projects p ON p.project_id = pma.project_id
    JOIN users u ON u.user_id = pma.mentor_id
    ORDER BY CASE WHEN pma.status = 'pending' THEN 0 ELSE 1 END, pma.created_at DESC
";
$apps = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

function getStatusBadge($status) {
    switch ($status) {
        case 'pending': return '<span style="background: rgba(247,148,29,0.1); color: #f7941d; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Pendente</span>';
        case 'under_review': return '<span style="background: rgba(96,165,250,0.1); color: #60a5fa; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Em Análise</span>';
        case 'shortlisted': return '<span style="background: rgba(167,139,250,0.1); color: #a78bfa; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Shortlisted</span>';
        case 'approved': return '<span style="background: rgba(16,185,129,0.1); color: #10b981; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Aprovado</span>';
        case 'rejected': return '<span style="background: rgba(239,68,68,0.1); color: #ef4444; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Rejeitado</span>';
        default: return '<span style="background: rgba(255,255,255,0.1); color: #ccc; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">'.$status.'</span>';
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Candidaturas de Mentoria a Projectos - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .pma-card { background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .pma-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem; }
        .pma-mentor-info { display: flex; align-items: center; gap: 1rem; }
        .pma-avatar { width: 50px; height: 50px; border-radius: 12px; object-fit: cover; border: 2px solid rgba(247, 148, 29, 0.2); }
        .pma-motivation { background: rgba(0,0,0,0.2); padding: 1rem; border-radius: 12px; color: rgba(255,255,255,0.8); font-size: 0.9rem; margin-bottom: 1rem; line-height: 1.6; border-left: 3px solid #f7941d; }
        .pma-actions { display: flex; gap: 0.5rem; justify-content: flex-end; }
        .pma-btn { padding: 0.5rem 1rem; border-radius: 8px; font-weight: 800; font-size: 0.75rem; border: none; cursor: pointer; transition: 0.2s; }
        .btn-review { background: rgba(96,165,250,0.1); color: #60a5fa; }
        .btn-review:hover { background: rgba(96,165,250,0.2); }
        .btn-shortlist { background: rgba(167,139,250,0.1); color: #a78bfa; }
        .btn-shortlist:hover { background: rgba(167,139,250,0.2); }
        .btn-reject { background: rgba(239,68,68,0.1); color: #ef4444; }
        .btn-reject:hover { background: rgba(239,68,68,0.2); }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Candidaturas a Projectos</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Gestão das candidaturas feitas por Mentores para mentorar projectos específicos.</p>
            </div>
            <div style="background: rgba(247, 148, 29, 0.1); border: 1px solid rgba(247, 148, 29, 0.2); padding: 0.6rem 1.25rem; border-radius: 12px; font-size: 0.75rem; color: #f7941d; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-chalkboard-teacher"></i> <?= count($apps) ?> CANDIDATURAS
            </div>
        </header>

        <section>
            <?php if(count($apps) == 0): ?>
                <div class="admin-card-premium" style="padding: 4rem; text-align: center;">
                    <div style="width: 80px; height: 80px; background: rgba(247, 148, 29, 0.1); color: #f7941d; border-radius: 50%; display: flex; align-items: center; justify-content: center; margin: 0 auto 1.5rem; font-size: 2rem;">
                        <i class="fas fa-inbox"></i>
                    </div>
                    <h3 style="color: #fff; font-weight: 800; margin-bottom: 0.5rem;">Nenhuma Candidatura</h3>
                    <p style="color: rgba(255,255,255,0.4);">Ainda não existem candidaturas de mentores para projectos.</p>
                </div>
            <?php else: ?>
                <?php foreach($apps as $app): ?>
                    <div class="pma-card">
                        <div class="pma-header">
                            <div class="pma-mentor-info">
                                <img src="<?= $base_url ?><?= ($app['profile_pic'] && $app['profile_pic'] != 'default_profile.png') ? $app['profile_pic'] : 'recursos/images/default_profile.png' ?>" class="pma-avatar">
                                <div>
                                    <h3 style="margin: 0; font-size: 1.1rem; color: #fff; font-weight: 800;"><?= htmlspecialchars($app['mentor_name']) ?></h3>
                                    <div style="font-size: 0.8rem; color: #f7941d; font-weight: 700; margin-top: 3px;"><?= htmlspecialchars($app['specialization_tags'] ?? 'Mentor') ?></div>
                                </div>
                            </div>
                            <div style="text-align: right;">
                                <?= getStatusBadge($app['status']) ?>
                                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); margin-top: 5px;">
                                    <?= date('d M Y, H:i', strtotime($app['created_at'])) ?>
                                </div>
                            </div>
                        </div>
                        
                        <div style="margin-bottom: 1rem;">
                            <span style="font-size: 0.75rem; color: rgba(255,255,255,0.5); text-transform: uppercase; font-weight: 800; letter-spacing: 1px;">Projecto Alvo:</span>
                            <div style="font-size: 1rem; color: #60a5fa; font-weight: 800; margin-top: 3px;">
                                <i class="fas fa-rocket"></i> <?= htmlspecialchars($app['project_title']) ?> (ID: <?= $app['project_id'] ?>)
                            </div>
                        </div>

                        <?php if(!empty($app['motivation'])): ?>
                        <div class="pma-motivation">
                            <strong style="color: #f7941d; display: block; margin-bottom: 5px; font-size: 0.75rem; text-transform: uppercase; letter-spacing: 1px;">Mensagem / Motivação:</strong>
                            <?= nl2br(htmlspecialchars($app['motivation'])) ?>
                        </div>
                        <?php endif; ?>

                        <?php if(!empty($app['admin_response'])): ?>
                        <div style="background: rgba(96,165,250,0.05); padding: 1rem; border-radius: 12px; margin-bottom: 1rem; border-left: 3px solid #60a5fa; font-size: 0.85rem; color: rgba(255,255,255,0.7);">
                            <strong style="color: #60a5fa; display: block; margin-bottom: 5px;">Resposta do Admin:</strong>
                            <?= nl2br(htmlspecialchars($app['admin_response'])) ?>
                        </div>
                        <?php endif; ?>

                        <?php if(!in_array($app['status'], ['approved', 'withdrawn'])): ?>
                        <div class="pma-actions">
                            <button onclick="processPma(<?= $app['application_id'] ?>, 'under_review')" class="pma-btn btn-review"><i class="fas fa-search"></i> EM ANÁLISE</button>
                            <button onclick="processPma(<?= $app['application_id'] ?>, 'shortlisted')" class="pma-btn btn-shortlist"><i class="fas fa-star"></i> SHORTLIST</button>
                            <button onclick="processPma(<?= $app['application_id'] ?>, 'rejected')" class="pma-btn btn-reject"><i class="fas fa-times"></i> REJEITAR</button>
                        </div>
                        <?php endif; ?>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </section>
    </main>

    <script>
    function processPma(appId, status) {
        Swal.fire({
            title: 'Actualizar Estado',
            text: status === 'rejected' ? 'Por favor, explique o motivo da rejeição (obrigatório):' : 'Pode deixar uma resposta/nota opcional para o mentor:',
            input: 'textarea',
            inputPlaceholder: 'Mensagem...',
            showCancelButton: true,
            confirmButtonText: 'Confirmar',
            cancelButtonText: 'Cancelar',
            background: '#0d1628',
            color: '#fff',
            confirmButtonColor: status === 'rejected' ? '#ef4444' : '#f7941d',
            preConfirm: (value) => {
                if (status === 'rejected' && !value.trim()) {
                    Swal.showValidationMessage('Tem de explicar o motivo da rejeição.');
                    return false;
                }
                return value;
            }
        }).then((result) => {
            if (result.isConfirmed) {
                const fd = new FormData();
                fd.append('application_id', appId);
                fd.append('status', status);
                fd.append('admin_response', result.value || '');

                fetch('../../interface_programacao/admin/admin_process_mentorship_application.php', {
                    method: 'POST',
                    body: fd
                })
                .then(r => r.json())
                .then(data => {
                    if(data.success) {
                        Swal.fire({ icon: 'success', title: 'Sucesso', text: data.message, background: '#0d1628', color: '#fff' })
                        .then(() => location.reload());
                    } else {
                        Swal.fire({ icon: 'error', title: 'Erro', text: data.message, background: '#0d1628', color: '#fff' });
                    }
                });
            }
        });
    }
    </script>
</body>
</html>
