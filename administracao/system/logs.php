<?php
// admin/logs.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('audit')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch logs with admin details
$query = "SELECT l.*, u.full_name, u.profile_pic 
          FROM audit_logs l 
          LEFT JOIN users u ON l.admin_id = u.user_id 
          ORDER BY l.created_at DESC LIMIT 200";
$logs = $db->query($query)->fetchAll(PDO::FETCH_ASSOC);

function getActionColor($action) {
    $action = strtolower($action);
    if (strpos($action, 'delete') !== false || strpos($action, 'rejeitar') !== false) return '#f87171'; // Red
    if (strpos($action, 'create') !== false || strpos($action, 'add') !== false || strpos($action, 'send') !== false) return '#34d399'; // Green
    if (strpos($action, 'toggle') !== false || strpos($action, 'update') !== false || strpos($action, 'verify') !== false) return '#60a5fa'; // Blue
    if (strpos($action, 'invest') !== false || strpos($action, 'assign') !== false) return '#fbbf24'; // Gold
    return '#94a3b8'; // Default Gray
}

function time_elapsed_string($datetime, $full = false) {
    $now = new DateTime;
    $ago = new DateTime($datetime);
    $diff = $now->diff($ago);
    $diff->w = floor($diff->d / 7);
    $diff->d -= $diff->w * 7;
    $string = array(
        'y' => 'ano', 'm' => 'mês', 'w' => 'semana', 'd' => 'dia', 'h' => 'hora', 'i' => 'minuto', 's' => 'segundo',
    );
    foreach ($string as $k => &$v) {
        if ($diff->$k) {
            $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? ($k == 'm' ? 'es' : 's') : '');
        } else { unset($string[$k]); }
    }
    if (!$full) $string = array_slice($string, 0, 1);
    return $string ? 'há ' . implode(', ', $string) : 'agora mesmo';
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Histórico de Auditoria - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        .log-badge { font-size: 0.65rem; font-weight: 900; padding: 4px 10px; border-radius: 6px; text-transform: uppercase; letter-spacing: 0.5px; }
        @keyframes pulse-green { 0% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0.4); } 70% { box-shadow: 0 0 0 10px rgba(16, 185, 129, 0); } 100% { box-shadow: 0 0 0 0 rgba(16, 185, 129, 0); } }
        .live-status { display: flex; align-items: center; gap: 0.5rem; background: rgba(16, 185, 129, 0.1); color: #10b981; padding: 0.5rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 800; border: 1px solid rgba(16, 185, 129, 0.1); }
        .live-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse-green 2s infinite; }
    </style>
