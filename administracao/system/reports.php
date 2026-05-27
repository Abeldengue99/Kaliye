<?php
// admin/system/reports.php - Central Hub for Reports
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
/** @var PDO $db */
$db = $database->getConnection();

// Quick Stats for the reports hub
$stats = [
    'users' => $db->query("SELECT COUNT(*) FROM users")->fetchColumn(),
    'investments' => $db->query("SELECT COUNT(*) FROM project_investments WHERE status = 'paid'")->fetchColumn(),
    'ads' => $db->query("SELECT COUNT(*) FROM ads WHERE is_active = true")->fetchColumn(),
    'logs' => $db->query("SELECT COUNT(*) FROM audit_logs")->fetchColumn(),
    'support' => $db->query("SELECT COUNT(*) FROM support_messages")->fetchColumn(),
    'projects' => $db->query("SELECT COUNT(*) FROM projects")->fetchColumn(),
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Central de Relatórios - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .report-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
            gap: 1.5rem;
            margin-top: 2rem;
        }
        .report-card {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.05);
            border-radius: 20px;
            padding: 2rem;
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            display: flex;
            flex-direction: column;
            gap: 1.5rem;
            position: relative;
            overflow: hidden;
        }
        .report-card:hover {
            transform: translateY(-5px);
            background: rgba(255, 255, 255, 0.05);
            border-color: var(--aksanti-orange);
        }
        .report-card i.bg-icon {
            position: absolute;
            right: -20px;
            bottom: -20px;
            font-size: 8rem;
            opacity: 0.03;
            pointer-events: none;
        }
        .report-icon {
            width: 50px;
            height: 50px;
            border-radius: 12px;
            background: rgba(247, 148, 29, 0.1);
            color: var(--aksanti-orange);
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
        }
        .report-info h3 { margin: 0; color: #fff; font-size: 1.25rem; }
        .report-info p { margin: 0.5rem 0 0; color: rgba(255,255,255,0.5); font-size: 0.85rem; line-height: 1.5; }
        .report-actions {
            display: flex;
            gap: 0.75rem;
            margin-top: auto;
        }
        .btn-report {
            flex: 1;
            padding: 0.75rem;
            border-radius: 10px;
            border: 1px solid rgba(255,255,255,0.1);
            background: rgba(255,255,255,0.05);
            color: #fff;
            text-decoration: none;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            font-size: 0.8rem;
            font-weight: 700;
            transition: all 0.2s;
        }
        .btn-report:hover {
            background: var(--aksanti-orange);
            color: #000;
            border-color: var(--aksanti-orange);
        }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Hub de Relatórios Profissionais</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Consultee e transfira registos consolidados em formato PDF (A4) ou CSV para Excel.</p>
            </div>
            <div style="background: rgba(247, 148, 29, 0.1); color: var(--aksanti-orange); padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; border: 1px solid rgba(247, 148, 29, 0.2);">
                <i class="fas fa-file-invoice"></i> REPORT ENGINE v2.0
            </div>
        </header>

        <div class="report-grid">
            
            <!-- Relatório de Finanças -->
            <div class="report-card">
                <i class="fas fa-vault bg-icon"></i>
                <div class="report-icon"><i class="fas fa-coins"></i></div>
                <div class="report-info">
                    <h3>Fluxo Financeiro</h3>
                    <p>Controle de movimentações, investimentos aprovados e pipeline de capital. Resumo por moeda.</p>
                </div>
                <div class="report-actions">
                    <a href="../finance/export_finance.php?format=view" target="_blank" class="btn-report"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="../finance/export_finance.php?format=csv" class="btn-report"><i class="fas fa-file-csv"></i> CSV</a>
                </div>
            </div>

            <!-- NOVO: Inteligência Demográfica e Atividade -->
            <div class="report-card" style="border-color: #3b82f644; background: rgba(59, 130, 246, 0.03);">
                <i class="fas fa-chart-pie bg-icon"></i>
                <div class="report-icon" style="background: rgba(59, 130, 246, 0.1); color: #60a5fa;"><i class="fas fa-brain"></i></div>
                <div class="report-info">
                    <h3>Inteligência & Atividade</h3>
                    <p>Estatísticas de Localização, Género, Escolas e Engajamento. Top membros que mais publicam e interagem.</p>
                </div>
                <div class="report-actions">
                    <a href="stats_report.php" class="btn-report" style="background: #3b82f6; color: #fff; border:none; width: 100%;"><i class="fas fa-bolt"></i> ABRIR INTELIGÊNCIA</a>
                </div>
            </div>

            <!-- Relatório de Utilizadors -->
            <div class="report-card">
                <i class="fas fa-users-gear bg-icon"></i>
                <div class="report-icon"><i class="fas fa-user-group"></i></div>
                <div class="report-info">
                    <h3>Base de Utilizadores</h3>
                    <p>Total de <?= $stats['users'] ?> membros. Relatório detalhado por tipo (Investidor, Mentor, Admin).</p>
                </div>
                <div class="report-actions">
                    <a href="../users/export_users.php?format=view" target="_blank" class="btn-report"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="../users/export_users.php?format=csv" class="btn-report"><i class="fas fa-file-csv"></i> CSV</a>
                </div>
            </div>

            <!-- Relatório de Projectos -->
            <div class="report-card">
                <i class="fas fa-diagram-project bg-icon"></i>
                <div class="report-icon"><i class="fas fa-rocket"></i></div>
                <div class="report-info">
                    <h3>Inventário de Projectos</h3>
                    <p>Catálogo completo de projectos e negócios submetidos. <?= $stats['projects'] ?? 0 ?> projectos ativos/pendentes.</p>
                </div>
                <div class="report-actions">
                    <a href="../moderation/export_projects.php?format=view" target="_blank" class="btn-report"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="../moderation/export_projects.php?format=csv" class="btn-report"><i class="fas fa-file-csv"></i> CSV</a>
                </div>
            </div>

            <!-- Relatório de Telemetria -->
            <div class="report-card">
                <i class="fas fa-satellite-dish bg-icon"></i>
                <div class="report-icon"><i class="fas fa-microchip"></i></div>
                <div class="report-info">
                    <h3>Telemetria & Acessos</h3>
                    <p>Auditoria de endereços IP, geolocalização e mix de dispositivos/navegadores dos acessos recentes.</p>
                </div>
                <div class="report-actions">
                    <a href="export_telemetry.php?format=view" target="_blank" class="btn-report"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="export_telemetry.php?format=csv" class="btn-report"><i class="fas fa-file-csv"></i> CSV</a>
                </div>
            </div>

            <!-- Relatório de Auditoria -->
            <div class="report-card">
                <i class="fas fa-fingerprint bg-icon"></i>
                <div class="report-icon"><i class="fas fa-shield-halved"></i></div>
                <div class="report-info">
                    <h3>Logs de Auditoria</h3>
                    <p>Rastreabilidade total de ações administrativas. Registos de <?= $stats['logs'] ?> eventos críticos.</p>
                </div>
                <div class="report-actions">
                    <a href="export_logs.php?format=view" target="_blank" class="btn-report"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="export_logs.php?format=csv" class="btn-report"><i class="fas fa-file-csv"></i> CSV</a>
                </div>
            </div>

            <!-- Relatório de Suporte -->
            <div class="report-card">
                <i class="fas fa-headset bg-icon"></i>
                <div class="report-icon"><i class="fas fa-message"></i></div>
                <div class="report-info">
                    <h3>Suporte & Incidências</h3>
                    <p>Log completo de mensagens enviadas pelos utilizadores. <?= $stats['support'] ?? 0 ?> registos totais.</p>
                </div>
                <div class="report-actions">
                    <a href="../moderation/export_support.php?format=view" target="_blank" class="btn-report"><i class="fas fa-file-pdf"></i> PDF</a>
                    <a href="../moderation/export_support.php?format=csv" class="btn-report"><i class="fas fa-file-csv"></i> CSV</a>
                </div>
            </div>

            <!-- Relatório de Publicidade -->
            <div class="report-card">
                <i class="fas fa-bullhorn bg-icon"></i>
                <div class="report-icon"><i class="fas fa-chart-line"></i></div>
                <div class="report-info">
                    <h3>Desempenho Publicitário</h3>
                    <p>Métricas consolidadas de campanhas (Cliques, Views, CTR) e ROI para parceiros comerciais.</p>
                </div>
                <div class="report-actions">
                    <a href="../marketing/manage_ads.php" class="btn-report"><i class="fas fa-eye"></i> SELECIONAR ANÚNCIO</a>
                </div>
            </div>

            <!-- NOVO: Compliance e Termos Legais -->
            <div class="report-card" style="border-color: #ef444444; background: rgba(239, 68, 68, 0.03);">
                <i class="fas fa-gavel bg-icon"></i>
                <div class="report-icon" style="background: rgba(239, 68, 68, 0.1); color: #f87171;"><i class="fas fa-file-contract"></i></div>
                <div class="report-info">
                    <h3>Compliance & Termos</h3>
                    <p>Auditoria de aceitação de termos legais (Registo e Projectos). Registos com validade para prova jurídica.</p>
                </div>
                <div class="report-actions">
                    <a href="compliance_report.php" class="btn-report" style="background: #ef4444; color: #fff; border:none; width: 100%;"><i class="fas fa-shield-gavel"></i> AUDITAR TERMOS</a>
                </div>
            </div>

            <!-- NOVO: Auditoria Linguística -->
            <div class="report-card" style="border-color: #10b98144; background: rgba(16, 185, 129, 0.03);">
                <i class="fas fa-language bg-icon"></i>
                <div class="report-icon" style="background: rgba(16, 185, 129, 0.1); color: #34d399;"><i class="fas fa-spell-check"></i></div>
                <div class="report-info">
                    <h3>Auditoria Linguística</h3>
                    <p>Varre a plataforma em busca de rótulos antigos, falta de acentos, grafia fora do português de Angola e codificação quebrada.</p>
                </div>
                <div class="report-actions">
                    <a href="content_audit.php" class="btn-report" style="background: #10b981; color: #04130d; border:none; width: 100%;"><i class="fas fa-language"></i> AUDITAR TEXTOS</a>
                </div>
            </div>

        </div>
    </main>

</body>
</html>

