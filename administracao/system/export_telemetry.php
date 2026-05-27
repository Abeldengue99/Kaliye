<?php
// admin/export_telemetry.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';

// Segurança: Apenas admins podem baixar
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die("Acesso negado.");
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$format = isset($_GET['format']) ? $_GET['format'] : 'csv';

// Buscar dados
$query = "SELECT l.login_id, u.full_name, u.email, u.user_type, l.ip_address, l.country, l.city, l.isp, l.device_type, l.device_brand, l.login_at 
          FROM login_logs l 
          JOIN users u ON l.user_id = u.user_id 
          ORDER BY l.login_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$data = $stmt->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    $filename = "relatorio_acessos_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF)); // BOM
    fputcsv($output, ['ID Log', 'Utilizador', 'Email', 'Tipo de Conta', 'IP Address', 'País', 'Cidade', 'ISP', 'Dispositivo', 'Marca/Browser', 'Data e Hora']);
    foreach ($data as $row) {
        fputcsv($output, $row);
    }
    fclose($output);
    exit;
} else if ($format === 'pdf' || $format === 'view') {
    // Render as a beautiful HTML page for printing/viewing
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Telemetria - KALIYE</title>
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
            
            .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
            .badge-admin { background: #fef3c7; color: #92400e; }
            .badge-user { background: #dcfce7; color: #166534; }
            
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
            <a href="export_telemetry.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="../../recursos/images/marca/favicon-k-32x32.png" alt="KALIYE">
                </div>
                <div class="title-info">
                    <h1>Relatório de Telemetria de Acessos</h1>
                    <p>Ecossistema KALIYE</p>
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
                    <th>Utilizador</th>
                    <th>Tipo</th>
                    <th>IP / Localização</th>
                    <th>Dispositivo / Marca</th>
                    <th>Data e Hora</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($data as $row): ?>
                <tr>
                    <td>#<?php echo $row['login_id']; ?></td>
                    <td>
                        <strong><?php echo htmlspecialchars($row['full_name']); ?></strong><br>
                        <span style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($row['email']); ?></span>
                    </td>
                    <td>
                        <span class="badge badge-<?php echo ($row['user_type'] === 'admin' ? 'admin' : 'user'); ?>">
                            <?php echo $row['user_type']; ?>
                        </span>
                    </td>
                    <td>
                        <code><?php echo $row['ip_address']; ?></code><br>
                        <span style="font-size: 11px;"><?php echo htmlspecialchars($row['city'] . ', ' . $row['country']); ?></span>
                    </td>
                    <td>
                        <i class="fas fa-<?php echo (strtolower($row['device_type']) === 'mobile' ? 'mobile-alt' : (strtolower($row['device_type']) === 'tablet' ? 'tablet-alt' : 'desktop')); ?>"></i> 
                        <?php echo $row['device_type']; ?><br>
                        <span style="font-size: 11px; color: #64748b;"><?php echo htmlspecialchars($row['device_brand']); ?></span>
                    </td>
                    <td>
                        <?php echo date('d/m/Y', strtotime($row['login_at'])); ?><br>
                        <strong><?php echo date('H:i', strtotime($row['login_at'])); ?></strong>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}

