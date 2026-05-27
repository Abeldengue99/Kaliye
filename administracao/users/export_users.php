<?php
// admin/users/export_users.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    die("Acesso negado.");
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';
$role_filter = $_GET['role'] ?? null;
$where = $role_filter ? "WHERE user_type = " . $db->quote($role_filter) : "";

$query = "SELECT user_id, full_name, email, user_type, created_at FROM users $where ORDER BY created_at DESC";
$users = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kaliye_users_' . ($role_filter ?: 'all') . '_' . date('Y-m-d') . '.csv');
    
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, ['ID', 'Nome Completo', 'Email', 'Tipo de Usuário', 'Data de Adesão']);
    
    foreach ($users as $u) {
        fputcsv($output, [
            $u['user_id'],
            $u['full_name'],
            $u['email'],
            $u['user_type'],
            $u['created_at']
        ]);
    }
    fclose($output);
    exit;
} else if ($format === 'view' || $format === 'pdf') {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Utilizadores - KALIYE</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --aksanti-orange: #f7941d;
                --bg-dark: #0f172a;
            }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
            .logo-box img { width: 30px; height: 30px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .meta-info { text-align: right; color: #64748b; font-size: 14px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            th { background: #0f172a; color: white; text-align: left; padding: 12px 15px; font-size: 12px; text-transform: uppercase; }
            td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
            .badge-admin { background: #fef3c7; color: #92400e; }
            .badge-investor { background: #dbeafe; color: #1e40af; }
            .badge-mentor { background: #dcfce7; color: #166534; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
            
            @media print {
                .actions { display: none; }
                body { padding: 0; }
                @page { margin: 1.5cm; }
            }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn" style="background: var(--aksanti-orange); color: white;"><i class="fas fa-print"></i> Imprimir / PDF</button>
            <a href="export_users.php?format=csv&role=<?php echo $role_filter; ?>" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="../../recursos/images/marca/favicon-k-32x32.png" alt="KALIYE">
                </div>
                <div class="title-info">
                    <h1>Relatório de Utilizadores</h1>
                    <p>Filtro: <?php echo $role_filter ?: 'Todos os Utilizadores'; ?></p>
                </div>
            </div>
            <div class="meta-info">
                <strong>Data:</strong> <?php echo date('d/m/Y H:i'); ?><br>
                <strong>Total:</strong> <?php echo count($users); ?> registos
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome Completo</th>
                    <th>E-mail</th>
                    <th>Tipo</th>
                    <th>Adesão</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td>#<?php echo $u['user_id']; ?></td>
                    <td><strong><?php echo htmlspecialchars($u['full_name']); ?></strong></td>
                    <td><?php echo htmlspecialchars($u['email']); ?></td>
                    <td>
                        <span class="badge badge-<?php echo $u['user_type']; ?>">
                            <?php echo $u['user_type']; ?>
                        </span>
                    </td>
                    <td><?php echo date('d/m/Y', strtotime($u['created_at'])); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}

