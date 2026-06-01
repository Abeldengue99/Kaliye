<?php
/**
 * interface_programacao/admin/export_legal_contracts.php
 * Exporta dados de contratos legais em CSV ou PDF
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

$agreements = $db->query("
    SELECT la.agreement_id, la.agreement_type, la.status, u.full_name, u.email, 
           p.title as project_title, la.created_at, la.signed_at
    FROM legal_agreements la 
    JOIN users u ON la.user_id = u.user_id 
    LEFT JOIN projects p ON la.project_id = p.project_id 
    ORDER BY la.created_at DESC
    LIMIT 500
")->fetchAll(PDO::FETCH_ASSOC);

$stats = [
    'total' => count($agreements),
    'pending' => count(array_filter($agreements, fn($a) => $a['status'] === 'pending')),
    'signed' => count(array_filter($agreements, fn($a) => $a['status'] === 'signed')),
    'rejected' => count(array_filter($agreements, fn($a) => $a['status'] === 'rejected')),
];

if ($format === 'csv') {
    $filename = "contratos_legais_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['ID', 'Tipo Contrato', 'Utilizador', 'Email', 'Projecto', 'Estado', 'Data Criação', 'Data Assinatura']);
    
    foreach ($agreements as $a) {
        fputcsv($output, [
            $a['agreement_id'],
            $a['agreement_type'],
            $a['full_name'],
            $a['email'],
            $a['project_title'] ?? 'Acordo Geral',
            strtoupper($a['status']),
            date('d/m/Y H:i', strtotime($a['created_at'])),
            $a['signed_at'] ? date('d/m/Y H:i', strtotime($a['signed_at'])) : 'Não assinado'
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
        <title>Relatório de Contratos Legais - KALIYE</title>
        <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
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
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; margin-top: 1.5rem; }
            th { background: var(--bg-dark); color: white; padding: 14px; font-size: 12px; text-transform: uppercase; font-weight: 700; }
            td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .status-pending { background: #fef3c7; color: #92400e; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
            .status-signed { background: #d1fae5; color: #065f46; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
            .status-rejected { background: #fee2e2; color: #991b1b; padding: 4px 8px; border-radius: 4px; font-weight: 600; }
            
            .actions { margin-bottom: 20px; display: flex; gap: 10px; }
            .btn { padding: 10px 20px; border-radius: 6px; font-weight: 600; cursor: pointer; border: none; transition: 0.2s; text-decoration: none; }
            .btn-print { background: var(--aksanti-orange); color: white; }
            
            @media print { .actions { display: none; } }
        </style>
    </head>
    <body>
        <div class="actions">
            <button onclick="window.print()" class="btn btn-print"><i class="fas fa-print"></i> Imprimir / PDF</button>
            <a href="export_legal_contracts.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box"><img src="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png" alt="K" style="width: 30px; height: 30px;"></div>
                <div class="title-info">
                    <h1>Relatório de Contratos Legais</h1>
                    <p>Gestão de Acordos e Termos Contratuais</p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= $stats['total'] ?></div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Total Contratos</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['pending'] ?></div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Pendentes</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['signed'] ?></div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Assinados</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= $stats['rejected'] ?></div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Rejeitados</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Tipo Contrato</th>
                    <th>Utilizador</th>
                    <th>Projecto</th>
                    <th>Estado</th>
                    <th>Data Criação</th>
                    <th>Assinatura</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($agreements as $a): ?>
                <tr>
                    <td><?= $a['agreement_id'] ?></td>
                    <td><?= ucfirst(str_replace('_', ' ', $a['agreement_type'])) ?></td>
                    <td><?= htmlspecialchars($a['full_name']) ?></td>
                    <td><?= $a['project_title'] ? htmlspecialchars($a['project_title']) : '<em>Acordo Geral</em>' ?></td>
                    <td><span class="status-<?= $a['status'] ?>"><?= strtoupper($a['status']) ?></span></td>
                    <td><?= date('d/m/Y H:i', strtotime($a['created_at'])) ?></td>
                    <td><?= $a['signed_at'] ? date('d/m/Y H:i', strtotime($a['signed_at'])) : '<span style="color: #ef4444;">—</span>' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
