<?php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/SimpleMailer.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// Lógica de desbloqueio
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'], $_POST['request_id'])) {
    if ($_POST['action'] === 'unlock') {
        $req_id = (int)$_POST['request_id'];
        
        // 1. Marcar pedido como resolvido
        $stmt = $db->prepare("UPDATE unlock_requests SET status = 'resolved' WHERE id = ? RETURNING ip_address, email, name");
        $stmt->execute([$req_id]);
        $req = $stmt->fetch();
        
        if ($req) {
            require_once '../../inclusoes/RateLimiter.php';
            $admin_id = $_SESSION['user_id'] ?? 1;

            // 2. Limpar bloqueios usando a classe RateLimiter
            RateLimiter::unblock($db, 'login', $req['ip_address'], $admin_id, 'Desbloqueio efetuado pelo Admin via painel');
            RateLimiter::unblock($db, 'login', $req['email'], $admin_id, 'Desbloqueio efetuado pelo Admin via painel');
            
            // 4. Enviar email de confirmação ao utilizador (Sem links, conforme pedido)
            $mailer = new SimpleMailer();
            $user_name = $req['name'] ? $req['name'] : 'Utilizador';
            $subject = "KALIYE: A sua conta foi desbloqueada";
            $body = "
                <h3>Olá " . htmlspecialchars($user_name) . "!</h3>
                <p>A nossa equipa técnica analisou o seu pedido e procedeu com sucesso ao desbloqueio da sua conta e do seu IP.</p>
                <p>O acesso à plataforma KALIYE foi restaurado e já pode voltar a utilizá-la normalmente.</p>
                <br>
                <p>Obrigado pela sua paciência e colaboração.</p>
                <p>Com os melhores cumprimentos,</p>
                <p><strong>Equipa de Segurança - KALIYE</strong></p>
            ";
            $mailer->send($req['email'], $user_name, $subject, $body);

            $_SESSION['flash_msg'] = "Utilizador desbloqueado com sucesso! E-mail de confirmação enviado.";
        }
    }
    header("Location: unlock_requests.php");
    exit();
}

// Lógica de rejeição
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reject') {
    $req_id = (int)$_POST['request_id'];
    $stmt = $db->prepare("UPDATE unlock_requests SET status = 'rejected' WHERE id = ?");
    $stmt->execute([$req_id]);
    $_SESSION['flash_msg'] = "Pedido rejeitado.";
    header("Location: unlock_requests.php");
    exit();
}

