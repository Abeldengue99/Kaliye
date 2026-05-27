<?php
// admin/doubts.php - Gestão de Dúvidas
$admin_base = '../';
$base_url = '../../';
require_once '../../inclusoes/auth_check.php';
requireAdmin();

require_once '../../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

$current_user_id = $_SESSION['user_id'];
$current_user_type = $_SESSION['user_type'];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moderação de Dúvidas - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .doubt-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(380px, 1fr)); gap: 1.5rem; }
        @media (max-width: 768px) { .doubt-grid { grid-template-columns: 1fr; } }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Moderação de Dúvidas</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Supervisão e suporte ao fórum de conhecimento da comunidade.</p>
            </div>
            <button onclick="openDoubtModal()" class="btn-admin btn-admin-primary">
                <i class="fas fa-plus"></i> NOVA DÚVIDA
            </button>
        </header>

        <!-- Filtros Avançados -->
        <div class="admin-card-premium" style="margin-bottom: 2rem; padding: 1.25rem;">
            <div style="display: flex; gap: 1rem; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2);"></i>
                    <input type="text" id="searchInput" placeholder="Pesquisar por título, descrição ou autor..." 
                           style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.8rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: #fff; outline: none; transition: 0.3s; font-size: 0.9rem;">
                </div>
                <select id="categoryFilter" onchange="filterDoubts()" 
                        style="padding: 0.75rem 1.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: #fff; outline: none; cursor: pointer;">
                    <option value="">Todas Categorias</option>
                    <option value="programming">Programação</option>
                    <option value="math">Matemática</option>
                    <option value="physics">Física</option>
                    <option value="chemistry">Química</option>
                    <option value="languages">Línguas</option>
                    <option value="business">Negócios</option>
                    <option value="design">Design</option>
                    <option value="other">Outro</option>
                </select>
                <select id="statusFilter" onchange="filterDoubts()" 
                        style="padding: 0.75rem 1.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: #fff; outline: none; cursor: pointer;">
                    <option value="">Todos Status</option>
                    <option value="open">Abertas</option>
                    <option value="resolved">Resolvidas</option>
                    <option value="closed">Fechadas</option>
                </select>
            </div>
        </div>

        <!-- Lista de Dúvidas Grid -->
        <div id="doubts-container" class="doubt-grid">
            <!-- Loading dynamic content -->
        </div>
    </main>

    <!-- Modal: Publicação de Teste -->
    <div id="doubtModal" class="admin-modal-overlay">
        <div class="admin-modal-content" style="max-width: 600px;">
            <div class="admin-modal-header">
                <h3>Publicar Nova Dúvida</h3>
                <button onclick="closeDoubtModal()" class="close-btn">&times;</button>
            </div>
            <form id="doubtForm" onsubmit="submitDoubt(event)" style="padding: 2rem;">
                <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                    <label>Título da Pergunta</label>
                    <input type="text" name="title" required placeholder="Ex: Como otimizar consultas PostgreSQL?">
                </div>
                <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                    <label>Explicação Detalhada</label>
                    <textarea name="description" required rows="5" placeholder="Forneça contexto suficiente..."></textarea>
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 1rem; margin-bottom: 2rem;">
                    <div class="input-group-premium">
                        <label>Categoria</label>
                        <select name="category" required>
                            <option value="programming">Programação</option>
                            <option value="math">Matemática</option>
                            <option value="business">Negócios</option>
                            <option value="other">Outro</option>
                        </select>
                    </div>
                    <div class="input-group-premium">
                        <label>Tags (opcional)</label>
                        <input type="text" name="tags" placeholder="sql, db, performance">
                    </div>
                </div>
                <div style="display: flex; gap: 1rem; justify-content: flex-end;">
                    <button type="button" onclick="closeDoubtModal()" class="btn-admin">CANCELAR</button>
                    <button type="submit" class="btn-admin btn-admin-primary">PUBLICAR</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal: Visualização Detalhada -->
    <div id="doubtDetailModal" class="admin-modal-overlay">
        <div class="admin-modal-content" style="max-width: 800px; max-height: 90vh; overflow-y: auto;">
            <div id="doubtDetailContent">
                <!-- Dynamic Content -->
            </div>
        </div>
    </div>

    <script>
    let allDoubts = [];

    async function loadDoubts() {
        const container = document.getElementById('doubts-container');
        container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 4rem;"><i class="fas fa-circle-notch fa-spin fa-2x" style="color: #f7941d;"></i></div>`;
        
        try {
            const response = await fetch('../../interface_programacao/social/get_doubts.php');
            const data = await response.json();
            if (data.success) {
                allDoubts = data.doubts;
                renderDoubts(allDoubts);
            }
        } catch (error) {
            container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; color: #ef4444;">Erro ao carregar dados.</div>`;
        }
    }

    function renderDoubts(doubts) {
        const container = document.getElementById('doubts-container');
        if (doubts.length === 0) {
            container.innerHTML = `<div style="grid-column: 1/-1; text-align: center; padding: 4rem; color: rgba(255,255,255,0.2);">Nenhum registo encontrado.</div>`;
            return;
        }

        container.innerHTML = doubts.map(doubt => `
            <div class="admin-card-premium" onclick="openDoubtDetail(${doubt.doubt_id})" style="cursor: pointer; transition: 0.3s; position: relative; display: flex; flex-direction: column;">
                <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 1.5rem;">
                    <div style="display: flex; gap: 0.75rem; align-items: center;">
                        <img src="${doubt.profile_pic || '../../recursos/images/default_profile.png'}" style="width: 40px; height: 40px; border-radius: 10px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                        <div>
                            <div style="font-weight: 800; font-size: 0.85rem; color: #fff;">${doubt.full_name}</div>
                            <div style="font-size: 0.65rem; color: rgba(148, 163, 184, 0.6); text-transform: uppercase; font-weight: 700;">${new Date(doubt.created_at).toLocaleDateString()}</div>
                        </div>
                    </div>
                    <span style="font-size: 0.6rem; font-weight: 900; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; 
                          ${doubt.status === 'open' ? 'background: rgba(59, 130, 246, 0.1); color: #60a5fa;' : doubt.status === 'resolved' ? 'background: rgba(16, 185, 129, 0.1); color: #34d399;' : 'background: rgba(255,255,255,0.05); color: #94a3b8;'}">
                        ${doubt.status === 'open' ? '<i class="fas fa-clock"></i> Aberta' : doubt.status === 'resolved' ? '<i class="fas fa-check"></i> Resolvida' : '<i class="fas fa-lock"></i> Fechada'}
                    </span>
                </div>
                
                <h3 style="margin: 0 0 1rem 0; font-size: 1rem; font-weight: 800; color: #f7941d; line-height: 1.4;">${doubt.title}</h3>
                <p style="color: rgba(255,255,255,0.4); font-size: 0.85rem; line-height: 1.6; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; margin-bottom: 1.5rem;">${doubt.description}</p>
                
                <div style="margin-top: auto; display: flex; justify-content: space-between; align-items: center; border-top: 1px solid rgba(255,255,255,0.05); padding-top: 1rem;">
                    <span style="font-size: 0.7rem; color: #94a3b8; background: rgba(255,255,255,0.03); padding: 3px 8px; border-radius: 4px;"># ${doubt.category || 'Geral'}</span>
                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.3);"><i class="fas fa-comments"></i> ${doubt.comment_count || 0} respostas</div>
                </div>
            </div>
        `).join('');
    }

    function filterDoubts() {
        const term = document.getElementById('searchInput').value.toLowerCase();
        const cat = document.getElementById('categoryFilter').value;
        const stat = document.getElementById('statusFilter').value;
        const filtered = allDoubts.filter(d => 
            (d.title.toLowerCase().includes(term) || d.description.toLowerCase().includes(term) || d.full_name.toLowerCase().includes(term)) &&
            (!cat || d.category === cat) &&
            (!stat || d.status === stat)
        );
        renderDoubts(filtered);
    }

    document.getElementById('searchInput').addEventListener('input', filterDoubts);

    async function openDoubtDetail(id) {
        document.getElementById('doubtDetailModal').style.display = 'flex';
        const content = document.getElementById('doubtDetailContent');
        content.innerHTML = `<div style="padding: 4rem; text-align: center;"><i class="fas fa-circle-notch fa-spin fa-2x" style="color: #f7941d;"></i></div>`;
        
        try {
            const resp = await fetch(`../../interface_programacao/social/get_doubt_detail.php?doubt_id=${id}`);
            const data = await resp.json();
            if (data.success) {
                renderDetailModal(data.doubt, data.comments);
            }
        } catch (e) {
            content.innerHTML = `<div style="padding: 4rem; text-align: center; color: #ef4444;">Erro ao carregar detalhes.</div>`;
        }
    }

    function renderDetailModal(doubt, comments) {
        const content = document.getElementById('doubtDetailContent');
        content.innerHTML = `
            <div class="admin-modal-header">
                <div style="display: flex; align-items: center; gap: 1rem;">
                    <div style="width: 8px; height: 24px; background: var(--aksanti-orange); border-radius: 4px;"></div>
                    <h3>DETALHES DA INTERAÇÃO #${doubt.doubt_id}</h3>
                </div>
                <button onclick="closeDoubtDetailModal()" class="close-btn"><i class="fas fa-times"></i></button>
            </div>
            
            <div style="padding: 2.5rem; background: radial-gradient(circle at top right, rgba(247, 148, 29, 0.03), transparent);">
                <!-- Cabeçalho da Dúvida (Estilo Screenshot) -->
                <div style="margin-bottom: 2.5rem;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                        <h2 style="font-size: 1.8rem; font-weight: 900; color: #f7941d; margin: 0; line-height: 1.2; letter-spacing: -0.5px; max-width: 80%;">
                            ${doubt.title}
                        </h2>
                        <span style="background: rgba(247, 148, 29, 0.1); color: #f7941d; padding: 6px 14px; border-radius: 8px; font-weight: 900; font-size: 0.75rem; border: 1px solid rgba(247, 148, 29, 0.2); letter-spacing: 1px;">
                            ${doubt.status === 'open' ? 'ABERTA' : doubt.status.toUpperCase()}
                        </span>
                    </div>

                    <!-- User Box -->
                    <div style="display: flex; align-items: center; gap: 1rem; background: rgba(255,255,255,0.02); padding: 1rem 1.5rem; border-radius: 18px; border: 1px solid rgba(255,255,255,0.05); width: fit-content;">
                        <img src="${doubt.profile_pic || '../../recursos/images/default_profile.png'}" style="width: 48px; height: 48px; border-radius: 12px; border: 2px solid rgba(247, 148, 29, 0.5); object-fit: cover;">
                        <div>
                            <div style="font-weight: 800; color: #fff; font-size: 0.95rem;">${doubt.full_name}</div>
                            <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4); font-weight: 600;">
                                ${doubt.user_type_label} <span style="margin: 0 6px; opacity: 0.3;">•</span> ${new Date(doubt.created_at).toLocaleString()}
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Conteúdo -->
                <div style="background: rgba(13, 22, 40, 0.4); backdrop-filter: blur(10px); padding: 2rem; border-radius: 24px; border: 1px solid rgba(255,255,255,0.05); border-left: 4px solid #f7941d; margin-bottom: 3rem; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
                    <p style="color: rgba(255,255,255,0.8); line-height: 1.8; font-size: 1.05rem; white-space: pre-wrap; margin: 0; font-style: italic;">
                        "${doubt.description}"
                    </p>
                </div>

                <!-- Stats e Ações -->
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 3rem; padding-bottom: 1.5rem; border-bottom: 1px solid rgba(255,255,255,0.05);">
                    <div style="display: flex; gap: 2rem;">
                        <div style="text-align: center;">
                            <div style="font-size: 0.65rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase;">Respostas</div>
                            <div style="font-size: 1.25rem; font-weight: 900; color: #fff;">${comments.length}</div>
                        </div>
                        <div style="text-align: center;">
                            <div style="font-size: 0.65rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase;">Categoria</div>
                            <div style="font-size: 1rem; font-weight: 900; color: #f7941d;"># ${doubt.category || 'Geral'}</div>
                        </div>
                    </div>
                    <button onclick="deleteDoubt(${doubt.doubt_id})" class="btn-admin" style="background: rgba(239, 68, 68, 0.1); color: #ef4444; border: 1px solid rgba(239, 68, 68, 0.2);"><i class="fas fa-trash"></i> ELIMINAR</button>
                </div>

                <!-- Respostas -->
                <h4 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.8rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.75rem;">
                    <i class="fas fa-comments" style="color: #f7941d;"></i> Interações da Comunidade
                </h4>
                
                <div style="display: flex; flex-direction: column; gap: 1.25rem;">
                    ${comments.length === 0 ? 
                      '<div style="background: rgba(255,255,255,0.01); padding: 3rem; text-align: center; border-radius: 20px; color: rgba(255,255,255,0.2); border: 1px dashed rgba(255,255,255,0.1);">Aguardando primeira intervenção...</div>' : 
                      comments.map(c => `
                        <div style="background: rgba(255,255,255,0.02); padding: 1.5rem; border-radius: 20px; border: 1px solid rgba(255,255,255,0.03); transition: 0.3s; position: relative;">
                            <div style="display: flex; justify-content: space-between; margin-bottom: 1rem;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <div style="width: 32px; height: 32px; background: rgba(247, 148, 29, 0.1); border-radius: 8px; display: flex; align-items: center; justify-content: center; color: #f7941d; font-weight: 900; font-size: 0.7rem;">
                                        ${c.full_name.charAt(0)}
                                    </div>
                                    <span style="font-weight: 800; font-size: 0.85rem; color: #fff;">${c.full_name}</span>
                                </div>
                                <span style="font-size: 0.65rem; color: rgba(255,255,255,0.2); font-weight: 700;">${new Date(c.created_at).toLocaleDateString()}</span>
                            </div>
                            <p style="color: rgba(255,255,255,0.6); font-size: 0.95rem; line-height: 1.7; margin: 0; padding-left: 0.5rem;">${c.content}</p>
                        </div>
                      `).join('')
                    }
                </div>
            </div>
        `;
    }

    async function deleteDoubt(id) {
        const res = await Swal.fire({
            title: 'Confirmar Eliminação?',
            text: "Esta ação é irreversível e removerá todas as respostas associadas.",
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#ef4444',
            confirmButtonText: 'Sim, Eliminar',
            cancelButtonText: 'Cancelar',
            background: '#0f172a',
            color: '#fff'
        });

        if (res.isConfirmed) {
            const fd = new FormData();
            fd.append('doubt_id', id);
            const resp = await fetch('../../interface_programacao/social/delete_doubt.php', { method: 'POST', body: fd });
            const data = await resp.json();
            if (data.success) {
                Swal.fire({ icon: 'success', title: 'Sucesso', background: '#0f172a', color: '#fff' });
                closeDoubtDetailModal();
                loadDoubts();
            }
        }
    }

    function openDoubtModal() { document.getElementById('doubtModal').style.display = 'flex'; }
    function closeDoubtModal() { document.getElementById('doubtModal').style.display = 'none'; }
    function closeDoubtDetailModal() { document.getElementById('doubtDetailModal').style.display = 'none'; }

    async function submitDoubt(e) {
        e.preventDefault();
        const fd = new FormData(e.target);
        const resp = await fetch('../../interface_programacao/social/post_doubt.php', { method: 'POST', body: fd });
        const data = await resp.json();
        if (data.success) {
            Swal.fire({ icon: 'success', title: 'Publicado!', background: '#0f172a', color: '#fff' });
            closeDoubtModal();
            loadDoubts();
        }
    }

    document.addEventListener('DOMContentLoaded', loadDoubts);
    </script>
</body>
</html>



