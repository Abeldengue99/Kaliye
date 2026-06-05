<?php
/**
 * interface_programacao/admin/export_terms_tracking.php
 * Exporta rastreio de termos de utilizadores em CSV ou PDF
 */
session_start();

require_once '../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado.');
}

$format = $_GET['format'] ?? 'csv';
$base_url = '../../';

$users = $db->query("
    SELECT user_id, full_name, email, user_type, 
           terms_accepted, terms_accepted_at, 
           privacy_accepted, privacy_accepted_at,
           created_at
    FROM users 
    ORDER BY created_at DESC
    LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);

$total_users = count($users);
$terms_accepted = count(array_filter($users, fn($u) => $u['terms_accepted']));
$privacy_accepted = count(array_filter($users, fn($u) => $u['privacy_accepted']));
$terms_pct = $total_users > 0 ? round(($terms_accepted / $total_users) * 100) : 0;
$privacy_pct = $total_users > 0 ? round(($privacy_accepted / $total_users) * 100) : 0;

if ($format === 'csv') {
    $filename = "rastreio_termos_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID', 'Nome', 'Email', 'Tipo', 'Data Registo', 'Termos Aceitos', 'Data Aceitação', 'Privacidade Aceita', 'Data Privacidade']);
    
    foreach ($users as $u) {
        fputcsv($output, [
            $u['user_id'],
            $u['full_name'],
            $u['email'],
            strtoupper($u['user_type']),
            date('d/m/Y H:i', strtotime($u['created_at'])),
            $u['terms_accepted'] ? 'Sim' : 'Não',
            $u['terms_accepted_at'] ? date('d/m/Y H:i', strtotime($u['terms_accepted_at'])) : '',
            $u['privacy_accepted'] ? 'Sim' : 'Não',
            $u['privacy_accepted_at'] ? date('d/m/Y H:i', strtotime($u['privacy_accepted_at'])) : ''
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
        <title>Rastreio de Termos - KALIYE</title>
        recursos/images/marca/favicon-16x16.ico">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root { --aksanti-orange: #f7941d; --bg-dark: #0f172a; }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 25px 0; }
            .stat-card { background: #f1f5f9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--aksanti-orange); text-align: center; }
            .stat-card .value { font-size: 28px; font-weight: 700; color: var(--aksanti-orange); }
            .stat-card .label { font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; margin-top: 1.5rem; }
            th { background: var(--bg-dark); color: white; padding: 14px; font-size: 12px; text-transform: uppercase; font-weight: 700; }
            td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .check { color: #10b981; font-weight: 700; }
            .cross { color: #ef4444; font-weight: 700; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; text-decoration: none; }
            .btn-print { background: var(--aksanti-orange); color: white; }
            
            @media print { .actions { display: none; } }
        </style>
        <?php 
    if (!function_exists('renderKaliyeFavicons')) {
        $root_dir_favicon = __DIR__;
        while (!is_dir($root_dir_favicon . '/inclusoes') && dirname($root_dir_favicon) !== $root_dir_favicon) {
            $root_dir_favicon = dirname($root_dir_favicon);
        }
        require_once $root_dir_favicon . '/inclusoes/components/favicon.php';
    }
    renderKaliyeFavicons($base_url ?? './'); 
    ?>
</head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir / PDF</button>
            <a href="export_terms_tracking.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box"><img src="<?= $base_url ?>recursos/images/marca/favicon-16x16.ico" alt="K" style="width: 30px; height: 30px;"></div>
                <div class="title-info">
                    <h1>Relatório de Conformidade Legal</h1>
                    <p>Rastreio de Aceitação de Termos e Políticas</p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $total_users ?></div>
                <div class="label">Total Utilizadores</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $terms_pct ?>%</div>
                <div class="label">Termos Aceitos</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $privacy_pct ?>%</div>
                <div class="label">Privacidade Aceita</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $total_users - $terms_accepted ?></div>
                <div class="label">Termos Pendentes</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Data Registo</th>
                    <th>Termos</th>
                    <th>Privacidade</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users as $u): ?>
                <tr>
                    <td><?= $u['user_id'] ?></td>
                    <td><?= htmlspecialchars($u['full_name']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= strtoupper($u['user_type']) ?></td>
                    <td><?= date('d/m/Y', strtotime($u['created_at'])) ?></td>
                    <td><span class="<?= $u['terms_accepted'] ? 'check' : 'cross' ?>"><?= $u['terms_accepted'] ? '✓' : '✗' ?></span></td>
                    <td><span class="<?= $u['privacy_accepted'] ? 'check' : 'cross' ?>"><?= $u['privacy_accepted'] ? '✓' : '✗' ?></span></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