$status_filter = $_GET['status'] ?? 'pending';
$where = $status_filter ? "WHERE status = " . $db->quote($status_filter) : "";
$requests = $db->query("SELECT * FROM unlock_requests $where ORDER BY created_at DESC")->fetchAll();
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pedidos de Desbloqueio - KALIYE Admin</title>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css">
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
                <h1>Pedidos de Desbloqueio</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Contas suspensas por segurança (Hard Lock).</p>
            </div>
            
            <div style="display: flex; gap: 0.5rem; background: rgba(255,255,255,0.03); padding: 0.4rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05);">
                <a href="unlock_requests.php?status=pending" class="btn-admin <?= $status_filter == 'pending' ? 'btn-admin-primary' : '' ?>" style="background: <?= $status_filter == 'pending' ? '' : 'transparent' ?>; color: <?= $status_filter == 'pending' ? '' : '#94a3b8' ?>;">Pendentes</a>
                <a href="unlock_requests.php?status=resolved" class="btn-admin <?= $status_filter == 'resolved' ? 'btn-admin-primary' : '' ?>" style="background: <?= $status_filter == 'resolved' ? '' : 'transparent' ?>; color: <?= $status_filter == 'resolved' ? '' : '#94a3b8' ?>;">Resolvidos</a>
                <a href="unlock_requests.php?status=rejected" class="btn-admin <?= $status_filter == 'rejected' ? 'btn-admin-primary' : '' ?>" style="background: <?= $status_filter == 'rejected' ? '' : 'transparent' ?>; color: <?= $status_filter == 'rejected' ? '' : '#94a3b8' ?>;">Rejeitados</a>
            </div>
        </header>

        <?php if(isset($_SESSION['flash_msg'])): ?>
            <div style="background: rgba(16, 185, 129, 0.1); border: 1px solid rgba(16, 185, 129, 0.3); color: #10b981; padding: 1rem; border-radius: 12px; margin-bottom: 1.5rem; font-weight: 600;">
                <i class="fas fa-check-circle"></i> <?= $_SESSION['flash_msg'] ?>
            </div>
            <?php unset($_SESSION['flash_msg']); ?>
        <?php endif; ?>

        <div class="admin-card-premium" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Data</th>
                            <th>Utilizador</th>
                            <th>Motivo</th>
                            <th>IP</th>
                            <th>Ref</th>
                            <th>Estado</th>
                            <th>Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($requests)): ?>
                            <tr><td colspan="7" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2); font-weight: 500;">Nenhum pedido encontrado.</td></tr>
                        <?php endif; ?>
                        <?php foreach($requests as $req): ?>
                        <tr>
                            <td style="font-size: 0.85rem; color: rgba(255,255,255,0.6);"><?= date('d/m/Y H:i', strtotime($req['created_at'])) ?></td>
                            <td>
                                <strong style="color: #fff; display: block; font-size: 0.9rem;"><?= htmlspecialchars($req['name'] ?? 'Não informado') ?></strong>
                                <span style="font-size: 0.75rem; color: var(--cor-sub);"><?= htmlspecialchars($req['email']) ?></span>
                            </td>
                            <td style="max-width: 250px; font-size: 0.8rem; color: rgba(255,255,255,0.7); overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="<?= htmlspecialchars($req['reason'] ?? '') ?>">
                                <?= htmlspecialchars($req['reason'] ?? 'Sem justificação') ?>
                            </td>
                            <td style="font-family: monospace; color: var(--cor-sub);"><?= htmlspecialchars($req['ip_address']) ?></td>
                            <td><span style="background: rgba(255,255,255,0.05); padding: 0.2rem 0.5rem; border-radius: 6px; font-size: 0.75rem;">#<?= htmlspecialchars($req['ref_code']) ?></span></td>
                            <td>
                                <?php if($req['status'] == 'pending'): ?>
                                    <span style="color: #f7941d; font-weight: 700; font-size: 0.8rem;"><i class="fas fa-clock"></i> Pendente</span>
                                <?php elseif($req['status'] == 'resolved'): ?>
                                    <span style="color: #10b981; font-weight: 700; font-size: 0.8rem;"><i class="fas fa-check"></i> Desbloqueado</span>
                                <?php else: ?>
                                    <span style="color: #ef4444; font-weight: 700; font-size: 0.8rem;"><i class="fas fa-times"></i> Rejeitado</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if($req['status'] == 'pending'): ?>
                                    <form method="POST" id="form-unlock-<?= $req['id'] ?>" style="display: inline-block;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="action" value="unlock">
                                        <button type="button" class="btn-action success" title="Desbloquear" onclick="confirmAction('form-unlock-<?= $req['id'] ?>', 'Desbloquear Utilizador', 'Tem a certeza que deseja desbloquear este utilizador e o seu IP?', 'success')">
                                            <i class="fas fa-unlock"></i>
                                        </button>
                                    </form>
                                    <form method="POST" id="form-reject-<?= $req['id'] ?>" style="display: inline-block;">
                                        <input type="hidden" name="request_id" value="<?= $req['id'] ?>">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="button" class="btn-action danger" title="Rejeitar" onclick="confirmAction('form-reject-<?= $req['id'] ?>', 'Rejeitar Pedido', 'Tem a certeza que deseja rejeitar este pedido de desbloqueio?', 'warning')">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </form>
                                <?php else: ?>
                                    <span style="color: rgba(255,255,255,0.2); font-size: 0.8rem;">---</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    function confirmAction(formId, title, text, icon) {
        Swal.fire({
            title: title,
            text: text,
            icon: icon,
            showCancelButton: true,
            confirmButtonColor: icon === 'success' ? '#10b981' : '#ef4444',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            confirmButtonText: 'Sim, confirmar!',
            cancelButtonText: 'Cancelar',
            background: '#0d1628',
            color: '#fff'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById(formId).submit();
            }
        })
    }
    </script>
</body>
</html>
