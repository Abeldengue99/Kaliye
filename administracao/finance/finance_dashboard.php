<?php
/**
 * admin/finance_dashboard.php - Financial Overview & Proof Validation
 * Refactored into a component-based structure.
 */
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('finance_docs')) {
    header("Location: index.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// 1. Get Financial Overview
try {
    $financial_stats = $db->query("
        SELECT 
            SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_disbursed,
            SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_held,
            SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as potential_pipeline,
            currency
        FROM project_investments
        GROUP BY currency
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Admin finance_dashboard stats query ERROR: " . $e->getMessage());
    try {
        $financial_stats = $db->query("
            SELECT 
                SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) as total_disbursed,
                SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_held,
                SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as potential_pipeline,
                'AOA' as currency
            FROM project_investments
        ")->fetchAll();
    } catch (PDOException $e2) {
        $financial_stats = [];
    }
}

// 2. Get Investment List (com campos de candidatura)
$investments_error = '';
try {
    $investments = $db->query("
        SELECT 
            pi.investment_id, pi.project_id, pi.investor_id, pi.amount, pi.status, pi.created_at,
            pi.currency, pi.investment_type, pi.investor_motivation, pi.investor_experience, pi.investor_linkedin,
            p.title as project_title, p.owner_id, 
            i.full_name as investor_name, o.full_name as owner_name
        FROM project_investments pi
        JOIN projects p ON pi.project_id = p.project_id
        JOIN users i ON pi.investor_id = i.user_id
        JOIN users o ON p.owner_id = o.user_id
        ORDER BY 
            CASE WHEN pi.status = 'pending' THEN 0 ELSE 1 END,
            pi.created_at DESC
    ")->fetchAll();
} catch (PDOException $e) {
    error_log("Admin finance_dashboard investments query ERROR: " . $e->getMessage());
    $investments_error = $e->getMessage();
    // Fallback: tentar query mais simples sem colunas opcionais
    try {
        $investments = $db->query("
            SELECT 
                pi.investment_id, pi.project_id, pi.investor_id, pi.amount, pi.status, pi.created_at,
                p.title as project_title, p.owner_id,
                i.full_name as investor_name, o.full_name as owner_name
            FROM project_investments pi
            JOIN projects p ON pi.project_id = p.project_id
            JOIN users i ON pi.investor_id = i.user_id
            JOIN users o ON p.owner_id = o.user_id
            ORDER BY pi.created_at DESC
        ")->fetchAll();
    } catch (PDOException $e2) {
        error_log("Admin finance fallback query ERROR: " . $e2->getMessage());
        $investments = [];
    }
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Gestão Financeira - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/tesseract.js@5/dist/tesseract.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/pdf.js/3.11.174/pdf.min.js"></script>
    <script src="../../recursos/js/admin_ai_engine.js?v=<?= filemtime(__DIR__ . '/../../recursos/js/admin_ai_engine.js') ?>"></script>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Fluxo de Capital</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Monitoramento de investimentos, validação de comprovativos e auditoria financeira.</p>
            </div>
            <div style="display: flex; gap: 1rem; align-items: center;">
            <div style="display: flex; gap: 0.75rem; align-items: center;">
                <div style="background: rgba(16, 185, 129, 0.1); color: #34d399; padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; border: 1px solid rgba(16, 185, 129, 0.2);">
                    <i class="fas fa-signal"></i> AUDIT ACTIVE
                </div>
                <a href="export_finance.php?format=view" target="_blank" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #fff; padding: 0.75rem 1.5rem; border: 1px solid rgba(255,255,255,0.1); text-decoration: none; border-radius: 10px; font-weight: 600; font-size: 0.85rem;">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="export_finance.php?format=csv" class="btn-admin btn-admin-primary" style="padding: 0.75rem 1.5rem; text-decoration: none;">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
            </div>
            </div>
        </header>

        <!-- KPI Cards Component -->
        <?php include '../../inclusoes/components/admin/admin_finance_kpis.php'; ?>

        <!-- Investment Table Component -->
        <?php include '../../inclusoes/components/admin/admin_investment_table.php'; ?>
    </main>

    <!-- Scripts Component -->
    <?php include '../../inclusoes/components/admin/finance_dashboard_scripts.php'; ?>

</body>
</html>




