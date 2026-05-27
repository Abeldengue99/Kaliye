<?php
/**
 * admin/system/compliance_report.php - Legal Compliance & Terms Audit
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();

// --- DATA FETCHING ---

// 1. User Registration Terms
$user_compliance = $db->query("
    SELECT user_id, full_name, email, created_at, COALESCE(terms_version, 'v1.0') as version, acceptance_ip 
    FROM users 
    ORDER BY created_at DESC 
    LIMIT 200
")->fetchAll();

// 2. Project Submission Terms
$project_compliance = $db->query("
    SELECT p.project_id, p.title, u.full_name as owner, p.created_at, p.terms_accepted 
    FROM projects p
    LEFT JOIN users u ON p.owner_id = u.user_id
    ORDER BY p.created_at DESC
    LIMIT 200
")->fetchAll();

?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base_url; ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base_url; ?>recursos/images/marca/apple-touch-icon-k.png">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria de Compliance - KALIYE Admin</title>
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .compliance-section {
            background: rgba(13, 22, 40, 0.4);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 2rem;
            margin-bottom: 2rem;
            backdrop-filter: blur(10px);
        }
        .compliance-section h3 {
            color: #fff; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 10px;
        }
        .compliance-section h3 i { color: #f87171; }
        
        .audit-table { width: 100%; border-collapse: collapse; }
        .audit-table th { text-align: left; padding: 12px; font-size: 0.75rem; color: #f87171; text-transform: uppercase; border-bottom: 1px solid rgba(255,255,255,0.1); }
        .audit-table td { padding: 12px; font-size: 0.85rem; color: rgba(255,255,255,0.7); border-bottom: 1px solid rgba(255,255,255,0.03); }
        
        .badge-compliance {
            padding: 4px 10px; border-radius: 6px; font-size: 0.7rem; font-weight: 800;
        }
        .badge-success { background: rgba(16, 185, 129, 0.1); color: #10b981; }
        .badge-warning { background: rgba(251, 191, 36, 0.1); color: #fbbf24; }
        .ip-addr { font-family: monospace; color: rgba(255,255,255,0.4); font-size: 0.75rem; }
    </style>
</head>
<body class="admin-dashboard-layout">

    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Auditoria de Compliance e Termos</h1>
                <p style="color: rgba(255,255,255,0.5);">Registos históricos de aceitação de termos legais e políticas de privacidade.</p>
            </div>
            <div style="display: flex; gap: 1rem;">
                <button onclick="window.print()" class="btn-admin" style="background: #ef4444; color:#fff; border:none;"><i class="fas fa-file-pdf"></i> GERAR PROVA JURÍDICA</button>
            </div>
        </header>

        <!-- SECÇÃO 1: TERMOS DE REGISTO -->
        <div class="compliance-section">
            <h3><i class="fas fa-user-shield"></i> Aceitação de Termos no Registo (Membros)</h3>
            <div style="overflow-x: auto;">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Data/Hora</th>
                            <th>Membro</th>
                            <th>Email</th>
                            <th>Versão</th>
                            <th>Endereço IP (Prova)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($user_compliance as $u): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                            <td style="color: #fff; font-weight: 700;"><?= $u['full_name'] ?></td>
                            <td><?= $u['email'] ?></td>
                            <td><span class="badge-compliance badge-success"><?= $u['version'] ?></span></td>
                            <td><span class="ip-addr"><?= $u['acceptance_ip'] ?: '0.0.0.0 (Migrado)' ?></span></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- SECÇÃO 2: TERMOS DE PROJETOS -->
        <div class="compliance-section">
            <h3><i class="fas fa-rocket"></i> Aceitação de Termos na Submissão de Projetos</h3>
            <div style="overflow-x: auto;">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th>Data de Submissão</th>
                            <th>Título do Projeto</th>
                            <th>Proprietário</th>
                            <th>Estado dos Termos</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($project_compliance as $p): ?>
                        <tr>
                            <td><?= date('d/m/Y H:i', strtotime($p['created_at'])) ?></td>
                            <td style="color: #fff; font-weight: 700;"><?= $p['title'] ?></td>
                            <td><?= $p['owner'] ?></td>
                            <td>
                                <?php if($p['terms_accepted']): ?>
                                    <span class="badge-compliance badge-success"><i class="fas fa-check"></i> ACEITE</span>
                                <?php else: ?>
                                    <span class="badge-compliance badge-warning"><i class="fas fa-clock"></i> PENDENTE</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

    </main>

</body>
</html>

