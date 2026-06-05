<?php
// administracao/salas_vip/index.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('moderation')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Fetch all VIP chats
$chats = $db->query("SELECT c.*, (SELECT COUNT(*) FROM vip_chat_participants WHERE chat_id = c.id) as participants_count FROM vip_chats c ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);

// Fetch all users for selection (for adding participants later, or initially)
$users_query = $db->query("SELECT user_id, full_name, user_type FROM users ORDER BY user_type, full_name ASC")->fetchAll(PDO::FETCH_ASSOC);

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão de Salas VIP - KALIYE Admin</title>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
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
    <style>
        .user-list-scroll {
            max-height: 200px;
            overflow-y: auto;
            border: 1px solid rgba(255,255,255,0.1);
            border-radius: 8px;
            padding: 10px;
            background: rgba(0,0,0,0.2);
            margin-bottom: 15px;
        }
        .user-item {
            display: flex;
            align-items: center;
            gap: 10px;
            padding: 8px;
            border-bottom: 1px solid rgba(255,255,255,0.05);
        }
        .user-item:last-child {
            border-bottom: none;
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Salas VIP (Networking)</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Crie grupos restritos e selecione estudantes, mentores e investidores.</p>
            </div>
            <button onclick="openVipModal()" class="btn-admin btn-admin-primary">
                <i class="fas fa-plus"></i> NOVA SALA VIP
            </button>
        </header>

        <div class="admin-card-premium" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Sala</th>
                            <th>Membros</th>
                            <th>Data de Criação</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if(empty($chats)): ?>
                            <tr><td colspan="5" style="text-align: center; color: rgba(255,255,255,0.5);">Nenhuma sala VIP criada.</td></tr>
                        <?php else: ?>
                            <?php foreach($chats as $c): ?>
                            <tr>
                                <td>#<?= $c['id'] ?></td>
                                <td>
                                    <div style="font-weight: 800; color: #fff; font-size: 0.9rem;"><?= htmlspecialchars($c['title']) ?></div>
                                    <div style="font-size: 0.75rem; color: rgba(255,255,255,0.5);"><?= htmlspecialchars($c['description']) ?></div>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: #f7941d;"><?= $c['participants_count'] ?> Utilizadores</span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 0.8rem; color: #fff;"><?= date('d M, Y', strtotime($c['created_at'])) ?></div>
                                </td>
                                <td>
                                    <div class="action-buttons">
                                        <button onclick="window.location.href='../../paginas/comunicacao/salas_vip.php?room_id=<?= $c['id'] ?>'" class="btn-action info" title="Entrar na Sala" style="color: #34d399;"><i class="fas fa-comments"></i></button>
                                        <button onclick="manageUsers(<?= $c['id'] ?>)" class="btn-action approve" title="Gerir Membros"><i class="fas fa-users"></i></button>
                                        <button onclick="deleteRoom(<?= $c['id'] ?>)" class="btn-action reject" title="Eliminar Sala" style="color: #ef4444;"><i class="fas fa-trash"></i></button>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <!-- Modal Nova Sala VIP -->
    <div id="vipModal" class="admin-modal-overlay" style="display:none;">
        <div class="admin-modal-content" style="max-width: 500px; background: #0d1628; border: 1px solid rgba(255,255,255,0.08);">
            <div class="admin-modal-header">
                <h3 style="color: #fff;">Criar Nova Sala VIP</h3>
                <button onclick="closeVipModal()" class="close-btn">&times;</button>
            </div>
            <form id="createVipForm" style="padding: 2rem; max-height: 70vh; overflow-y: auto;">
                <div class="input-group-premium" style="margin-bottom: 1.25rem;">
                    <label style="color: rgba(255,255,255,0.5);">NOME DA SALA</label>
                    <input type="text" id="vip_title" required placeholder="Ex: Comité de Investimento" style="background: rgba(255,255,255,0.03); color: #fff; border: 1px solid rgba(255,255,255,0.1); width: 100%; padding: 10px; border-radius: 8px;">
                </div>
                <div class="input-group-premium" style="margin-bottom: 1.25rem;">
                    <label style="color: rgba(255,255,255,0.5);">DESCRIÇÃO (Opcional)</label>
                    <input type="text" id="vip_desc" placeholder="Tópico da sala..." style="background: rgba(255,255,255,0.03); color: #fff; border: 1px solid rgba(255,255,255,0.1); width: 100%; padding: 10px; border-radius: 8px;">
                </div>
                
                <div style="display: flex; gap: 1rem; margin-top: 20px;">
                    <button type="button" onclick="closeVipModal()" class="btn-admin" style="flex: 1; border: 1px solid rgba(255,255,255,0.1); color: #fff; background: transparent;">CANCELAR</button>
                    <button type="submit" class="btn-admin btn-admin-primary" style="flex: 2;">CRIAR SALA</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Modal Gerir Utilizadores -->
    <div id="manageUsersModal" class="admin-modal-overlay" style="display:none;">
        <div class="admin-modal-content" style="max-width: 500px; background: #0d1628; border: 1px solid rgba(255,255,255,0.08);">
            <div class="admin-modal-header">
                <h3 style="color: #fff;">Gerir Participantes</h3>
                <button onclick="closeManageModal()" class="close-btn">&times;</button>
            </div>
            <div style="padding: 2rem;">
                <input type="hidden" id="current_manage_chat_id">
                
                <div style="margin-bottom: 20px;">
                    <h4 style="color: #f7941d; margin-bottom: 10px;">Membros Atuais:</h4>
                    <div id="current_members_list" style="max-height: 150px; overflow-y: auto; background: rgba(0,0,0,0.2); border-radius: 8px; border: 1px solid rgba(255,255,255,0.05); padding: 10px;">
                        <!-- JS inject -->
                    </div>
                </div>

                <hr style="border-color: rgba(255,255,255,0.05); margin: 20px 0;">

                <label style="color: rgba(255,255,255,0.5); display:block; margin-bottom: 5px;">Selecione o Utilizador a Adicionar</label>
                <select id="user_select" style="background: #0d1628; color: #fff; border: 1px solid rgba(255,255,255,0.1); width: 100%; padding: 10px; border-radius: 8px; margin-bottom: 15px;">
                    <option value="" style="background: #0d1628; color: #fff;">-- Selecionar Utilizador --</option>
                    <?php foreach($users_query as $u): ?>
                        <option value="<?= $u['user_id'] ?>" style="background: #0d1628; color: #fff;">[<?= strtoupper($u['user_type']) ?>] <?= htmlspecialchars($u['full_name']) ?></option>
                    <?php endforeach; ?>
                </select>
                
                <button onclick="addUserToRoom()" class="btn-admin btn-admin-primary" style="width: 100%;">
                    <i class="fas fa-user-plus"></i> Adicionar à Sala
                </button>
            </div>
        </div>
    </div>

    <script>
        function openVipModal() {
            document.getElementById('vipModal').style.display = 'flex';
        }
        function closeVipModal() {
            document.getElementById('vipModal').style.display = 'none';
        }

        function manageUsers(chat_id) {
            document.getElementById('current_manage_chat_id').value = chat_id;
            loadCurrentMembers(chat_id);
            document.getElementById('manageUsersModal').style.display = 'flex';
        }
        function closeManageModal() {
            document.getElementById('manageUsersModal').style.display = 'none';
        }

        function loadCurrentMembers(chat_id) {
            const list = document.getElementById('current_members_list');
            list.innerHTML = '<span style="color:rgba(255,255,255,0.5);">A carregar...</span>';
            fetch('../../interface_programacao/vip_chat/get_room_participants.php?chat_id=' + chat_id)
            .then(r => r.json())
            .then(data => {
                if(data.success && data.members.length > 0) {
                    list.innerHTML = '';
                    data.members.forEach(m => {
                        list.innerHTML += `
                            <div style="display:flex; justify-content:space-between; align-items:center; padding: 5px 0; border-bottom: 1px solid rgba(255,255,255,0.05);">
                                <span style="color:#fff; font-size:0.85rem;">[${m.user_type}] ${m.full_name}</span>
                                <button onclick="removeUserFromRoom(${chat_id}, ${m.user_id})" style="background:transparent; border:none; color:#ef4444; cursor:pointer;"><i class="fas fa-times"></i></button>
                            </div>
                        `;
                    });
                } else {
                    list.innerHTML = '<span style="color:rgba(255,255,255,0.5);">Sem membros.</span>';
                }
            });
        }

        function deleteRoom(chat_id) {
            Swal.fire({
                title: 'Eliminar Sala?',
                text: 'A sala e todas as mensagens serão eliminadas para sempre!',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Sim, Eliminar',
                cancelButtonText: 'Cancelar',
                background: '#0f172a', color: '#fff'
            }).then(res => {
                if(res.isConfirmed) {
                    fetch('../../interface_programacao/vip_chat/delete_room.php', {
                        method: 'POST',
                        headers: {'Content-Type': 'application/json'},
                        body: JSON.stringify({chat_id: chat_id})
                    }).then(r=>r.json()).then(data=>{
                        if(data.success) location.reload();
                        else Swal.fire('Erro', data.error, 'error');
                    });
                }
            });
        }

        document.getElementById('createVipForm').addEventListener('submit', function(e) {
            e.preventDefault();
            const title = document.getElementById('vip_title').value;
            const desc = document.getElementById('vip_desc').value;
            
            fetch('../../interface_programacao/vip_chat/create_room.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({title: title, description: desc})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire('Sucesso', 'Sala VIP criada!', 'success').then(()=>location.reload());
                } else {
                    Swal.fire('Erro', data.error, 'error');
                }
            });
        });

        function addUserToRoom() {
            const chat_id = document.getElementById('current_manage_chat_id').value;
            const user_id = document.getElementById('user_select').value;
            if(!user_id) return;
            
            fetch('../../interface_programacao/vip_chat/manage_participants.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({chat_id: chat_id, user_id: user_id, action: 'add'})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    Swal.fire({title:'Adicionado', text:'Utilizador adicionado.', icon:'success', timer: 1500, showConfirmButton:false});
                    loadCurrentMembers(chat_id);
                } else {
                    Swal.fire('Erro', data.error, 'error');
                }
            });
        }

        function removeUserFromRoom(chat_id, user_id) {
            fetch('../../interface_programacao/vip_chat/manage_participants.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({chat_id: chat_id, user_id: user_id, action: 'remove'})
            })
            .then(res => res.json())
            .then(data => {
                if(data.success) {
                    loadCurrentMembers(chat_id);
                }
            });
        }
    </script>
</body>
</html>
