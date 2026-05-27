<?php
// admin/moderation/export_projects.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) die("Acesso negado.");

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$format = $_GET['format'] ?? 'csv';

$query = "SELECT p.*, u.full_name 
          FROM projects p 
          JOIN users u ON p.owner_id = u.user_id 
          ORDER BY p.created_at DESC";
$projects = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kaliye_projects_report_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, ['ID', 'T�tulo', 'Dono', 'Categoria', 'Estado Apr.', 'P�blico', 'Data']);
    foreach ($projects as $p) {
        fputcsv($output, [
            $p['project_id'],
            $p['title'],
            $p['full_name'],
            $p['category'],
            $p['approval_status'],
            $p['is_public'] ? 'Sim' : 'N�o',
            $p['created_at']
        ]);
    }
    fclose($output);
    exit;
} else {
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Invent�rio de Projectos - KALIYE</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        :root { --aksanti-orange: #f7941d; --bg-dark: #0f172a; }
        body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
        .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
        .logo-section { display: flex; align-items: center; gap: 15px; }
        .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
        .logo-box img { width: 30px; height: 30px; }
        .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
        .meta-info { text-align: right; color: #64748b; font-size: 14px; }
        
        table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); }
        th { background: #0f172a; color: white; text-align: left; padding: 12px 15px; font-size: 11px; text-transform: uppercase; }
        td { padding: 10px 15px; border-bottom: 1px solid #e2e8f0; font-size: 12px; }
        tr:nth-child(even) { background: #f1f5f9; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-approved { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        
        .actions { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
        
        @media print { .actions { display: none; } body { padding: 0; } @page { margin: 1cm; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()" class="btn" style="background: var(--aksanti-orange); color: white;"><i class="fas fa-print"></i> Imprimir / PDF</button>
        <a href="export_projects.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> CSV</a>
    </div>
    
    <div class="header">
        <div class="logo-section">
            <div class="logo-box"><img src="../../recursos/images/marca/favicon-k-32x32.png"></div>
            <div class="title-info">
                <h1>Cat�logo Geral de Projectos</h1>
                <p>Ecossistema KALIYE</p>
            </div>
        </div>
        <div class="meta-info">
            <strong>Total:</strong> <?php echo count($projects); ?> submiss�es<br>
            <strong>Data:</strong> <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>ID</th>
                <th>T�tulo do Projecto</th>
                <th>Propriet�rio</th>
                <th>Categoria</th>
                <th>Modera��o</th>
                <th>Data</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($projects as $p): ?>
            <tr>
                <td>#<?php echo $p['project_id']; ?></td>
                <td><strong><?php echo htmlspecialchars($p['title']); ?></strong></td>
                <td><?php echo htmlspecialchars($p['full_name']); ?></td>
                <td><?php echo htmlspecialchars($p['category']); ?></td>
                <td>
                    <span class="badge <?php echo $p['approval_status'] === 'approved' ? 'badge-approved' : 'badge-pending'; ?>">
                        <?php echo strtoupper($p['approval_status']); ?>
                    </span>
                </td>
                <td><?php echo date('d/m/Y', strtotime($p['created_at'])); ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php } ?>

