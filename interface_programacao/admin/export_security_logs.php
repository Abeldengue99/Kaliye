<?php
/**
 * interface_programacao/admin/export_security_logs.php
 * Exporta os logs de NDA e Visualizações para CSV ou PDF.
 */
session_start();

require_once '../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

// Validar acesso de Administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado. Privilégios de Administrador requeridos.');
}

$type = $_GET['type'] ?? 'nda';
$format = $_GET['format'] ?? 'csv';
$base_url = '../../';

// Buscar dados dependendo do tipo
if ($type === 'nda') {
    $data = [];
    $stmt = $db->prepare("
        SELECT n.nda_id, n.accepted_at, u.full_name, u.user_type, p.title, n.ip_address, p.content_hash
        FROM project_nda_logs n
        JOIN users u ON n.user_id = u.user_id
        JOIN projects p ON n.project_id = p.project_id
        ORDER BY n.accepted_at DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $typeLabel = 'NDA';
    $headers = ['ID', 'Data de Assinatura', 'Nome do Utilizador', 'Tipo de Perfil', 'Ideia/Projecto Protegido', 'Endereço IP', 'Hash de Conteúdo'];
} else {
    $data = [];
    $stmt = $db->prepare("
        SELECT v.view_id, v.viewed_at, u.full_name, u.user_type, p.title, v.ip_address
        FROM project_views_log v
        JOIN users u ON v.viewer_id = u.user_id
        JOIN projects p ON v.project_id = p.project_id
        ORDER BY v.viewed_at DESC
    ");
    $stmt->execute();
    $data = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $typeLabel = 'Visualizações';
    $headers = ['ID', 'Data de Acesso', 'Nome do Espectador', 'Tipo de Perfil', 'Projecto Acedido', 'Endereço IP'];
}

// Gerar CSV
if ($format === 'csv') {
    $filename = "relatorio_seguranca_{$type}_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF)); // BOM
    fputcsv($output, $headers);
    
    foreach ($data as $row) {
        if ($type === 'nda') {
            fputcsv($output, [
                $row['nda_id'],
                $row['accepted_at'],
                $row['full_name'],
                strtoupper($row['user_type']),
                $row['title'],
                $row['ip_address'],
                $row['content_hash'] ?? 'N/D'
            ]);
        } else {
            fputcsv($output, [
                $row['view_id'],
                $row['viewed_at'],
                $row['full_name'],
                strtoupper($row['user_type']),
                $row['title'],
                $row['ip_address']
            ]);
        }
    }
    fclose($output);
    exit;

// Gerar PDF/HTML
} else if ($format === 'pdf' || $format === 'view') {
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Segurança - <?= $typeLabel ?> - KALIYE</title>
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
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; display: flex; align-items: center; }
            .logo-box img { width: 30px; height: 30px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .title-info p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
            .meta-info { text-align: right; color: #64748b; font-size: 14px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.1); margin-top: 1rem; }
            th { background: var(--bg-dark); color: white; text-align: left; padding: 14px 15px; font-size: 12px; text-transform: uppercase; letter-spacing: 0.05em; font-weight: 700; }
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
            <a href="export_security_logs.php?type=<?= urlencode($type) ?>&format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box">
                    <img src="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png" alt="KALIYE">
                </div>
                <div class="title-info">
                    <h1>Relatório de Segurança - <?= $typeLabel ?></h1>
                    <p>Logs de Propriedade Intelectual e Confidencialidade KALIYE</p>
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
                    <?php foreach ($headers as $header): ?>
                    <th><?= htmlspecialchars($header) ?></th>
                    <?php endforeach; ?>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($data)): ?>
                    <tr><td colspan="<?= count($headers) ?>" style="text-align: center; padding: 20px; color: #999;">Nenhum registro encontrado</td></tr>
                <?php else: ?>
                    <?php foreach ($data as $row): ?>
                    <tr>
                        <?php if ($type === 'nda'): ?>
                            <td><?= htmlspecialchars($row['nda_id']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($row['accepted_at']))) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><strong><?= htmlspecialchars(strtoupper($row['user_type'])) ?></strong></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?= htmlspecialchars($row['ip_address']) ?></code></td>
                            <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 10px;"><?= $row['content_hash'] ? substr(htmlspecialchars($row['content_hash']), 0, 12) . '...' : 'N/D' ?></code></td>
                        <?php else: ?>
                            <td><?= htmlspecialchars($row['view_id']) ?></td>
                            <td><?= htmlspecialchars(date('d/m/Y H:i:s', strtotime($row['viewed_at']))) ?></td>
                            <td><?= htmlspecialchars($row['full_name']) ?></td>
                            <td><strong><?= htmlspecialchars(strtoupper($row['user_type'])) ?></strong></td>
                            <td><?= htmlspecialchars($row['title']) ?></td>
                            <td><code style="background: #f1f5f9; padding: 2px 6px; border-radius: 3px; font-size: 11px;"><?= htmlspecialchars($row['ip_address']) ?></code></td>
                        <?php endif; ?>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
