<?php
/**
 * admin/system/retention_management.php - Data Retention & Archival Dashboard
 * 
 * Interface to monitor and manage archived data, deleted records, and retention policies.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/RetentionMaintenance.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
$db = $database->getConnection();
$retention = new RetentionMaintenance($db);
$retention->ensureSchema();

// Get retention statistics
$stats = [
    'archived_investments' => (int)$db->query("SELECT COUNT(*) FROM project_investments WHERE archived_at IS NOT NULL")->fetchColumn(),
    'archived_notifications' => (int)$db->query("SELECT COUNT(*) FROM notifications WHERE archived_at IS NOT NULL")->fetchColumn(),
    'archived_mentorships' => (int)$db->query("SELECT COUNT(*) FROM free_mentorship_requests WHERE archived_at IS NOT NULL")->fetchColumn(),
    'archived_resources' => (int)$db->query("SELECT COUNT(*) FROM mentorship_resources WHERE archived_at IS NOT NULL")->fetchColumn(),
    'total_snapshots' => (int)$db->query("SELECT COUNT(*) FROM data_archive_snapshots")->fetchColumn(),
];

// Get last retention run
$lastRun = null;
$stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'retention_last_run_at' LIMIT 1");
$stmt->execute();
$lastRunValue = $stmt->fetchColumn();
if ($lastRunValue) {
    $lastRun = new DateTime($lastRunValue);
    $now = new DateTime();
    $interval = $now->diff($lastRun);
}

// Get archive snapshots (most recent)
$snapshots = $db->query("
    SELECT source_table, archive_reason, COUNT(*) as count, MAX(created_at) as last_archived
    FROM data_archive_snapshots
    GROUP BY source_table, archive_reason
    ORDER BY last_archived DESC
")->fetchAll(PDO::FETCH_ASSOC);

// Get detailed snapshots for inspection
$detailedSnapshots = $db->query("
    SELECT snapshot_id, source_table, source_pk, archive_reason, 
           created_at, LENGTH(payload) as payload_size
    FROM data_archive_snapshots
    ORDER BY created_at DESC
    LIMIT 50
")->fetchAll(PDO::FETCH_ASSOC);

// Handle manual trigger (if admin triggers retention manually)
$manualRunResult = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'trigger_retention' && isset($_POST['_token'])) {
        // Simple token check (in production, use proper CSRF token)
        try {
            $dryRun = isset($_POST['dry_run']) && $_POST['dry_run'] === 'on';
            $result = $dryRun ? $retention->run(true) : $retention->runIfDue(0);
            $manualRunResult = [
                'type' => $dryRun ? 'dry_run' : 'executed',
                'data' => $result
            ];
        } catch (Throwable $e) {
            $manualRunResult = [
                'type' => 'error',
                'message' => $e->getMessage()
            ];
        }
    }
}

function formatBytes($bytes): string {
    $units = ['B', 'KB', 'MB', 'GB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= (1 << (10 * $pow));
    return round($bytes, 2) . ' ' . $units[$pow];
}

function getReasonBadgeColor($reason) {
    $colors = [
        'mentor_application_expired' => '#8b5cf6',
        'investor_application_expired' => '#6366f1',
        'investment_application_expired' => '#f59e0b',
        'old_notification' => '#64748b',
        'expired_invitation' => '#ec4899',
        'completed_mentorship_history' => '#10b981',
        'mentorship_resource_expired' => '#ef4444',
        'task_expired' => '#f97316',
        'notice_expired' => '#06b6d4',
    ];
    return $colors[$reason] ?? '#6b7280';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gerenciamento de Retenção - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .retention-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .retention-card {
            background: rgba(255, 255, 255, 0.02);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 16px;
            padding: 1.5rem;
            transition: all 0.3s ease;
        }
        .retention-card:hover {
            background: rgba(255, 255, 255, 0.04);
            border-color: rgba(255, 255, 255, 0.1);
        }
        .retention-card .stat-value {
            font-size: 2.5rem;
            font-weight: 700;
            color: var(--aksanti-orange);
            margin: 0.5rem 0;
        }
        .retention-card .stat-label {
            font-size: 0.9rem;
            color: rgba(255, 255, 255, 0.6);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .retention-card .stat-icon {
            font-size: 2.5rem;
            opacity: 0.15;
            margin-bottom: 0.5rem;
        }
        
        .status-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            background: rgba(16, 185, 129, 0.1);
            color: #10b981;
            border: 1px solid rgba(16, 185, 129, 0.2);
        }
        
        .snapshot-table {
            width: 100%;
            border-collapse: collapse;
            margin-top: 1.5rem;
        }
        .snapshot-table thead {
            background: rgba(255, 255, 255, 0.03);
        }
        .snapshot-table th, .snapshot-table td {
            padding: 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }
        .snapshot-table th {
            font-weight: 700;
            color: rgba(255, 255, 255, 0.7);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        .snapshot-table tbody tr:hover {
            background: rgba(255, 255, 255, 0.02);
        }
        
        .badge {
            display: inline-block;
            padding: 0.35rem 0.75rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .action-buttons {
            display: flex;
            gap: 0.75rem;
            margin-top: 2rem;
        }
        
        .btn-retention {
            padding: 0.875rem 1.75rem;
            border: none;
            border-radius: 12px;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.3s ease;
            display: flex;
            align-items: center;
            gap: 0.5rem;
            font-size: 0.95rem;
        }
        
        .btn-retention-primary {
            background: linear-gradient(135deg, #f7941d, #f79c1d);
            color: #fff;
            border: 1px solid rgba(247, 148, 29, 0.3);
        }
        
        .btn-retention-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(247, 148, 29, 0.3);
        }
        
        .btn-retention-secondary {
            background: rgba(255, 255, 255, 0.05);
            color: #fff;
            border: 1px solid rgba(255, 255, 255, 0.1);
        }
        
        .btn-retention-secondary:hover {
            background: rgba(255, 255, 255, 0.08);
            border-color: rgba(255, 255, 255, 0.2);
        }
        
        .alert-info {
            background: rgba(59, 130, 246, 0.1);
            border: 1px solid rgba(59, 130, 246, 0.2);
            color: #60a5fa;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .alert-success {
            background: rgba(16, 185, 129, 0.1);
            border: 1px solid rgba(16, 185, 129, 0.2);
            color: #10b981;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
        
        .alert-warning {
            background: rgba(245, 158, 11, 0.1);
            border: 1px solid rgba(245, 158, 11, 0.2);
            color: #f59e0b;
            padding: 1rem;
            border-radius: 12px;
            margin-bottom: 1.5rem;
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1><i class="fas fa-archive"></i> Gerenciamento de Retenção</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Monitore dados arquivados, políticas de retenção e snapshots de auditoria.</p>
            </div>
        </header>

        <!-- Status da Última Execução -->
        <?php if ($lastRun): ?>
            <div class="alert-info">
                <i class="fas fa-check-circle"></i> Última execução: <strong><?= $lastRun->format('d/m/Y H:i:s') ?></strong>
                (há <?= $interval->format('%d dias, %h horas') ?>)
            </div>
        <?php else: ?>
            <div class="alert-warning">
                <i class="fas fa-exclamation-triangle"></i> Nenhuma execução de retenção registada ainda. O sistema executará automaticamente a cada 90 dias.
            </div>
        <?php endif; ?>

        <!-- Resultado de Execução Manual -->
        <?php if ($manualRunResult): ?>
            <?php if ($manualRunResult['type'] === 'error'): ?>
                <div class="alert-error" style="background: rgba(239, 68, 68, 0.1); border-color: rgba(239, 68, 68, 0.2); color: #ef4444; margin-bottom: 1.5rem;">
                    <i class="fas fa-exclamation-circle"></i> <strong>Erro:</strong> <?= htmlspecialchars($manualRunResult['message']) ?>
                </div>
            <?php else: ?>
                <div class="alert-success">
                    <i class="fas fa-check-circle"></i> <strong><?= $manualRunResult['type'] === 'dry_run' ? 'Simulação' : 'Retenção' ?> completada!</strong>
                    <?php if (!empty($manualRunResult['data'])): ?>
                        <br><small>
                        <?php foreach ($manualRunResult['data'] as $type => $count): ?>
                            <?php if (is_int($count) && $count > 0): ?>
                                <?= htmlspecialchars($type) ?>: <strong><?= $count ?></strong> |
                            <?php endif; ?>
                        <?php endforeach; ?>
                        </small>
                    <?php endif; ?>
                </div>
            <?php endif; ?>
        <?php endif; ?>

        <!-- Statistics Grid -->
        <div class="retention-grid">
            <div class="retention-card">
                <i class="fas fa-chart-line stat-icon"></i>
                <div class="stat-label">Investimentos Arquivados</div>
                <div class="stat-value"><?= $stats['archived_investments'] ?></div>
            </div>
            <div class="retention-card">
                <i class="fas fa-bell stat-icon"></i>
                <div class="stat-label">Notificações Arquivadas</div>
                <div class="stat-value"><?= $stats['archived_notifications'] ?></div>
            </div>
            <div class="retention-card">
                <i class="fas fa-handshake stat-icon"></i>
                <div class="stat-label">Mentorias Arquivadas</div>
                <div class="stat-value"><?= $stats['archived_mentorships'] ?></div>
            </div>
            <div class="retention-card">
                <i class="fas fa-file-alt stat-icon"></i>
                <div class="stat-label">Recursos Arquivados</div>
                <div class="stat-value"><?= $stats['archived_resources'] ?></div>
            </div>
            <div class="retention-card">
                <i class="fas fa-database stat-icon"></i>
                <div class="stat-label">Total de Snapshots</div>
                <div class="stat-value"><?= $stats['total_snapshots'] ?></div>
            </div>
        </div>

        <!-- Manual Execution -->
        <div class="admin-card-premium" style="margin-top: 2rem;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
                <h3 style="margin: 0;">Executar Retenção Manualmente</h3>
                <div style="display: flex; gap: 10px;">
                    <button onclick="exportRetentionCSV()" class="btn-retention btn-retention-secondary" style="gap: 8px; padding: 0.75rem 1.25rem; font-size: 0.85rem;">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </button>
                    <button onclick="exportRetentionPDF()" class="btn-retention btn-retention-secondary" style="gap: 8px; padding: 0.75rem 1.25rem; font-size: 0.85rem;">
                        <i class="fas fa-file-pdf"></i> Imprimir / PDF
                    </button>
                </div>
            </div>
            <form method="POST" style="display: flex; gap: 1rem; align-items: center;">
                <label style="display: flex; align-items: center; gap: 0.5rem; cursor: pointer; font-weight: 600;">
                    <input type="checkbox" name="dry_run" id="dry_run" style="cursor: pointer;">
                    <span>Modo Simulação (não deleta dados)</span>
                </label>
                <button type="submit" class="btn-retention btn-retention-primary" name="action" value="trigger_retention">
                    <i class="fas fa-play"></i> Executar Agora
                </button>
            </form>
        </div>

        <!-- Archive Reasons Summary -->
        <div class="admin-card-premium" style="margin-top: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Resumo de Razões de Arquivagem</h3>
            <table class="snapshot-table">
                <thead>
                    <tr>
                        <th>Tabela</th>
                        <th>Razão</th>
                        <th>Quantidade</th>
                        <th>Última Arquivagem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($snapshots as $snapshot): ?>
                        <tr>
                            <td><code style="background: rgba(0,0,0,0.2); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($snapshot['source_table']) ?></code></td>
                            <td>
                                <span class="badge" style="background: <?= getReasonBadgeColor($snapshot['archive_reason']) ?>20; color: <?= getReasonBadgeColor($snapshot['archive_reason']) ?>;">
                                    <?= htmlspecialchars($snapshot['archive_reason']) ?>
                                </span>
                            </td>
                            <td><strong><?= $snapshot['count'] ?></strong></td>
                            <td><?= (new DateTime($snapshot['last_archived']))->format('d/m/Y H:i') ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- Recent Snapshots Detail -->
        <div class="admin-card-premium" style="margin-top: 2rem; margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1.5rem;">Últimos Snapshots (Últimas 50 arquivagens)</h3>
            <div style="overflow-x: auto;">
                <table class="snapshot-table">
                    <thead>
                        <tr>
                            <th>ID do Snapshot</th>
                            <th>Tabela</th>
                            <th>ID do Registo</th>
                            <th>Razão</th>
                            <th>Tamanho</th>
                            <th>Data</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detailedSnapshots as $snap): ?>
                            <tr>
                                <td><code style="font-size: 0.8rem;">#<?= $snap['snapshot_id'] ?></code></td>
                                <td><?= htmlspecialchars($snap['source_table']) ?></td>
                                <td><code style="background: rgba(0,0,0,0.2); padding: 0.25rem 0.5rem; border-radius: 4px; font-size: 0.85rem;"><?= htmlspecialchars($snap['source_pk']) ?></code></td>
                                <td>
                                    <span class="badge" style="background: <?= getReasonBadgeColor($snap['archive_reason']) ?>20; color: <?= getReasonBadgeColor($snap['archive_reason']) ?>;">
                                        <?= htmlspecialchars($snap['archive_reason']) ?>
                                    </span>
                                </td>
                                <td><?= formatBytes($snap['payload_size']) ?></td>
                                <td><?= (new DateTime($snap['created_at']))->format('d/m/Y H:i:s') ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Documentation -->
        <div class="admin-card-premium" style="margin-bottom: 2rem;">
            <h3 style="margin-bottom: 1rem;"><i class="fas fa-info-circle"></i> Como Funciona a Retenção?</h3>
            <p style="color: rgba(255,255,255,0.7); line-height: 1.6; margin-bottom: 1rem;">
                O sistema de retenção funciona de forma <strong>automática</strong> e <strong>silenciosa</strong>:
            </p>
            <ul style="color: rgba(255,255,255,0.6); line-height: 1.8; padding-left: 1.5rem;">
                <li><strong>Frequência:</strong> A cada 90 dias (ou quando acionado manualmente)</li>
                <li><strong>Tipos de Dados Arquivados:</strong>
                    <ul style="margin-top: 0.5rem;">
                        <li>Aplicações de mentor/investidor com 90+ dias sem ação</li>
                        <li>Investimentos rejeitados ou cancelados com 90+ dias</li>
                        <li>Notificações lidas com 180+ dias</li>
                        <li>Mentorias completadas com 180+ dias</li>
                        <li>Recursos/ficheiros expirados</li>
                        <li>Tarefas/Avisos com data de expiração passada</li>
                    </ul>
                </li>
                <li><strong>Segurança:</strong> Antes de deletar, cria um JSON snapshot na tabela <code>data_archive_snapshots</code> para auditoria</li>
                <li><strong>Impacto Zero:</strong> Queries normais usam <code>WHERE archived_at IS NULL</code> para excluir automaticamente dados arquivados</li>
            </ul>
        </div>
    </main>

    <script>
    function exportRetentionCSV() {
        window.location.href = '../../interface_programacao/admin/export_retention_data.php?format=csv';
    }

    function exportRetentionPDF() {
        window.location.href = '../../interface_programacao/admin/export_retention_data.php?format=pdf';
    }
    </script>

</body>
</html>
