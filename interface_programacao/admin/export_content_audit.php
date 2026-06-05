<?php
/**
 * interface_programacao/admin/export_content_audit.php
 * Exporta resultados de auditoria de conteúdo em CSV ou PDF
 */
session_start();

require_once '../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    die('Acesso negado.');
}

$format = $_GET['format'] ?? 'csv';
$base_url = '../../';

$checks = [
    ['label' => 'Rótulo antigo de projectos', 'severity' => 'alta', 'pattern' => '/\b(meus projectos|novos projectos|explorar projectos)\b/iu'],
    ['label' => 'Grafia brasileira', 'severity' => 'média', 'pattern' => '/\b(usuário|gerenciar|monitorar)\b/iu'],
    ['label' => 'Projeto sem grafia angolana', 'severity' => 'média', 'pattern' => '/\b(projeto|projetos)\b/u'],
    ['label' => 'Codificação quebrada', 'severity' => 'crítica', 'pattern' => '/(Ãƒ.|ââ‚¬|ï¿½)/u'],
];

if ($format === 'csv') {
    $filename = "auditoria_conteudo_" . date('Y-m-d_H-i') . ".csv";
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="' . $filename . '"');
    
    $output = fopen('php://output', 'w');
    fputs($output, chr(0xEF) . chr(0xBB) . chr(0xBF));
    fputcsv($output, ['Verificação', 'Severidade', 'Padrão', 'Sugestão Correcção']);
    
    foreach ($checks as $check) {
        fputcsv($output, [
            $check['label'],
            strtoupper($check['severity']),
            $check['pattern'],
            'Revisar código e aplicar correções conforme sugerido no sistema.'
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
        <title>Auditoria de Conteúdo - KALIYE</title>
        recursos/images/marca/favicon-16x16.ico">
        <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
        <style>
            :root { --aksanti-orange: #f7941d; --bg-dark: #0f172a; }
            body { font-family: 'Inter', system-ui, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 40px; }
            .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 3px solid var(--aksanti-orange); padding-bottom: 20px; margin-bottom: 30px; }
            .logo-section { display: flex; align-items: center; gap: 15px; }
            .logo-box { background: var(--aksanti-orange); padding: 10px; border-radius: 10px; }
            .title-info h1 { margin: 0; font-size: 24px; color: #0f172a; }
            .title-info p { margin: 5px 0 0; color: #64748b; font-size: 14px; }
            
            .stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; margin: 25px 0; }
            .stat-card { background: #f1f5f9; padding: 15px; border-radius: 8px; border-left: 4px solid var(--aksanti-orange); text-align: center; }
            .stat-card .value { font-size: 28px; font-weight: 700; color: var(--aksanti-orange); }
            
            table { width: 100%; border-collapse: collapse; background: white; border-radius: 8px; margin-top: 1.5rem; }
            th { background: var(--bg-dark); color: white; padding: 14px; font-size: 12px; text-transform: uppercase; font-weight: 700; }
            td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; font-size: 13px; }
            tr:nth-child(even) { background: #f1f5f9; }
            
            .severity-critica { color: #ef4444; font-weight: 700; }
            .severity-alta { color: #f97316; font-weight: 700; }
            .severity-media { color: #eab308; font-weight: 700; }
            
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
            <a href="export_content_audit.php?format=csv" class="btn" style="background: #64748b; color: white;"><i class="fas fa-file-csv"></i> Baixar CSV</a>
        </div>
        
        <div class="header">
            <div class="logo-section">
                <div class="logo-box"><img src="<?= $base_url ?>recursos/images/marca/favicon-16x16.ico" alt="K" style="width: 30px; height: 30px;"></div>
                <div class="title-info">
                    <h1>Relatório de Auditoria de Conteúdo</h1>
                    <p>Verificações de Qualidade e Conformidade de Código</p>
                </div>
            </div>
        </div>

        <div class="stats-grid">
            <div class="stat-card">
                <div class="value"><?= count($checks) ?></div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Verificações</div>
            </div>
            <div class="stat-card">
                <div class="value"><?= count(array_filter($checks, fn($c) => $c['severity'] === 'crítica')) ?></div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Críticas</div>
            </div>
            <div class="stat-card">
                <div class="value">4</div>
                <div class="label" style="font-size: 12px; color: #64748b; text-transform: uppercase; margin-top: 5px;">Categorias</div>
            </div>
        </div>

        <table>
            <thead>
                <tr>
                    <th>Verificação</th>
                    <th>Severidade</th>
                    <th>Padrão Detectado</th>
                    <th>Ação Recomendada</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($checks as $check): 
                    $severityClass = 'severity-' . strtolower(str_replace(' ', '-', $check['severity']));
                ?>
                <tr>
                    <td><?= htmlspecialchars($check['label']) ?></td>
                    <td><span class="<?= $severityClass ?>"><?= strtoupper($check['severity']) ?></span></td>
                    <td><code style="background: #f1f5f9; padding: 4px 8px; border-radius: 3px; font-size: 11px;"><?= htmlspecialchars($check['pattern']) ?></code></td>
                    <td>Corrigir conforme padrão KALIYE</td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </body>
    </html>
    <?php
}
?>
