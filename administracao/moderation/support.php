<?php
/**
 * admin/support.php - Support Center
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('support')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// --- Data Fetching ---
$params = [];
$where = [];

if (!empty($_GET['search'])) {
    $searchTerm = '%' . $_GET['search'] . '%';
    $where[] = "(m.message LIKE ? OR u.full_name LIKE ? OR u.email LIKE ?)";
    array_push($params, $searchTerm, $searchTerm, $searchTerm);
}

if (isset($_GET['status'])) {
    if ($_GET['status'] === 'unread') { $where[] = "CAST(m.is_read AS INTEGER) = 0"; }
    elseif ($_GET['status'] === 'read') { $where[] = "CAST(m.is_read AS INTEGER) = 1"; }
}

$where_sql = count($where) ? "WHERE " . implode(" AND ", $where) : "";

$query = "SELECT m.*, u.full_name, u.email, u.user_type, u.profile_pic 
          FROM support_messages m 
          LEFT JOIN users u ON m.user_id = u.user_id 
          $where_sql 
          ORDER BY m.created_at DESC";

$stmt = $db->prepare($query);
$stmt->execute($params);
$messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Stats
$total_unread = $db->query("SELECT COUNT(*) FROM support_messages WHERE CAST(is_read AS INTEGER) = 0")->fetchColumn();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centro de Suporte - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Centro de Suporte</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Gestão estratégica de incidências e apoio ao ecossistema.</p>
            </div>
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <?php if($total_unread > 0): ?>
                    <div style="background: rgba(239, 68, 68, 0.1); border: 1px solid rgba(239, 68, 68, 0.2); padding: 0.6rem 1.25rem; border-radius: 12px; font-size: 0.75rem; color: #f87171; font-weight: 800; display: flex; align-items: center; gap: 0.5rem; animation: pulse 2s infinite;">
                        <i class="fas fa-satellite-dish"></i> <?= $total_unread ?> PENDENTES
                    </div>
                <?php endif; ?>
                <a href="export_support.php?format=view" target="_blank" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #fff; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; border-radius: 10px; padding: 0.75rem 1.5rem; font-weight: 600; font-size: 0.85rem;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="export_support.php?format=csv" class="btn-admin btn-admin-primary" style="padding: 0.75rem 1.5rem; text-decoration: none;">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
            </div>
        </header>

        <!-- Filtros Rápidos -->
        <div class="admin-card-premium" style="margin-bottom: 2rem; padding: 1.25rem;">
            <form method="GET" style="display: flex; gap: 1rem; align-items: center; flex-wrap: wrap;">
                <div style="flex: 2; min-width: 300px; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2);"></i>
                    <input type="text" name="search" value="<?= htmlspecialchars($_GET['search'] ?? '') ?>" placeholder="Pesquisar por autor, email ou conteúdo..." 
                           style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.8rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: #fff; outline: none; transition: 0.3s; font-size: 0.9rem;">
                </div>
                <div style="flex: 1; min-width: 150px;">
                    <select name="status" onchange="this.form.submit()" style="width: 100%; padding: 0.75rem 1.5rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: #fff; outline: none; cursor: pointer;">
                        <option value="">Todas Mensagens</option>
                        <option value="unread" <?= (isset($_GET['status']) && $_GET['status'] == 'unread') ? 'selected' : '' ?>>Não Lidas (Inbox)</option>
                        <option value="read" <?= (isset($_GET['status']) && $_GET['status'] == 'read') ? 'selected' : '' ?>>Arquivadas / Lidas</option>
                    </select>
                </div>
                <button type="submit" class="btn-admin btn-admin-primary" style="padding: 0.75rem 1.5rem;">APLICAR</button>
            </form>
        </div>

        <!-- Tabela de Mensagens -->
        <div class="admin-card-premium" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Utilizador</th>
                            <th>Mensagem</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($messages)): ?>
                            <tr><td colspan="4" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2); font-weight: 500;">A caixa de entrada está vazia.</td></tr>
                        <?php endif; ?>
                        <?php foreach($messages as $msg): ?>
                        <tr class="<?= !$msg['is_read'] ? 'unread-row' : '' ?>" style="<?= !$msg['is_read'] ? 'background: rgba(247, 148, 29, 0.02);' : '' ?>">
                            <td style="width: 140px;">
                                <div style="font-weight: 800; font-size: 0.8rem; color: #fff;"><?= date('d M, Y', strtotime($msg['created_at'])) ?></div>
                                <div style="font-size: 0.65rem; color: rgba(255,255,255,0.3); font-weight: 700;"><?= date('H:i', strtotime($msg['created_at'])) ?>h</div>
                            </td>
                            <td style="width: 280px;">
                                <div style="display: flex; align-items: center; gap: 1rem;">
                                    <div style="position: relative;">
                                        <img src="../<?= $msg['profile_pic'] ?: 'recursos/images/default_profile.png' ?>" style="width: 42px; height: 42px; border-radius: 12px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                        <?php if(!$msg['is_read']): ?><span style="position: absolute; -1px; -1px; width: 12px; height: 12px; background: #f7941d; border-radius: 50%; border: 2px solid #050a15;"></span><?php endif; ?>
                                    </div>
                                    <div>
                                        <div style="font-weight: 800; color: #fff; font-size: 0.9rem;"><?= htmlspecialchars($msg['full_name'] ?: 'Visitante') ?></div>
                                        <div style="font-size: 0.7rem; color: rgba(255,255,255,0.4);"><?= htmlspecialchars($msg['email'] ?: 'No Email') ?></div>
                                    </div>
                                </div>
                            </td>
                            <td>
                                <div style="font-size: 0.9rem; line-height: 1.6; color: rgba(255,255,255,0.6); max-width: 600px; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;" title="<?= htmlspecialchars($msg['message']) ?>">
                                    <?= nl2br(htmlspecialchars($msg['message'])) ?>
                                </div>
                            </td>
                            <td style="width: 150px;">
                                <div class="action-buttons">
                                    <?php if($msg['user_id']): ?>
                                        <button onclick="replySupport(<?= $msg['user_id'] ?>, '<?= addslashes($msg['full_name']) ?>')" class="btn-action" title="Responder"><i class="fas fa-reply"></i></button>
                                    <?php endif; ?>
                                    <button onclick="markAsRead(<?= $msg['id'] ?>)" class="btn-action <?= $msg['is_read'] ? '' : 'success' ?>" title="<?= $msg['is_read'] ? 'Lida' : 'Marcar como lida' ?>">
                                        <i class="fas <?= $msg['is_read'] ? 'fa-check-double' : 'fa-check' ?>"></i>
                                    </button>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function markAsRead(id) {
        const formData = new FormData();
        formData.append('msg_id', id);
        fetch('../../interface_programacao/admin/admin_mark_support_read.php', { method: 'POST', body: formData })
        .then(res => res.json())
        .then(data => {
            if(data.success) {
                Swal.fire({ icon: 'success', title: 'Arquivado', background: '#0f172a', color: '#fff', timer: 1000, showConfirmButton: false });
                setTimeout(() => location.reload(), 1000);
            }
        });
    }

    function replySupport(userId, name) {
        Swal.fire({
            title: 'Responder a ' + name,
            input: 'textarea',
            inputPlaceholder: 'Escreva a orientação para o utilizador...',
            showCancelButton: true,
            confirmButtonText: 'ENVIAR RESPOSTA',
            confirmButtonColor: '#f7941d',
            cancelButtonText: 'CANCELAR',
            background: '#050a15',
            color: '#fff',
            customClass: {
                input: 'admin-swal-input'
            }
        }).then(res => {
            if(res.isConfirmed && res.value) {
                const formData = new FormData();
                formData.append('receiver_id', userId);
                formData.append('message', res.value);
                fetch('../../interface_programacao/social/send_message.php', { method: 'POST', body: formData })
                .then(res => res.json())
                .then(data => {
                    if(data.success) Swal.fire({ icon: 'success', title: 'Mensagem Enviada!', background: '#0f172a', color: '#fff' });
                });
            }
        });
    }
    </script>

    <style>
    .admin-swal-input { background: rgba(0,0,0,0.3) !important; color: #fff !important; border: 1px solid rgba(255,255,255,0.1) !important; border-radius: 12px !important; }
    @keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.5; } 100% { opacity: 1; } }
    </style>
</body>
</html>




