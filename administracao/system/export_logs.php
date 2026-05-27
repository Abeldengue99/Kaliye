<?php
// admin/export_logs.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('audit')) {
    die("Acesso negado.");
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

$query = "SELECT l.*, u.full_name, u.email FROM audit_logs l LEFT JOIN users u ON l.admin_id = u.user_id ORDER BY l.created_at DESC";
$logs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kaliye_audit_logs_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, ['ID', 'Data/Hora', 'Admin ID', 'Nome do Administrador', 'Ação', 'Detalhes']);
    foreach ($logs as $log) {
        fputcsv($output, [
            $log['id'],
            $log['created_at'],
            $log['admin_id'] ?? 'SISTEMA',
            $log['full_name'] ?? 'Sistema',
            $log['action'],
            $log['details']
        ]);
    }
    fclose($output);
    exit;
} else if ($format === 'pdf' || $format === 'view') {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Auditoria - KALIYE</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root {
                --aksanti-orange: #f7941d;
                --bg-dark: #0f172a;
                --text-primary: #f8fafc;
                --text-secondary: #94a3b8;
            }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
            .logo-box img { width: 30px; height: 30px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .title-info p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
            .meta-info { text-align: right; color: #64748b; font-size: 14px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
            th { background: #0f172a; color: white; text-align: left; padding: 12px 15px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; }
            td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 13px; color: #334155; }
            tr:last-child td { border-bottom: none; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; }
            .btn-print { background: var(--aksanti-orange); color: white; }
            .btn-print:hover { background: #e08210; }
            
            @media print {
                .actions { display: none; }
                body { padding: 0; }
                table { box-shadow: none; border: 1px solid #e2e8f0; }
                @page { margin: 1.5cm; }
            }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir / Guardar PDF</button>
            <a href="export_logs.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="../../recursos/images/marca/favicon-k-32x32.png" alt="KALIYE">
                </div>
                <div class="title-info">
                    <h1>Relatório de Log de Auditoria</h1>
                    <p>Rastreabilidade Administrativa KALIYE</p>
                </div>
            </div>
            <div class="meta-info">
                <strong>Data do Relatório:</strong> <?php echo date('d/m/Y H:i'); ?><br>
                <strong>Emitido por:</strong> Administrador (ID: <?php echo $_SESSION['user_id']; ?>)
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Data e Hora</th>
                    <th>Administrador</th>
                    <th>Ação</th>
                    <th>Detalhes</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                <tr>
                    <td>#<?php echo $log['id']; ?></td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($log['created_at'])); ?><br>
                        <strong><?php echo date('H:i:s', strtotime($log['created_at'])); ?></strong>
                    </td>
                    <td>
                        <strong><?php echo htmlspecialchars($log['full_name'] ?? 'Sistema'); ?></strong><br>
                        <span style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($log['email'] ?? 'Ação Automatizada'); ?></span>
                    </td>
                    <td>
                        <strong style="color: var(--aksanti-orange);"><?php echo htmlspecialchars($log['action']); ?></strong>
                    </td>
                    <td style="font-size: 12px;">
                        <?php echo htmlspecialchars($log['details']); ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}

