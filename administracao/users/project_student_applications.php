<?php
// administracao/users/project_student_applications.php
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
ensureStudentApplicationsSchema($db);

// Fetch all project student applications
try {
    $query = "
        SELECT psa.*, p.title as project_title, u.full_name as student_name, u.profile_pic, u.specialization_tags
        FROM project_student_applications psa
        JOIN projects p ON p.project_id = psa.project_id
        JOIN users u ON u.user_id = psa.student_id
        ORDER BY CASE WHEN psa.status = 'submitted' THEN 0 ELSE 1 END, psa.created_at DESC
    ";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $apps = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (Exception $e) {
    $apps = [];
    $error_msg = $e->getMessage();
}

function getStatusBadge($status) {
    switch ($status) {
        case 'submitted': return '<span style="background: rgba(247,148,29,0.1); color: #f7941d; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Enviada</span>';
        case 'under_review': return '<span style="background: rgba(96,165,250,0.1); color: #60a5fa; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Em Análise</span>';
        case 'shortlisted': return '<span style="background: rgba(167,139,250,0.1); color: #a78bfa; padding: 4px 8px; border-radius: 6px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase;">Pré-Selecionado</span>';
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
    <title>Candidaturas de Estudantes a Projectos - KALIYE Admin</title>

    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .pstudent-card { background: rgba(15,23,42,0.6); border: 1px solid rgba(255,255,255,0.05); border-radius: 20px; padding: 1.5rem; margin-bottom: 1.5rem; }
        .pstudent-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem; border-bottom: 1px solid rgba(255,255,255,0.05); padding-bottom: 1rem; }
    </style>
    <?php 
    if (!function_exists('renderKaliyeFavicons')) {
        $root_dir_favicon = __DIR__;
        while (!is_dir($root_dir_favicon . '/inclusoes') && dirname($root_dir_favicon) !== $root_dir_favicon) {
            $root_dir_favicon = dirname($root_dir_favicon);
        }
        require_once $root_dir_favicon . '/inclusoes/components/favicon.php';
    }
    renderKaliyeFavicons($base_url ?? './'); 
    ?>
</head>
<body>
    <div class="container-admin">
        <?php include '../barra_lateral.php'; ?>
        
        <main class="admin-content">
            <div style="padding: 2rem;">
                <h1>Candidaturas de Estudantes a Projectos</h1>
                <p style="color: var(--text-secondary); margin-bottom: 2rem;">Gerencie as candidaturas de estudantes para participar em projetos de investidores e mentores.</p>

                <?php if (isset($error_msg)): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.3); color: #ef4444; padding: 1rem; border-radius: 10px; margin-bottom: 2rem;">
                        <strong>Erro:</strong> <?php echo htmlspecialchars($error_msg); ?>
                    </div>
                <?php endif; ?>

                <?php if (count($apps) > 0): ?>
                    <div>
                        <?php foreach ($apps as $app): ?>
                            <div class="pstudent-card">
                                <div class="pstudent-header">
                                    <div>
                                        <h3><?php echo htmlspecialchars($app['student_name']); ?></h3>
                                        <p style="color: var(--text-secondary); font-size: 0.9rem; margin: 0.5rem 0 0 0;">
                                            Projeto: <strong><?php echo htmlspecialchars($app['project_title']); ?></strong>
                                        </p>
                                    </div>
                                    <div>
                                        <?php echo getStatusBadge($app['status']); ?>
                                    </div>
                                </div>

                                <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; margin-bottom: 1rem; font-size: 0.9rem;">
                                    <?php if ($app['motivation']): ?>
                                        <p><strong>Motivação:</strong> <?php echo nl2br(htmlspecialchars($app['motivation'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($app['relevant_skills']): ?>
                                        <p><strong>Competências Relevantes:</strong> <?php echo nl2br(htmlspecialchars($app['relevant_skills'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($app['learning_objectives']): ?>
                                        <p><strong>Objetivos de Aprendizagem:</strong> <?php echo nl2br(htmlspecialchars($app['learning_objectives'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($app['time_availability']): ?>
                                        <p><strong>Disponibilidade de Tempo:</strong> <?php echo nl2br(htmlspecialchars($app['time_availability'])); ?></p>
                                    <?php endif; ?>
                                    <?php if ($app['academic_background']): ?>
                                        <p><strong>Formação Académica:</strong> <?php echo nl2br(htmlspecialchars($app['academic_background'])); ?></p>
                                    <?php endif; ?>
                                </div>

                                <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                                    <button class="btn-primary" onclick="updateApplicationStatus(<?php echo $app['application_id']; ?>, 'approved')" style="padding: 0.7rem 1.5rem; font-size: 0.9rem;">
                                        Aprovar
                                    </button>
                                    <button class="btn-secondary" onclick="updateApplicationStatus(<?php echo $app['application_id']; ?>, 'rejected')" style="padding: 0.7rem 1.5rem; font-size: 0.9rem;">
                                        Rejeitar
                                    </button>
                                    <button class="btn-secondary" onclick="updateApplicationStatus(<?php echo $app['application_id']; ?>, 'under_review')" style="padding: 0.7rem 1.5rem; font-size: 0.9rem;">
                                        Em Análise
                                    </button>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div style="text-align: center; padding: 3rem; background: rgba(15,23,42,0.3); border-radius: 20px;">
                        <p style="color: var(--text-secondary);">Sem candidaturas de estudantes no momento.</p>
                    </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <script>
    function updateApplicationStatus(applicationId, newStatus) {
        fetch('../../interface_programacao/admin/admin_process_student_application.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
            body: 'application_id=' + applicationId + '&status=' + newStatus
        })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                Swal.fire('Sucesso', data.message, 'success').then(() => location.reload());
            } else {
                Swal.fire('Erro', data.message, 'error');
            }
        });
    }
    </script>
</body>
</html>
