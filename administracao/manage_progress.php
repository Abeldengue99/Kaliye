<?php
/**
 * administracao/manage_progress.php - Gestão de Relatórios de Progresso (Aprovação KALIYE)
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

$db = (new Database())->getConnection();

// Buscar Relatórios que aguardam aprovação da Admin (Já validados pelo Mentor)
// A query tenta incluir as colunas de escrow. Se ainda não existirem, usa a query de fallback.
try {
    $query = "SELECT r.*, p.title as project_name, p.owner_id, u.full_name as author_name, m.full_name as mentor_name,
              COALESCE(p.escrow_balance, 0) as escrow_balance,
              COALESCE(p.current_milestone_index, 0) as milestone_index
              FROM project_progress_reports r
              JOIN projects p ON r.project_id = p.project_id
              JOIN users u ON r.author_id = u.user_id
              LEFT JOIN users m ON p.assigned_mentor_id = m.user_id
              WHERE r.report_status = 'pending_admin' 
              ORDER BY r.created_at DESC";
    $reports = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // Fallback: colunas de escrow ainda não existem (migração pendente)
    $query = "SELECT r.*, p.title as project_name, p.owner_id, u.full_name as author_name, m.full_name as mentor_name,
              0 as escrow_balance, 0 as milestone_index
              FROM project_progress_reports r
              JOIN projects p ON r.project_id = p.project_id
              JOIN users u ON r.author_id = u.user_id
              LEFT JOIN users m ON p.assigned_mentor_id = m.user_id
              WHERE r.report_status = 'pending_admin' 
              ORDER BY r.created_at DESC";
    $reports = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);
}

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Progresso - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">

    <link rel="stylesheet" href="../recursos/css/style.css">
    <link rel="stylesheet" href="../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .report-card {
            background: rgba(255,255,255,0.03);
            border: 1px solid rgba(255,255,255,0.08);
            border-radius: 20px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            transition: transform 0.3s;
        }
        .report-card:hover { transform: translateY(-5px); background: rgba(255,255,255,0.05); }
        .tag-status { padding: 4px 12px; border-radius: 50px; font-size: 0.7rem; font-weight: 800; text-transform: uppercase; }
        .tag-mentor { background: rgba(59, 130, 246, 0.1); color: #60a5fa; border: 1px solid rgba(59, 130, 246, 0.2); }
        .btn-action { padding: 8px 16px; border-radius: 10px; font-size: 0.75rem; font-weight: 800; cursor: pointer; border: none; transition: 0.3s; }
        .btn-approve { background: #10b981; color: white; }
        .btn-reject { background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2); }
    </style>
</head>
<body class="admin-dashboard-layout">
    
    <?php include 'barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Validação de Roadmap</h1>
                <p style="color: rgba(255,255,255,0.5);">Supervisão de avanços investidos na plataforma.</p>
            </div>
        </header>

        <div style="margin-top: 2rem;">
            <?php if (empty($reports)): ?>
                <div style="text-align: center; padding: 5rem; background: rgba(255,255,255,0.01); border-radius: 30px; border: 1px dashed rgba(255,255,255,0.1);">
                    <i class="fas fa-check-double" style="font-size: 3rem; color: #10b981; margin-bottom: 1.5rem; opacity: 0.5;"></i>
                    <h3 style="color: #fff; font-weight: 800;">Tudo em Dia!</h3>
                    <p style="color: rgba(255,255,255,0.4);">Não existem relatórios de progresso pendentes de validação administrativa.</p>
                </div>
            <?php else: ?>
                <div style="display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 1.5rem;">
                    <?php foreach ($reports as $r): ?>
                        <div class="report-card">
                            <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1rem;">
                                <div>
                                    <span class="tag-status tag-mentor"><i class="fas fa-user-shield"></i> Validado por <?php echo htmlspecialchars($r['mentor_name'] ?? 'Mentor'); ?></span>
                                    <h3 style="color: #fff; margin: 10px 0 5px; font-size: 1.1rem; font-weight: 800;"><?php echo htmlspecialchars($r['title']); ?></h3>
                                    <div style="color: var(--accent-orange); font-size: 0.75rem; font-weight: 900;"><?php echo htmlspecialchars($r['project_name']); ?></div>
                                </div>
                                <div style="text-align: right;">
                                    <div style="color: #fff; font-size: 1.2rem; font-weight: 900;"><?php echo $r['progress_percentage']; ?>%</div>
                                    <div style="color: rgba(255,255,255,0.2); font-size: 0.65rem; font-weight: 700;">AVANÇO GLOBAL</div>
                                </div>
                            </div>

                            <div style="background: rgba(0,0,0,0.2); border-radius: 12px; padding: 1rem; margin-bottom: 1.5rem; color: rgba(255,255,255,0.6); font-size: 0.85rem; line-height: 1.6; max-height: 100px; overflow-y: auto;">
                                <?php echo nl2br(htmlspecialchars($r['content'])); ?>
                            </div>

                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <div style="font-size: 0.7rem; color: rgba(255,255,255,0.3); font-weight: 700;">
                                    SUBMETIDO POR: <strong><?php echo htmlspecialchars($r['author_name']); ?></strong><br>
                                    EM: <?php echo date('d/m/Y H:i', strtotime($r['created_at'])); ?>
                                </div>

                                <?php
                                $escrow = (float)$r['escrow_balance'];
                                $tranche = round($escrow * 0.25, 2);
                                $milestone_num = (int)$r['milestone_index'] + 1;
                                if ($escrow > 0):
                                ?>
                                <div style="background: rgba(16, 185, 129, 0.08); border: 1px solid rgba(16, 185, 129, 0.2); border-radius: 12px; padding: 8px 14px; text-align: center;">
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); text-transform: uppercase; letter-spacing: 1px;">Escrow — Marco <?php echo $milestone_num; ?></div>
                                    <div style="color: #10b981; font-weight: 900; font-size: 1rem;"><?php echo number_format($tranche, 2, ',', '.'); ?> <span style="font-size:0.6rem;">AOA</span></div>
                                    <div style="font-size: 0.55rem; color: rgba(255,255,255,0.3);">serão libertados ao aprovar</div>
                                </div>
                                <?php endif; ?>

                                <div style="display: flex; gap: 0.8rem;">
                                    <button onclick="handleReport(<?php echo $r['report_id']; ?>, 'feedback')" class="btn-action btn-reject">RECUSAR</button>
                                    <button onclick="handleReport(<?php echo $r['report_id']; ?>, 'approve')" class="btn-action btn-approve">PUBLICAR AGORA</button>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>
        </div>
    </main>

    <script>
    function handleReport(reportId, action) {
        if (action === 'approve') {
            Swal.fire({
                title: 'Confirmar Publicação?',
                text: 'O progresso será visível para todos os investidores do projecto.',
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#10b981',
                confirmButtonText: 'Sim, Publicar!',
                cancelButtonText: 'Cancelar',
                background: '#1e293b',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) processAction(reportId, 'approve');
            });
        } else {
            Swal.fire({
                title: 'Motivo da Recusa',
                input: 'textarea',
                inputPlaceholder: 'Explica o que precisa de ser corrigido...',
                showCancelButton: true,
                confirmButtonText: 'Enviar Feedback',
                cancelButtonText: 'Voltar',
                background: '#1e293b',
                color: '#fff',
                inputAttributes: { required: 'true' }
            }).then((result) => {
                if (result.isConfirmed && result.value) {
                    processAction(reportId, 'feedback', result.value);
                }
            });
        }
    }

    function processAction(id, action, feedback = '') {
        const formData = new FormData();
        formData.append('report_id', id);
        formData.append('action', action);
        formData.append('admin_feedback', feedback);

        fetch('../interface_programacao/admin/approve_progress_report.php', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                Swal.fire({ title: 'Sucesso!', text: data.message, icon: 'success', background: '#1e293b', color: '#fff' })
                .then(() => location.reload());
            } else {
                Swal.fire({ title: 'Erro', text: data.message, icon: 'error', background: '#1e293b', color: '#fff' });
            }
        });
    }
    </script>
</body>
</html>

