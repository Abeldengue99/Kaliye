<?php
// admin/moderation/export_support.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) die("Acesso negado.");

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$format = $_GET['format'] ?? 'csv';

$query = "SELECT m.*, u.full_name, u.email 
          FROM support_messages m 
          LEFT JOIN users u ON m.user_id = u.user_id 
          ORDER BY m.created_at DESC";
$messages = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=kaliye_support_messages_' . date('Y-m-d') . '.csv');
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, ['ID', 'Data', 'Remetente', 'Email', 'Mensagem', 'Estado']);
    foreach ($messages as $m) {
        fputcsv($output, [
            $m['id'],
            $m['created_at'],
            $m['full_name'] ?: 'Visitante',
            $m['email'] ?: 'N/A',
            $m['message'],
            $m['is_read'] ? 'Lida' : 'Pendente'
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
    <title>Relatório de Suporte - KALIYE</title>
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
        td { padding: 12px 15px; border-bottom: 1px solid #e2e8f0; font-size: 12px; vertical-align: top; }
        tr:nth-child(even) { background: #f1f5f9; }
        
        .badge { padding: 3px 8px; border-radius: 4px; font-size: 10px; font-weight: 700; text-transform: uppercase; }
        .badge-read { background: #dcfce7; color: #166534; }
        .badge-pending { background: #fef3c7; color: #92400e; }
        
        .actions { margin-bottom: 20px; display: flex; gap: 10px; }
        .btn { text-decoration: none; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; }
        
        @media print { .actions { display: none; } body { padding: 0; } @page { margin: 1cm; } }
    </style>
</head>
<body>
    <div class="actions">
        <button onclick="window.print()" class="btn" style="background: var(--aksanti-orange); color: white;"><i class="fas fa-print"></i> Imprimir / PDF</button>
        <a href="export_support.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> CSV</a>
    </div>
    
    <div class="header">
        <div class="logo-section">
            <div class="logo-box"><img src="../../recursos/images/marca/favicon-k-32x32.png"></div>
            <div class="title-info">
                <h1>Log de Atendimento ao Cliente</h1>
                <p>Mensagens de Suporte e Incidências</p>
            </div>
        </div>
        <div class="meta-info">
            <strong>Total:</strong> <?php echo count($messages); ?> registos<br>
            <strong>Data:</strong> <?php echo date('d/m/Y H:i'); ?>
        </div>
    </div>

    <table>
        <thead>
            <tr>
                <th>Data</th>
                <th>Utilizador</th>
                <th>Mensagem</th>
                <th>Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($messages as $m): ?>
            <tr>
                <td style="white-space: nowrap;"><?php echo date('d/m/Y H:i', strtotime($m['created_at'])); ?></td>
                <td>
                    <strong><?php echo htmlspecialchars($m['full_name'] ?: 'Visitante'); ?></strong><br>
                    <small><?php echo htmlspecialchars($m['email'] ?: 'No Email'); ?></small>
                </td>
                <td><?php echo nl2br(htmlspecialchars($m['message'])); ?></td>
                <td>
                    <span class="badge <?php echo $m['is_read'] ? 'badge-read' : 'badge-pending'; ?>">
                        <?php echo $m['is_read'] ? 'Lida' : 'Pendente'; ?>
                    </span>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>
<?php } ?>

