<?php
/**
 * administracao/newsletter/subscribers.php
 * Painel de Gestão de Subscritores da Newsletter
 */
session_start();
$base_url = '../../';

// Proteção de Acesso Admin
require_once $base_url . 'inclusoes/auth_check.php';
if (!isAdmin() || !hasPermission('ads')) {
    header("Location: " . $base_url . "index.php");
    exit();
}

require_once $base_url . 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

// Lógica de Eliminação
if (isset($_GET['delete_id'])) {
    if (!verifyCSRFToken($_GET['csrf_token'] ?? '')) {
        header("Location: subscribers.php?error=csrf");
        exit();
    }
    $del_id = (int)$_GET['delete_id'];
    $stmt = $db->prepare("DELETE FROM newsletter_subscribers WHERE id = ?");
    $stmt->execute([$del_id]);
    header("Location: subscribers.php?success=1");
    exit();
}

// Buscar Subscritores
$subscribers = $db->query("SELECT * FROM newsletter_subscribers ORDER BY subscribed_at DESC")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <title>Gestão Newsletter | KALIYE Admin</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/dashboard-aksanti-elite.css">
    <style>
        .admin-page-header {
            margin-bottom: 2rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        .subscriber-table-card {
            background: var(--bg-1);
            border-radius: 20px;
            border: 1px solid var(--surface-10);
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
        }
        .elite-table {
            width: 100%;
            border-collapse: collapse;
            color: #fff;
        }
        .elite-table th {
            background: var(--surface-5);
            padding: 1.2rem;
            text-align: left;
            font-size: 0.75rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--elite-orange);
        }
        .elite-table td {
            padding: 1.2rem;
            border-bottom: 1px solid var(--surface-5);
            font-size: 0.9rem;
        }
        .elite-table tr:hover {
            background: rgba(255,255,255,0.02);
        }
        .btn-delete-subscriber {
            color: #ef4444;
            background: rgba(239, 68, 68, 0.1);
            border: 1px solid rgba(239, 68, 68, 0.2);
            padding: 6px 12px;
            border-radius: 8px;
            cursor: pointer;
            transition: 0.3s;
        }
        .btn-delete-subscriber:hover {
            background: #ef4444;
            color: #fff;
        }
        .empty-state {
            padding: 4rem;
            text-align: center;
            color: var(--surface-40);
        }
    </style>
</head>
<body class="admin-body">
    <?php include $base_url . 'inclusoes/cabecalho.php'; ?>

    <main class="main-content" style="padding-top: 100px;">
        <div class="container-secao">
            <div class="admin-page-header">
                <div>
                    <h1 style="color:#fff; font-family:'Outfit';">Gestão de Newsletter</h1>
                    <p style="color:var(--surface-60);">Visualiza e gere os contactos subscritos na plataforma.</p>
                </div>
                <div class="header-actions">
                    <span class="elite-badge-pulse" style="position:relative; top:0; right:0; background:var(--elite-orange); padding: 8px 15px; height:auto; border-radius:12px;">
                        <?php echo count($subscribers); ?> Subscritores
                    </span>
                </div>
            </div>

            <div class="subscriber-table-card">
                <?php if (count($subscribers) > 0): ?>
                <table class="elite-table">
                    <thead>
                        <tr>
                            <th>Nome</th>
                            <th>E-mail</th>
                            <th>Data de Subscrição</th>
                            <th style="text-align:right;">Ações</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($subscribers as $sub): ?>
                        <tr>
                            <td><strong><?php echo htmlspecialchars($sub['name'] ?: 'N/A'); ?></strong></td>
                            <td style="color: var(--elite-orange);"><?php echo htmlspecialchars($sub['email']); ?></td>
                            <td><?php echo date('d/m/Y H:i', strtotime($sub['subscribed_at'])); ?></td>
                            <td style="text-align:right;">
                                <a href="javascript:void(0)" onclick="confirmDelete(<?php echo $sub['id']; ?>)" class="btn-delete-subscriber">
                                    <i class="fas fa-trash-alt"></i> Eliminar
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
                <?php else: ?>
                <div class="empty-state">
                    <i class="fas fa-mailbox fa-3x" style="margin-bottom:1rem; opacity:0.3;"></i>
                    <p>Ainda não existem subscritores na newsletter.</p>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </main>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function confirmDelete(id) {
            Swal.fire({
                title: 'Tem a certeza?',
                text: "Esta ação irá remover permanentemente o contacto da newsletter.",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#ef4444',
                cancelButtonColor: '#1e293b',
                confirmButtonText: 'Sim, eliminar!',
                cancelButtonText: 'Cancelar',
                background: '#0d1628',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    window.location.href = 'subscribers.php?delete_id=' + id + '&csrf_token=<?php echo urlencode(generateCSRFToken()); ?>';
                }
            })
        }

        <?php if (isset($_GET['success'])): ?>
        Swal.fire({
            icon: 'success',
            title: 'Eliminado!',
            text: 'O subscritor foi removido com sucesso.',
            background: '#0d1628',
            color: '#fff',
            timer: 3000,
            showConfirmButton: false
        });
        <?php endif; ?>
    </script>

    <?php include $base_url . 'inclusoes/rodape.php'; ?>
</body>
</html>

