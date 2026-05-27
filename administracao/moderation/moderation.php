<?php
/**
 * admin/moderation.php - Project Moderation
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('moderation')) {
    header("Location: index.php"); 
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$projects = $db->query("
    SELECT p.*, u.full_name, u.profile_pic 
    FROM projects p 
    JOIN users u ON p.owner_id = u.user_id 
    ORDER BY p.created_at DESC
")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderação - KALIYE Admin</title>
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
                <h1>Moderação de Projectos</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Validar e supervisionar submissões da rede.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="export_projects.php?format=view" target="_blank" class="btn-admin">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="export_projects.php?format=csv" class="btn-admin btn-admin-primary">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
            </div>
        </header>

        <div class="admin-card-premium">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Autor</th>
                            <th>Projecto</th>
                            <th>Categoria</th>
                            <th>Data</th>
                            <th style="text-align: right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($projects)): ?>
                            <tr><td colspan="5" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);"><i class="fas fa-check-circle" style="font-size:3rem; margin-bottom:1rem; opacity:0.5;"></i><br>Tudo em dia! Nenhum projecto pendente.</td></tr>
                        <?php endif; ?>
                        <?php foreach($projects as $p): ?>
                        <tr id="project-row-<?= $p['project_id'] ?>">
                            <td>
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="../../<?= ($p['profile_pic'] && $p['profile_pic'] != 'default_profile.png') ? $p['profile_pic'] : 'recursos/images/default_profile.png' ?>" style="width: 36px; height: 36px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(255,148,29,0.2);">
                                    <span style="font-weight: 600; color: #fff;"><?= htmlspecialchars($p['full_name']) ?></span>
                                </div>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #fff;">
                                    <?= htmlspecialchars($p['title']) ?>
                                    <?php if ($p['approval_status'] === 'approved'): ?>
                                        <i class="fas fa-circle-check" style="color: #10b981; font-size: 0.75rem; margin-left: 5px;"></i>
                                    <?php endif; ?>
                                </div>
                                <div style="font-size: 0.75rem; color: rgba(255,255,255,0.4); max-width: 300px; display: -webkit-box; -webkit-line-clamp: 1; -webkit-box-orient: vertical; overflow: hidden;">
                                    <?= htmlspecialchars($p['description']) ?>
                                </div>
                            </td>
                            <td>
                                <span class="user-badge-premium" style="color: #f7941d; border-color: rgba(247, 148, 29, 0.2);">
                                    <?= htmlspecialchars($p['category']) ?>
                                </span>
                            </td>
                            <td style="color: rgba(255,255,255,0.5); font-size: 0.85rem;">
                                <?= date('d M, Y', strtotime($p['created_at'])) ?>
                            </td>
                            <td>
                                <div style="display: flex; gap: 0.6rem; justify-content: flex-end;">
                                    <?php if ($p['approval_status'] !== 'approved'): ?>
                                        <button onclick="approveProject(<?= $p['project_id'] ?>)" class="btn-action approve" title="Aprovar"><i class="fas fa-check"></i></button>
                                        <button onclick="rejectProject(<?= $p['project_id'] ?>)" class="btn-action reject" title="Rejeitar"><i class="fas fa-times"></i></button>
                                    <?php endif; ?>
                                    <button onclick="viewProject(<?= $p['project_id'] ?>)" class="btn-action info" title="Ver Detalhes"><i class="fas fa-arrow-up-right-from-square"></i></button>
                                    <button onclick="deleteProject(<?= $p['project_id'] ?>)" class="btn-action danger" title="Eliminar"><i class="fas fa-trash-can"></i></button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL DE DETALHES DO PROJECTO DINÂMICO -->
        <div id="projectModal" class="modal-overlay" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.85); z-index:10000; backdrop-filter:blur(15px); align-items:center; justify-content:center;">
            <div class="admin-card-premium" style="width:95%; max-width:1000px; max-height:90vh; overflow-y:auto; position:relative; padding:3rem; border:1px solid rgba(247,148,29,0.3); box-shadow: 0 25px 50px rgba(0,0,0,0.6);">
                <button onclick="closeModal()" style="position:absolute; top:2rem; right:2rem; background:none; border:none; color:rgba(255,255,255,0.4); font-size:1.8rem; cursor:pointer; transition:0.3s;" onmouseover="this.style.color='#fff'" onmouseout="this.style.color='rgba(255,255,255,0.4)'"><i class="fas fa-times"></i></button>
                
                <div id="modalContent">
                    <!-- Conteúdo Carregado via AJAX -->
                    <div style="text-align:center; padding:4rem;">
                        <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:var(--accent-orange);"></i>
                        <p style="margin-top:1rem; color:rgba(255,255,255,0.5);">A carregar detalhes do projecto...</p>
                    </div>
                </div>
                
                <div id="modalActions" style="margin-top:3rem; padding-top:2rem; border-top:1px solid rgba(255,255,255,0.08); display:flex; justify-content:flex-end; gap:1.2rem;">
                    <!-- Botões de Ação -->
                </div>
            </div>
        </div>
    </main>

    <script>
    function approveProject(id) {
        Swal.fire({
            title: 'Aprovar Projecto?',
            text: "O projecto será aprovado e ficará visível publicamente na plataforma.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#10b981',
            confirmButtonText: 'Sim, Aprovar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../interface_programacao/admin/admin_process_project.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=approve&project_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Aprovado!', 'O projecto foi aprovado com sucesso.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.error || 'Falha ao aprovar o projecto.', 'error');
                    }
                })
                .catch(() => Swal.fire('Erro', 'Ocorreu um erro no servidor.', 'error'));
            }
        });
    }

    function rejectProject(id) {
        Swal.fire({
            title: 'Rejeitar Projecto?',
            text: "O projecto será marcado como rejeitado e não aparecerá no feed público.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Sim, Rejeitar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                fetch('../../interface_programacao/admin/admin_process_project.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=reject&project_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire('Rejeitado!', 'O projecto foi rejeitado.', 'success').then(() => location.reload());
                    } else {
                        Swal.fire('Erro', data.error || 'Falha ao rejeitar o projecto.', 'error');
                    }
                })
                .catch(() => Swal.fire('Erro', 'Ocorreu um erro no servidor.', 'error'));
            }
        });
    }

    function viewProject(id) {
        document.getElementById('projectModal').style.display = 'flex';
        document.getElementById('modalContent').innerHTML = `
            <div style="text-align:center; padding:4rem;">
                <i class="fas fa-spinner fa-spin" style="font-size:2rem; color:var(--accent-orange);"></i>
                <p style="margin-top:1rem; color:rgba(255,255,255,0.5);">A carregar detalhes do projecto...</p>
            </div>
        `;

        fetch('get_project_details.php?id=' + id)
        .then(res => res.json())
        .then(data => {
            if (!data.success) {
                Swal.fire('Erro', data.error, 'error');
                closeModal();
                return;
            }
            const p = data.project;
            const content = document.getElementById('modalContent');
            const actions = document.getElementById('modalActions');
            
            content.innerHTML = `
                <div style="display:flex; gap:2.5rem; align-items:flex-start; margin-bottom:3rem;">
                    <div style="width:100px; height:100px; border-radius:20px; overflow:hidden; border:3px solid var(--accent-orange); flex-shrink:0; box-shadow:0 10px 20px rgba(0,0,0,0.3);">
                        <img src="../../${p.profile_pic || 'recursos/images/default_profile.png'}" style="width:100%; height:100%; object-fit:cover;">
                    </div>
                    <div>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:0.5rem;">
                            <h2 style="font-size:2.2rem; font-weight:900; color:#fff; margin:0; letter-spacing:-1px;">${p.title}</h2>
                            ${p.approval_status === 'approved' ? '<i class="fas fa-check-circle" style="color:#10b981; font-size:1.2rem;"></i>' : ''}
                        </div>
                        <p style="color:var(--accent-orange); font-weight:800; text-transform:uppercase; font-size:0.85rem; letter-spacing:2px; display:flex; align-items:center; gap:8px;">
                            <i class="fas fa-folder-open"></i> ${p.category} <span style="color:rgba(255,255,255,0.2);">|</span> <i class="fas fa-user-tie"></i> POR ${p.full_name.toUpperCase()}
                        </p>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns: 1.8fr 1fr; gap:3.5rem;">
                    <div class="modal-info-section">
                        <div style="margin-bottom:2.5rem;">
                            <label style="display:block; color:rgba(255,255,255,0.3); text-transform:uppercase; font-size:0.7rem; font-weight:900; margin-bottom:1rem; letter-spacing:1.5px;">Descrição Detalhada</label>
                            <div style="color:rgba(255,255,255,0.85); line-height:1.8; white-space:pre-wrap; font-size:1.05rem; background:rgba(255,255,255,0.02); padding:1.5rem; border-radius:15px; border:1px solid rgba(255,255,255,0.04);">
                                ${p.description}
                            </div>
                        </div>
                        
                        <div style="display:grid; grid-template-columns:1fr 1fr; gap:2rem;">
                            <div>
                                <label style="display:block; color:rgba(255,255,255,0.3); text-transform:uppercase; font-size:0.7rem; font-weight:900; margin-bottom:1rem; letter-spacing:1.5px;">Plano de Execução & Equipa</label>
                                <p style="color:rgba(255,255,255,0.7); font-size:0.95rem; line-height:1.6;">
                                    <strong>Tempo:</strong> ${p.execution_time || 'N/D'}<br>
                                    <strong>Equipa:</strong> ${p.team_size || 1} Membro(s)<br>
                                    <strong>Público:</strong> ${p.target_audience || 'Geral'}
                                </p>
                            </div>
                            <div>
                                <label style="display:block; color:rgba(255,255,255,0.3); text-transform:uppercase; font-size:0.7rem; font-weight:900; margin-bottom:1rem; letter-spacing:1.5px;">Génese do Projecto</label>
                                <p style="color:rgba(255,255,255,0.7); font-size:0.95rem; line-height:1.6;">
                                    ${p.idea_origin ? `<strong>Origem:</strong> ${p.idea_origin}<br>` : ''}
                                    ${p.motivation ? `<strong>Motivação:</strong> ${p.motivation}` : ''}
                                </p>
                            </div>
                            <div style="grid-column: span 2;">
                                <label style="display:block; color:rgba(255,255,255,0.3); text-transform:uppercase; font-size:0.7rem; font-weight:900; margin-bottom:1rem; letter-spacing:1.5px;">Obstáculos Críticos</label>
                                <p style="color:rgba(255,255,255,0.7); font-size:0.95rem; line-height:1.6;">${p.needs_to_advance || 'Não especificado pelo autor.'}</p>
                            </div>
                            ${p.project_url ? `
                            <div style="grid-column: span 2; margin-top: 1rem;">
                                <a href="${p.project_url}" target="_blank" style="color: var(--accent-orange); text-decoration: none; font-weight: 800; font-size: 0.9rem;">
                                    <i class="fas fa-external-link-alt"></i> Website do Projecto: ${p.project_url}
                                </a>
                            </div>
                            ` : ''}
                        </div>
                    </div>

                    
                    <div class="modal-info-sidebar">
                        <div style="background:rgba(255,255,255,0.03); padding:2rem; border-radius:20px; border:1px solid rgba(255,255,255,0.06); position:sticky; top:0;">
                            <div style="margin-bottom:2rem;">
                                <small style="color:rgba(255,255,255,0.4); font-size:0.75rem; display:block; margin-bottom:8px; font-weight:700; text-transform:uppercase;">Necessidade Financeira</small>
                                <strong style="color:#fff; font-size:1.6rem; font-weight:900; display:block;">${new Intl.NumberFormat('pt-AO', { style: 'currency', currency: 'AOA' }).format(p.funding_goal || 0)}</strong>
                                <p style="font-size:0.7rem; color:rgba(255,255,255,0.2); margin-top:5px;">${p.funding_type || 'Captação Aberta'}</p>
                            </div>
                            
                            <div style="margin-bottom:2rem; padding-top:1.5rem; border-top:1px solid rgba(255,255,255,0.05);">
                                <small style="color:rgba(255,255,255,0.4); font-size:0.75rem; display:block; margin-bottom:8px; font-weight:700; text-transform:uppercase;">Estado de Execução</small>
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <div style="flex:1; height:6px; background:rgba(255,255,255,0.1); border-radius:10px; overflow:hidden;">
                                        <div style="width: ${p.project_stage === 'Beta' ? '60%' : (p.project_stage === 'MVP' ? '30%' : '10%')}; height:100%; background:var(--accent-orange);"></div>
                                    </div>
                                    <span style="color:#fff; font-size:0.8rem; font-weight:700;">${p.project_stage || 'Ideia'}</span>
                                </div>
                            </div>

                            <div>
                                <small style="color:rgba(255,255,255,0.4); font-size:0.75rem; display:block; margin-bottom:12px; font-weight:700; text-transform:uppercase;">Etiquetas do Ecossistema</small>
                                <div style="display:flex; flex-wrap:wrap; gap:8px;">
                                    ${(p.tags || []).map(t => `<span style="background:rgba(247,148,29,0.1); border:1px solid rgba(247,148,29,0.2); padding:6px 12px; border-radius:8px; font-size:0.65rem; color:#f7941d; font-weight:700;">${t}</span>`).join('')}
                                    ${(p.tags || []).length === 0 ? '<span style="color:rgba(255,255,255,0.2); font-size:0.75rem; font-style:italic;">Sem tags</span>' : ''}
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;

            actions.innerHTML = `
                <button onclick="closeModal()" class="btn-admin" style="background:rgba(255,255,255,0.05); color:#fff; padding:0.8rem 1.8rem;">Fechar Painel</button>
                ${p.approval_status !== 'approved' ? `
                    <button onclick="rejectProject(${p.project_id})" class="btn-admin" style="background:rgba(239,68,68,0.1); color:#ef4444; border:1px solid rgba(239,68,68,0.2); padding:0.8rem 1.8rem;">Rejeitar Proposta</button>
                    <button onclick="approveProject(${p.project_id})" class="btn-admin btn-admin-primary" style="padding:0.8rem 2.2rem;">✓ Aprovar e Publicar</button>
                ` : '<div style="background:rgba(16,185,129,0.1); color:#10b981; padding:0.8rem 2rem; border-radius:12px; font-weight:800; display:flex; align-items:center; gap:10px;"><i class="fas fa-check-double"></i> PROJECTO PUBLICADO NO FEED</div>'}
            `;
        })
        .catch(err => {
            console.error(err);
            Swal.fire('Erro', 'Falha ao comunicar com o servidor.', 'error');
            closeModal();
        });
    }

    function closeModal() {
        document.getElementById('projectModal').style.display = 'none';
    }

    function deleteProject(id) {
        Swal.fire({
            title: 'Tem a certeza?',
            text: "O projecto será removido permanentemente e o utilizador será notificado!",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Sim, eliminar',
            cancelButtonText: 'Cancelar'
        }).then((result) => {
            if (result.isConfirmed) {
                // Mostrar loading
                Swal.fire({
                    title: 'A eliminar...',
                    allowOutsideClick: false,
                    didOpen: () => Swal.showLoading()
                });

                fetch('../../interface_programacao/admin/admin_process_project.php', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=delete&project_id=' + id
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        Swal.fire({
                            icon: 'success',
                            title: 'Eliminado!',
                            text: 'O projecto foi removido com sucesso.',
                            timer: 2000,
                            showConfirmButton: false
                        });
                        // Remover a linha da tabela sem recarregar
                        const row = document.getElementById('project-row-' + id);
                        if (row) {
                            row.style.transition = 'opacity 0.4s';
                            row.style.opacity = '0';
                            setTimeout(() => row.remove(), 400);
                        }
                    } else {
                        Swal.fire('Erro', data.error || 'Falha ao eliminar o projecto.', 'error');
                    }
                })
                .catch(() => Swal.fire('Erro', 'Ocorreu um erro no servidor.', 'error'));
            }
        });
    }
    </script>

</body>
</html>



