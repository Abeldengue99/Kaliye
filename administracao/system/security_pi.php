<?php
// admin/system/security_pi.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('audit')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Segurança de PI - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .log-badge { font-size: 0.65rem; font-weight: 900; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
        .tab-btn {
            background: rgba(255,255,255,0.05); color: rgba(255,255,255,0.5); padding: 0.75rem 1.5rem; 
            border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; cursor: pointer; font-weight: 800; font-size: 0.85rem; transition: 0.3s;
        }
        .tab-btn.active {
            background: rgba(247, 148, 29, 0.1); color: #f7941d; border-color: rgba(247, 148, 29, 0.3);
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
                <h1>Segurança de PI & NDAs</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Monitorização de acesso a Propriedade Intelectual e assinaturas de Termos de Confidencialidade.</p>
            </div>
        </header>

        <!-- Filtros e Tabs -->
        <div class="admin-card-premium" style="margin-bottom: 2rem; padding: 1.25rem;">
            <div style="display: flex; gap: 1rem; align-items: center; justify-content: space-between;">
                <div style="display: flex; gap: 10px;">
                    <button class="tab-btn active" onclick="switchTab('nda')">Contratos NDA Assinados</button>
                    <button class="tab-btn" onclick="switchTab('views')">Registos de Acesso (Logs)</button>
                </div>
                
                <div style="display: flex; gap: 10px; align-items: center;">
                    <button onclick="exportCSV()" class="shine-on-hover" style="background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.75rem 1.25rem; border-radius: 12px; border: 1px solid rgba(16, 185, 129, 0.3); font-size: 0.85rem; font-weight: 800; cursor: pointer; transition: 0.3s; display: flex; align-items: center; gap: 8px;">
                        <i class="fas fa-file-csv"></i> Exportar CSV
                    </button>
                    <div style="background: rgba(255, 255, 255, 0.03); padding: 0.75rem 1.25rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem; color: #fff; font-weight: 700;">
                        <span style="color: rgba(255,255,255,0.3); margin-right: 0.5rem;">TOTAL REGISTOS:</span>
                        <span id="logCount">0</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabela NDA -->
        <div class="admin-card-premium" id="table-nda" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Subscritor (Utilizador)</th>
                            <th>Projecto / Ideia</th>
                            <th>Dados Técnicos / Prova</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-nda">
                        <!-- Carregado via JS -->
                        <tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.3);">A carregar NDAs...</td></tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Tabela Views -->
        <div class="admin-card-premium" id="table-views" style="padding: 0; display: none;">
            <div class="table-container">
                <table class="aksanti-table">
                    <thead>
                        <tr>
                            <th>Data / Hora</th>
                            <th>Espectador</th>
                            <th>Projecto Acedido</th>
                            <th>Rastreio de Dispositivo</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-views">
                        <!-- Carregado via JS -->
                    </tbody>
                </table>
            </div>
        </div>

    </main>

    <script>
    let currentTab = 'nda';

    function exportCSV() {
        window.location.href = `../../interface_programacao/admin/export_security_logs.php?type=${currentTab}`;
    }

    function switchTab(tab) {
        currentTab = tab;
        document.querySelectorAll('.tab-btn').forEach(btn => btn.classList.remove('active'));
        event.currentTarget.classList.add('active');
        
        document.getElementById('table-nda').style.display = tab === 'nda' ? 'block' : 'none';
        document.getElementById('table-views').style.display = tab === 'views' ? 'block' : 'none';
        
        loadSecurityLogs();
    }

    function timeElapsed(date) {
        const seconds = Math.floor((new Date() - new Date(date)) / 1000);
        let interval = seconds / 31536000;
        if (interval > 1) return 'há ' + Math.floor(interval) + ' anos';
        interval = seconds / 2592000;
        if (interval > 1) return 'há ' + Math.floor(interval) + ' meses';
        interval = seconds / 604800;
        if (interval > 1) return 'há ' + Math.floor(interval) + ' semanas';
        interval = seconds / 86400;
        if (interval > 1) return 'há ' + Math.floor(interval) + ' dias';
        interval = seconds / 3600;
        if (interval > 1) return 'há ' + Math.floor(interval) + ' horas';
        interval = seconds / 60;
        if (interval > 1) return 'há ' + Math.floor(interval) + ' min';
        return 'agora';
    }

    function loadSecurityLogs() {
        fetch('../../interface_programacao/admin/admin_security_logs.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    // Preencher NDAs
                    const tbodyNda = document.getElementById('tbody-nda');
                    if (data.nda_logs.length === 0) {
                        tbodyNda.innerHTML = '<tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.3);">Nenhum NDA registado ainda.</td></tr>';
                    } else {
                        tbodyNda.innerHTML = data.nda_logs.map(log => {
                            const dateObj = new Date(log.accepted_at);
                            return `
                            <tr>
                                <td>
                                    <div style="font-weight: 800; font-size: 0.8rem; color: #fff;">${dateObj.toLocaleDateString()}</div>
                                    <div style="font-size: 0.65rem; color: #10b981; font-weight: 700; text-transform: uppercase;">
                                        ${dateObj.toLocaleTimeString()} • ${timeElapsed(log.accepted_at)}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: #fff; font-size: 0.85rem;">${log.full_name}</div>
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 700;">${log.user_type}</div>
                                </td>
                                <td>
                                    <span style="color: var(--elite-orange); font-weight: 700; font-size: 0.8rem;">${log.project_title}</span>
                                </td>
                                <td>
                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); font-family: monospace;">IP: ${log.ip_address}</div>
                                    <div style="font-size: 0.65rem; color: #4ade80; margin-top:4px;"><i class="fas fa-certificate"></i> Hash: ${log.content_hash ? log.content_hash.substring(0, 16)+'...' : 'N/D'}</div>
                                </td>
                            </tr>`;
                        }).join('');
                    }

                    // Preencher Views
                    const tbodyViews = document.getElementById('tbody-views');
                    if (data.view_logs.length === 0) {
                        tbodyViews.innerHTML = '<tr><td colspan="4" style="text-align: center; color: rgba(255,255,255,0.3);">Nenhum acesso registado.</td></tr>';
                    } else {
                        tbodyViews.innerHTML = data.view_logs.map(log => {
                            const dateObj = new Date(log.viewed_at);
                            return `
                            <tr>
                                <td>
                                    <div style="font-weight: 800; font-size: 0.8rem; color: #fff;">${dateObj.toLocaleDateString()}</div>
                                    <div style="font-size: 0.65rem; color: #3b82f6; font-weight: 700; text-transform: uppercase;">
                                        ${dateObj.toLocaleTimeString()} • ${timeElapsed(log.viewed_at)}
                                    </div>
                                </td>
                                <td>
                                    <div style="font-weight: 800; color: #fff; font-size: 0.85rem;">${log.full_name}</div>
                                    <div style="font-size: 0.6rem; color: rgba(255,255,255,0.4); text-transform: uppercase; font-weight: 700;">${log.user_type}</div>
                                </td>
                                <td>
                                    <span style="color: #fff; font-weight: 600; font-size: 0.8rem;">${log.project_title}</span>
                                </td>
                                <td>
                                    <div style="font-size: 0.7rem; color: rgba(255,255,255,0.5); font-family: monospace;">IP: ${log.ip_address}</div>
                                </td>
                            </tr>`;
                        }).join('');
                    }

                    document.getElementById('logCount').innerText = currentTab === 'nda' ? data.nda_logs.length : data.view_logs.length;
                }
            });
    }

    document.addEventListener('DOMContentLoaded', loadSecurityLogs);
    setInterval(loadSecurityLogs, 10000);
    </script>
</body>
</html>
