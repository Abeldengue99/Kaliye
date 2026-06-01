<?php
/**
 * interface_programacao/admin/export_compliance_data.php
 * Exporta dados de compliance e termos legais em CSV ou PDF
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

// Fetch compliance data
$user_compliance = $db->query("
    SELECT user_id, full_name, email, user_type, created_at, COALESCE(terms_accepted_at, 'Não aceitou') as terms_at
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);

$project_compliance = $db->query("
    SELECT p.project_id, p.title, u.full_name as owner, p.created_at, p.terms_accepted 
    FROM projects p
    LEFT JOIN users u ON p.owner_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);

if ($format === 'csv') {
    $filename = "relatorio_compliance_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    header('Pragma: no-cache');
    header('Expires: 0');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    
    // User Compliance
    fputcsv($output, ['TERMOS DE REGISTO DE UTILIZADORES']);
    fputcsv($output, []);
    fputcsv($output, ['ID', 'Nome', 'Email', 'Tipo', 'Data Registo', 'Data Aceitação Termos']);
    
    foreach ($user_compliance as $row) {
        fputcsv($output, [
            $row['user_id'],
            $row['full_name'],
            $row['email'],
            strtoupper($row['user_type']),
            date('d/m/Y H:i', strtotime($row['created_at'])),
            $row['terms_at'] !== 'Não aceitou' ? date('d/m/Y H:i', strtotime($row['terms_at'])) : 'Pendente'
        ]);
    }
    
    fputcsv($output, []);
    fputcsv($output, []);
    fputcsv($output, ['TERMOS DE SUBMISSÃO DE PROJECTOS']);
    fputcsv($output, []);
    fputcsv($output, ['ID Projecto', 'Título', 'Proprietário', 'Data Criação', 'Termos Aceitos']);
    
    foreach ($project_compliance as $row) {
        fputcsv($output, [
            $row['project_id'],
            $row['title'],
            $row['owner'],
            date('d/m/Y H:i', strtotime($row['created_at'])),
            $row['terms_accepted'] ? 'Sim' : 'Não'
        ]);
    }
    
    fclose($output);
    exit;

} else if ($format === 'pdf' || $format === 'view') {
    $user_count = count($user_compliance);
    $project_count = count($project_compliance);
    $terms_accepted = count(array_filter($user_compliance, fn($u) => strtotime($u['terms_at']) > 0));
    $terms_pct = $user_count > 0 ? round(($terms_accepted / $user_count) * 100) : 0;
    ?>
    <!DOCTYPE html>
    <html lang="pt">
    <head>
        <meta charset="UTF-8">
        <title>Relatório de Compliance - KALIYE</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root { --aksanti-orange: #f7941d; --bg-dark: #0f172a; }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .title-info p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
            .meta-info { text-align: right; color: #64748b; font-size: 14px; }
            
            .stats-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 15px; margin: 25px 0; }
            .stat-card { background: #f1f5f9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--aksanti-orange); text-align: center; }
            .stat-card .value { font-size: 28px; font-weight: 700; color: var(--aksanti-orange); }
            .stat-card .label { font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px; }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; margin-top: 1.5rem; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
            th { background: var(--bg-dark); color: white; text-align: left; padding: 14px; font-size: 12px; text-transform: uppercase; font-weight: 700; }
            td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:last-child td { border-bottom: none; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .section-title { font-size: 18px; font-weight: 700; margin-top: 30px; margin-bottom: 15px; color: #0f172a; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; text-decoration: none; }
            .btn-print { background: var(--aksanti-orange); color: white; }
            
            @media print { .actions { display: none; } body { padding: 0; } }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir / PDF</button>
            <a href="export_compliance_data.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box"><img src="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png" alt="K" style="width: 30px; height: 30px;"></div>
                <div class="title-info">
                    <h1>Relatório de Compliance Legal</h1>
                    <p>Auditoria de Conformidade com Termos e Políticas</p>
                </div>
            </div>
            <div class="meta-info">
                <strong>Data:</strong> <?php echo date('d/m/Y H:i'); ?><br>
                <strong>Admin:</strong> ID <?php echo $_SESSION['user_id']; ?>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $user_count ?></div>
                <div class="label">Utilizadores Registados</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $terms_accepted ?></div>
                <div class="label">Termos Aceitos</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $terms_pct ?>%</div>
                <div class="label">Taxa Conformidade</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $project_count ?></div>
                <div class="label">Projectos Registados</div>
            </div>
        </div>

        <div class="section-title"><i class="fas fa-user-check"></i> Termos de Registo (Últimos 500 Utilizadores)</div>
        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nome</th>
                    <th>Email</th>
                    <th>Tipo</th>
                    <th>Data Registo</th>
                    <th>Aceitação Termos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($user_compliance as $row): ?>
                <tr>
                    <td><?= $row['user_id'] ?></td>
                    <td><?= htmlspecialchars($row['full_name']) ?></td>
                    <td><?= htmlspecialchars($row['email']) ?></td>
                    <td><?= strtoupper($row['user_type']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= $row['terms_at'] !== 'Não aceitou' ? date('d/m/Y H:i', strtotime($row['terms_at'])) : '<span style="color: #ef4444;">Pendente</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <div class="section-title"><i class="fas fa-file-contract"></i> Termos de Projectos (Últimos 500 Projectos)</div>
        <table>
            <thead>
                <tr>
                    <th>ID Projecto</th>
                    <th>Título</th>
                    <th>Proprietário</th>
                    <th>Data Criação</th>
                    <th>Termos Aceitos</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($project_compliance as $row): ?>
                <tr>
                    <td><?= $row['project_id'] ?></td>
                    <td><?= htmlspecialchars($row['title']) ?></td>
                    <td><?= htmlspecialchars($row['owner'] ?? 'Desconhecido') ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($row['created_at'])) ?></td>
                    <td><?= $row['terms_accepted'] ? '<span style="color: #10b981;">✓ Sim</span>' : '<span style="color: #ef4444;">✗ Não</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