</head>
<body class="<?= isset($_COOKIE['sidebar_collapsed']) && $_COOKIE['sidebar_collapsed'] == 'true' ? 'sidebar-collapsed' : '' ?>">

    <!-- Sidebar Admin -->
    <?php include '../barra_lateral.php'; ?>

    <!-- Main Content -->
    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <h1>Auditoria de Sistema</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Transparência absoluta e registo temporal de todas as atividades críticas.</p>
            </div>
            <div style="display: flex; gap: 0.75rem;">
                <a href="export_logs.php?format=view" target="_blank" class="btn-admin" style="background: rgba(255,255,255,0.05); color: #fff; padding: 0.75rem 1.5rem; border: 1px solid rgba(255,255,255,0.1);">
                    <i class="fas fa-file-pdf"></i> PDF
                </a>
                <a href="export_logs.php?format=csv" class="btn-admin btn-admin-primary" style="padding: 0.75rem 1.5rem;">
                    <i class="fas fa-file-csv"></i> CSV
                </a>
            </div>
        </header>

        <!-- Filtros e Busca -->
        <div class="admin-card-premium" style="margin-bottom: 2rem; padding: 1.25rem;">
            <div style="display: flex; gap: 1rem; align-items: center;">
                <div style="flex: 1; position: relative;">
                    <i class="fas fa-search" style="position: absolute; left: 1rem; top: 50%; transform: translateY(-50%); color: rgba(255,255,255,0.2);"></i>
                    <input type="text" id="logSearch" placeholder="Pesquisar por admin, ação ou detalhes técnicos..." 
                           style="width: 100%; padding: 0.75rem 0.75rem 0.75rem 2.8rem; background: rgba(0,0,0,0.2); border: 1px solid rgba(255,255,255,0.05); border-radius: 12px; color: #fff; outline: none; transition: 0.3s; font-size: 0.9rem;">
                </div>
                <div style="background: rgba(255, 255, 255, 0.03); padding: 0.75rem 1.25rem; border-radius: 12px; border: 1px solid rgba(255,255,255,0.05); font-size: 0.85rem; color: #fff; font-weight: 700;">
                    <span style="color: rgba(255,255,255,0.3); margin-right: 0.5rem;">TOTAL:</span>
                    <span id="logCount"><?php echo count($logs); ?></span> EVENTOS
                </div>
            </div>
        </div>

        <!-- Tabela de Auditoria -->
        <div class="admin-card-premium" style="padding: 0;">
            <div class="table-container">
                <table class="aksanti-table" id="logsTable">
                    <thead>
                        <tr>
                            <th>Cronologia</th>
                            <th>Responsável</th>
                            <th>Ação</th>
                            <th>Evidências / Detalhes</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach($logs as $log): ?>
                        <tr class="log-row">
                            <td style="width: 160px;">
                                <div style="font-weight: 800; font-size: 0.8rem; color: #fff;"><?= date('d M, Y', strtotime($log['created_at'])) ?></div>
                                <div style="font-size: 0.65rem; color: rgba(247, 148, 29, 0.8); font-weight: 700; text-transform: uppercase;">
                                    <?= date('H:i:s', strtotime($log['created_at'])) ?> • <?= time_elapsed_string($log['created_at']) ?>
                                </div>
                            </td>
                            <td style="width: 250px;">
                                <div style="display: flex; align-items: center; gap: 0.75rem;">
                                    <img src="<?= ($log['profile_pic'] && $log['profile_pic'] != 'default_profile.png') ? '../' . $log['profile_pic'] : '../../recursos/images/default_profile.png' ?>" 
                                         style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                    <div>
                                        <div style="font-weight: 800; color: #fff; font-size: 0.85rem;"><?= htmlspecialchars($log['full_name'] ?? 'Administrador') ?></div>
                                        <div style="font-size: 0.6rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase;">ID: #<?= $log['admin_id'] ?? 'SYS' ?></div>
                                    </div>
                                </div>
                            </td>
                            <td style="width: 180px;">
                                <span class="log-badge" style="background: <?= getActionColor($log['action']) ?>15; color: <?= getActionColor($log['action']) ?>; border: 1px solid <?= getActionColor($log['action']) ?>25;">
                                    <?= htmlspecialchars($log['action']) ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-size: 0.85rem; color: rgba(255,255,255,0.5); line-height: 1.5; font-family: 'Inter', sans-serif;">
                                    <?= htmlspecialchars($log['details']) ?>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </main>

    <script>
    let currentSearch = "";

    function updateLogs() {
        fetch('../../interface_programacao/system/get_admin_logs.php')
            .then(res => res.json())
            .then(data => {
                if (data.success) {
                    const tbody = document.querySelector('#logsTable tbody');
                    const countEl = document.getElementById('logCount');
                    
                    tbody.innerHTML = data.logs.map(log => {
                        const color = getActionColor(log.action);
                        const agoText = timeElapsed(log.created_at);
                        const pPic = log.profile_pic ? `../${log.profile_pic}` : '../../recursos/images/default_profile.png';
                        
                        const rowText = `${log.full_name} ID: #${log.admin_id} ${log.action} ${log.details}`.toLowerCase();
                        const displayStyle = rowText.includes(currentSearch) ? '' : 'none';

                        return `
                            <tr class="log-row" style="display: ${displayStyle}">
                                <td style="width: 160px;">
                                    <div style="font-weight: 800; font-size: 0.8rem; color: #fff;">${new Date(log.created_at).toLocaleDateString('pt-PT', {day:'2-digit', month:'short', year:'numeric'})}</div>
                                    <div style="font-size: 0.65rem; color: rgba(247, 148, 29, 0.8); font-weight: 700; text-transform: uppercase;">
                                        ${new Date(log.created_at).toLocaleTimeString()} • ${agoText}
                                    </div>
                                </td>
                                <td style="width: 250px;">
                                    <div style="display: flex; align-items: center; gap: 0.75rem;">
                                        <img src="${pPic}" style="width: 32px; height: 32px; border-radius: 8px; object-fit: cover; border: 1px solid rgba(255,255,255,0.1);">
                                        <div>
                                            <div style="font-weight: 800; color: #fff; font-size: 0.85rem;">${log.full_name || 'Administrador'}</div>
                                            <div style="font-size: 0.6rem; color: rgba(255,255,255,0.3); font-weight: 800; text-transform: uppercase;">ID: #${log.admin_id || 'SYS'}</div>
                                        </div>
                                    </div>
                                </td>
                                <td style="width: 180px;">
                                    <span class="log-badge" style="background: ${color}15; color: ${color}; border: 1px solid ${color}25;">
                                        ${log.action}
                                    </span>
                                </td>
                                <td>
                                    <div style="font-size: 0.85rem; color: rgba(255,255,255,0.5); line-height: 1.5;">
                                        ${log.details}
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                    
                    countEl.innerText = data.logs.length;
                }
            });
    }

    function getActionColor(action) {
        action = action.toLowerCase();
        if (action.includes('delete') || action.includes('rejeitar')) return '#f87171';
        if (action.includes('create') || action.includes('add') || action.includes('send')) return '#34d399';
        if (action.includes('toggle') || action.includes('update') || action.includes('verify')) return '#60a5fa';
        if (action.includes('invest') || action.includes('assign')) return '#fbbf24';
        return '#94a3b8';
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
        if (interval > 1) return 'há ' + Math.floor(interval) + ' minutos';
        return 'agora mesmo';
    }

    document.getElementById('logSearch').addEventListener('input', function(e) {
        currentSearch = e.target.value.toLowerCase();
        const rows = document.querySelectorAll('#logsTable tbody tr');
        rows.forEach(row => {
            const text = row.innerText.toLowerCase();
            row.style.display = text.includes(currentSearch) ? '' : 'none';
        });
    });

    setInterval(updateLogs, 15000);
    </script>
</body>
</html>

