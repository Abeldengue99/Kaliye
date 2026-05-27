<?php
// admin/announcements.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/logger.php';

if (!isAdmin() || !hasPermission('ads')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Handle Form Submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['message'])) {
    $message = $_POST['message'];
    $type = $_POST['type'];
    
    // Deactivate previous active announcements if needed, or allow multiple. Let's allow multiple for now.
    $stmt = $db->prepare("INSERT INTO announcements (message, type) VALUES (?, ?)");
    $stmt->execute([$message, $type]);
    
    logAdminAction($db, $_SESSION['user_id'], 'Post Announcement', "Type: $type");
    $success = "Anúncio publicado!";
}

// Handle Delete
if (isset($_GET['delete'])) {
    $stmt = $db->prepare("DELETE FROM announcements WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    logAdminAction($db, $_SESSION['user_id'], 'Delete Announcement', "ID: " . $_GET['delete']);
    header("Location: announcements.php");
    exit();
}

$announcements = $db->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Anúncios Globais - KALIYE Admin</title>
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
                <h1>Comunicados Globais</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Difusão de mensagens e alertas críticos para toda a infraestrutura do ecossistema.</p>
            </div>
            <div style="background: rgba(247, 148, 29, 0.1); border: 1px solid rgba(247, 148, 29, 0.2); padding: 0.6rem 1.25rem; border-radius: 12px; font-size: 0.75rem; color: #f7941d; font-weight: 800; display: flex; align-items: center; gap: 0.5rem;">
                <i class="fas fa-broadcast-tower"></i> BROADCAST ON
            </div>
        </header>

        <div style="display: grid; grid-template-columns: 350px 1fr; gap: 2rem; align-items: start;">
            <!-- Form Section -->
            <div class="admin-card-premium" style="padding: 1.5rem;">
                <h4 style="color: #fff; font-weight: 800; text-transform: uppercase; letter-spacing: 1px; font-size: 0.75rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 0.5rem;">
                    <i class="fas fa-edit" style="color: #f7941d;"></i> Redigir Comunicado
                </h4>
                <form method="POST">
                    <div class="input-group-premium" style="margin-bottom: 1.25rem;">
                        <label>CONTEÚDO DA MENSAGEM</label>
                        <textarea name="message" required style="height: 120px;" placeholder="Digite o anúncio aqui..."></textarea>
                    </div>
                    <div class="input-group-premium" style="margin-bottom: 1.5rem;">
                        <label>NÍVEL DE PRIORIDADE</label>
                        <select name="type">
                            <option value="info">INFORMAÇÃO (AZUL)</option>
                            <option value="alert">ALERTA CRÍTICO (VERMELHO)</option>
                            <option value="success">CONCLUÍDO (VERDE)</option>
                        </select>
                    </div>
                    <button type="submit" class="btn-admin btn-admin-primary" style="width: 100%;">
                        <i class="fas fa-paper-plane"></i> DISPARAR ANÚNCIO
                    </button>
                </form>
            </div>

            <!-- List Section -->
            <div class="admin-card-premium" style="padding: 0;">
                <div class="table-container">
                    <table class="aksanti-table">
                        <thead>
                            <tr>
                                <th>Mensagem Difundida</th>
                                <th>Prioridade</th>
                                <th>Data / Hora</th>
                                <th>Acções</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if(empty($announcements)): ?>
                                <tr><td colspan="4" style="padding: 4rem; text-align: center; color: rgba(255,255,255,0.2);">Nenhum anúncio ativo.</td></tr>
                            <?php endif; ?>
                            <?php foreach($announcements as $a): ?>
                            <tr>
                                <td>
                                    <div style="font-size: 0.85rem; color: rgba(255,255,255,0.7); line-height: 1.5; font-family: 'Inter', sans-serif;">
                                        <?= htmlspecialchars($a['message']) ?>
                                    </div>
                                </td>
                                <td>
                                    <?php 
                                        $bgColor = '#60a5fa15'; $color = '#60a5fa'; $label = 'INFO';
                                        if($a['type'] == 'alert') { $bgColor = '#f8717115'; $color = '#f87171'; $label = 'ALERTA'; }
                                        if($a['type'] == 'success') { $bgColor = '#34d39915'; $color = '#34d399'; $label = 'SUCESSO'; }
                                    ?>
                                    <span style="font-size: 0.65rem; background: <?= $bgColor ?>; color: <?= $color ?>; padding: 4px 10px; border-radius: 6px; font-weight: 900; border: 1px solid <?= $color ?>25;">
                                        <?= $label ?>
                                    </span>
                                </td>
                                <td>
                                    <div style="font-weight: 700; font-size: 0.8rem; color: #fff;"><?= date('d M, Y', strtotime($a['created_at'])) ?></div>
                                    <div style="font-size: 0.65rem; color: rgba(255,255,255,0.3);"><?= date('H:i', strtotime($a['created_at'])) ?></div>
                                </td>
                                <td>
                                    <button onclick="confirmDelete(<?= $a['id'] ?>)" class="btn-action" title="Eliminar Anúncio" style="color: #f87171;">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </main>

    <script>
    function confirmDelete(id) {
        Swal.fire({
            title: 'Remover Anúncio?',
            text: "Esta acção não poderá ser revertida.",
            icon: 'warning',
            showCancelButton: true,
            background: '#050a15',
            color: '#fff',
            confirmButtonColor: '#ef4444',
            cancelButtonColor: 'rgba(255,255,255,0.1)',
            confirmButtonText: 'SIM, REMOVER',
            cancelButtonText: 'CANCELAR'
        }).then((result) => {
            if (result.isConfirmed) {
                window.location.href = '?delete=' + id;
            }
        });
    }
    </script>
</body>
</html>







