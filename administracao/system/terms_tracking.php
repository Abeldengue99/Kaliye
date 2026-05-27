<?php
// admin/terms_tracking.php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Auth check
if (!isAdmin() || !hasPermission('settings')) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

if (!hasPermission('settings')) { // Rastreio de termos as a privacy/security feature often falls under settings or its own permission
     // header("Location: index.php"); 
     // exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Fetch users with terms acceptance data
$query = "SELECT user_id, full_name, email, user_type, 
                 terms_accepted, terms_accepted_at, acceptance_ip,
                 privacy_accepted, privacy_accepted_at,
                 created_at
          FROM users 
          ORDER BY created_at DESC";
$stmt = $db->prepare($query);
$stmt->execute();
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Calculate Stats
$total_users = count($users);
$terms_accepted = 0;
$privacy_accepted = 0;
$legacy_users = 0;

foreach ($users as $user) {
    if ($user['terms_accepted']) $terms_accepted++;
    if ($user['privacy_accepted']) $privacy_accepted++;
    if (!$user['terms_accepted'] || !$user['privacy_accepted']) $legacy_users++;
}

$terms_pct = $total_users > 0 ? round(($terms_accepted / $total_users) * 100) : 0;
$privacy_pct = $total_users > 0 ? round(($privacy_accepted / $total_users) * 100) : 0;

$user_type_labels = [
    'univ_student' => 'Estudante Univ.',
    'high_student' => 'Estudante Médio',
    'mentor'       => 'Mentor',
    'investor'     => 'Investidor',
    'admin'        => 'Admin',
    'school_admin' => 'Admin Escola'
];
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <title>Conformidade Legal | KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;600;700;800&display=swap" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <style>
        :root {
            --accent-orange: #f7941d;
            --accent-blue: #3b82f6;
            --accent-emerald: #10b981;
            --glass-bg: rgba(255, 255, 255, 0.03);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-dim: #94a3b8;
        }

        * {
            box-sizing: border-box;
            font-family: 'Outfit', sans-serif;
        }

        body {
            margin: 0;
            background: #020617;
            color: var(--text-main);
            display: flex;
            min-height: 100vh;
            overflow-x: hidden;
        }

        .admin-main-content {
            flex-grow: 1;
            padding: 3rem;
            max-width: 1600px;
            margin-left: 260px; /* Sidebar default width */
            transition: all 0.3s ease;
            position: relative;
        }

        body.sidebar-collapsed .admin-main-content {
            margin-left: 70px;
        }

        @media (max-width: 768px) {
            .admin-main-content {
                margin-left: 0 !important;
                padding: 1.5rem;
                padding-top: 5rem;
            }
        }

        .header-section {
            display: flex;
            justify-content: space-between;
            align-items: flex-end;
            margin-bottom: 3rem;
            animation: slideDown 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes slideDown {
            from { opacity: 0; transform: translateY(-20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .welcome-text h1 {
            font-size: 2.8rem;
            font-weight: 800;
            margin: 0;
            background: linear-gradient(135deg, #fff 0%, #64748b 100%);
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            letter-spacing: -1px;
        }

        .welcome-text p {
            color: var(--text-dim);
            font-size: 1.1rem;
            margin: 0.5rem 0 0 0;
        }

        /* Stats Grid */
        .stats-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(280px, 1fr));
            gap: 1.5rem;
            margin-bottom: 3rem;
        }

        .stat-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 24px;
            padding: 1.75rem;
            display: flex;
            align-items: center;
            gap: 1.5rem;
            transition: all 0.4s cubic-bezier(0.4, 0, 0.2, 1);
            position: relative;
            overflow: hidden;
        }

        .stat-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: linear-gradient(45deg, transparent, rgba(255, 255, 255, 0.03), transparent);
            transform: translateX(-100%);
            transition: 0.6s;
        }

        .stat-card:hover {
            transform: translateY(-5px);
            border-color: rgba(247, 148, 29, 0.3);
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.4);
        }

        .stat-card:hover::before {
            transform: translateX(100%);
        }

        .stat-icon {
            width: 60px;
            height: 60px;
            border-radius: 18px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.5rem;
            background: rgba(255, 255, 255, 0.05);
        }

        .stat-data {
            display: flex;
            flex-direction: column;
        }

        .stat-label {
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--text-dim);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .stat-value {
            font-size: 2rem;
            font-weight: 800;
            color: #fff;
            margin-top: 0.2rem;
        }

        /* Table Container */
        .table-container {
            background: var(--glass-bg);
            backdrop-filter: blur(20px);
            border: 1px solid var(--glass-border);
            border-radius: 30px;
            padding: 1.5rem;
            box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
            animation: fadeIn 1s ease-out 0.2s both;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(20px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .table-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.5rem;
            padding: 0 0.5rem;
        }

        .search-box {
            position: relative;
            min-width: 320px;
        }

        .search-box i {
            position: absolute;
            left: 1.25rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-dim);
            font-size: 0.9rem;
        }

        .search-box input {
            width: 100%;
            padding: 0.85rem 1rem 0.85rem 3rem;
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            border-radius: 16px;
            color: #fff;
            outline: none;
            transition: 0.3s;
            font-size: 0.95rem;
        }

        .search-box input:focus {
            border-color: var(--accent-orange);
            background: rgba(255, 255, 255, 0.08);
            box-shadow: 0 0 0 4px rgba(247, 148, 29, 0.15);
        }

        table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0 0.5rem;
        }

        th {
            padding: 1rem 1.5rem;
            text-align: left;
            color: var(--text-dim);
            font-weight: 600;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1px;
        }

        tbody tr {
            transition: 0.3s;
            background: rgba(255, 255, 255, 0.01);
            border-radius: 16px;
        }

        tbody tr:hover {
            background: rgba(255, 255, 255, 0.03);
            transform: scale(1.005);
        }

        td {
            padding: 1.25rem 1.5rem;
            vertical-align: middle;
        }

        td:first-child { border-top-left-radius: 16px; border-bottom-left-radius: 16px; }
        td:last-child { border-top-right-radius: 16px; border-bottom-right-radius: 16px; }

        .user-block {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .avatar {
            width: 48px;
            height: 48px;
            background: linear-gradient(135deg, var(--accent-orange), #fcd34d);
            border-radius: 15px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 800;
            color: #000;
            font-size: 1.2rem;
            box-shadow: 0 4px 15px rgba(247, 148, 29, 0.3);
        }

        .user-info h4 {
            margin: 0;
            font-size: 1rem;
            font-weight: 700;
            color: #fff;
        }

        .user-info span {
            font-size: 0.85rem;
            color: var(--text-dim);
        }

        .badge {
            padding: 0.4rem 0.8rem;
            border-radius: 10px;
            font-size: 0.75rem;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .badge-outline {
            background: rgba(255, 255, 255, 0.05);
            border: 1px solid var(--glass-border);
            color: var(--text-dim);
        }

        .status-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            border-radius: 12px;
            font-size: 0.85rem;
            font-weight: 600;
        }

        .status-on { border: 1px solid rgba(16, 185, 129, 0.2); background: rgba(16, 185, 129, 0.08); color: #34d399; }
        .status-off { border: 1px solid rgba(239, 68, 68, 0.2); background: rgba(239, 68, 68, 0.08); color: #f87171; }

        .ip-log {
            font-family: 'JetBrains Mono', 'Courier New', monospace;
            font-size: 0.8rem;
            color: var(--accent-orange);
            background: rgba(247, 148, 29, 0.05);
            padding: 0.25rem 0.6rem;
            border-radius: 6px;
        }

        .footer-banner {
            margin-top: 2.5rem;
            background: linear-gradient(90deg, rgba(247, 148, 29, 0.1) 0%, transparent 100%);
            border-left: 4px solid var(--accent-orange);
            padding: 2.5rem;
            border-radius: 24px;
            display: flex;
            align-items: center;
            gap: 2rem;
            animation: fadeIn 1.2s ease-out 0.4s both;
        }

        .footer-icon {
            width: 70px;
            height: 70px;
            background: rgba(247, 148, 29, 0.1);
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 1.75rem;
            color: var(--accent-orange);
            flex-shrink: 0;
            box-shadow: 0 0 30px rgba(247, 148, 29, 0.1);
        }

        .export-btn {
            background: #fff;
            color: #000;
            padding: 0.9rem 1.8rem;
            border-radius: 16px;
            border: none;
            font-weight: 800;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
            cursor: pointer;
            transition: 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            box-shadow: 0 10px 25px rgba(255, 255, 255, 0.1);
        }

        .export-btn:hover {
            background: var(--accent-orange);
            color: #fff;
            transform: translateY(-3px) scale(1.02);
            box-shadow: 0 15px 30px rgba(247, 148, 29, 0.3);
        }

        /* Responsive Fixes */
        @media (max-width: 1200px) {
            .header-section { flex-direction: column; align-items: flex-start; gap: 1.5rem; }
            .export-btn { width: 100%; justify-content: center; }
        }
    </style>
</head>
<body class="<?php echo isset($_COOKIE['sidebarCollapsed']) && $_COOKIE['sidebarCollapsed'] === 'true' ? 'sidebar-collapsed' : ''; ?>">
    
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <section class="header-section">
            <div class="welcome-text">
                <p>REGISTOS DE CONFORMIDADE</p>
                <h1>Rastreio de Termos</h1>
            </div>
            
            <button class="export-btn" onclick="exportData()">
                <i class="fas fa-file-download"></i>
                Exportar Relatório CSV
            </button>
        </section>

        <!-- Dynamic Stats Card Grid -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon" style="color: #60a5fa;"><i class="fas fa-users"></i></div>
                <div class="stat-data">
                    <span class="stat-label">Utilizadores Totais</span>
                    <span class="stat-value"><?php echo $total_users; ?></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #34d399;"><i class="fas fa-check-double"></i></div>
                <div class="stat-data">
                    <span class="stat-label">Impacto Aceitação</span>
                    <span class="stat-value"><?php echo $terms_pct; ?>% <small style="font-size: 0.8rem; opacity: 0.6;">Global</small></span>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon" style="color: #fca5a5;"><i class="fas fa-user-clock"></i></div>
                <div class="stat-data">
                    <span class="stat-label">Log de Legado</span>
                    <span class="stat-value"><?php echo $legacy_users; ?> <small style="font-size: 0.8rem; opacity: 0.6;">Pendentes</small></span>
                </div>
            </div>
        </div>

        <!-- Compliance Table -->
        <div class="table-container">
            <div class="table-header">
                <h3 style="margin: 0; font-size: 1.3rem; letter-spacing: -0.5px;">Logs de Consentimento Ativos</h3>
                <div class="search-box">
                    <i class="fas fa-search"></i>
                    <input type="text" id="complianceSearch" placeholder="Pesquisar por nome, email ou perfil...">
                </div>
            </div>

            <div style="overflow-x: auto;">
                <table id="logsTable">
                    <thead>
                        <tr>
                            <th>Identidade</th>
                            <th>Cargo / Perfil</th>
                            <th>Termos de Uso</th>
                            <th>Privacidade</th>
                            <th>Rastreio Digital</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($users as $user): ?>
                            <tr>
                                <td>
                                    <div class="user-block">
                                        <div class="avatar">
                                            <?php 
                                            $names = explode(' ', $user['full_name']);
                                            echo strtoupper(substr($names[0], 0, 1) . (count($names) > 1 ? substr($names[1], 0, 1) : ''));
                                            ?>
                                        </div>
                                        <div class="user-info">
                                            <h4><?php echo htmlspecialchars($user['full_name']); ?></h4>
                                            <span><?php echo htmlspecialchars($user['email']); ?></span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="badge badge-outline">
                                        <?php echo $user_type_labels[$user['user_type']] ?? $user['user_type']; ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if ($user['terms_accepted']): ?>
                                        <div class="status-pill status-on">
                                            <i class="fas fa-shield-check"></i>
                                            <span>Aceite em <?php echo date('d/m/Y', strtotime($user['terms_accepted_at'])); ?></span>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-pill status-off">
                                            <i class="fas fa-exclamation-triangle"></i>
                                            <span>Pendente</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php if ($user['privacy_accepted']): ?>
                                        <div class="status-pill status-on">
                                            <i class="fas fa-lock"></i>
                                            <span>Confirmado</span>
                                        </div>
                                    <?php else: ?>
                                        <div class="status-pill status-off">
                                            <i class="fas fa-history"></i>
                                            <span>Legacy Profile</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="display: flex; flex-direction: column; gap: 0.3rem;">
                                        <span class="ip-log"><?php echo $user['acceptance_ip'] ?: '0.0.0.0'; ?></span>
                                        <span style="font-size: 0.75rem; color: var(--text-dim);">ID: #<?php echo str_pad($user['user_id'], 4, '0', STR_PAD_LEFT); ?></span>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Compliance Notes -->
        <div class="footer-banner">
            <div class="footer-icon">
                <i class="fas fa-fingerprint"></i>
            </div>
            <div style="flex-grow: 1;">
                <h4 style="color: var(--accent-orange); margin: 0 0 0.5rem 0; font-size: 1.25rem; font-weight: 700;">SegurançaJurídica & RGPD</h4>
                <p style="color: var(--text-dim); margin: 0; font-size: 1rem; line-height: 1.6; max-width: 1000px;">
                    Este sistema utiliza <strong>Imutabilidade de Logs</strong> para garantir que cada aceitação de termos seja registada com carimbo de tempo inviolável e IP de origem. 
                    Utilizadores com status <em>"Pendente"</em> ou <em>"Legacy"</em> não possuem registo biométrico/digital de aceitação nesta versão e deverão ser redirecionados para o portal de atualização.
                </p>
            </div>
        </div>
    </main>

    <script>
        // Search functionality
        document.getElementById('complianceSearch').addEventListener('keyup', function() {
            const term = this.value.toLowerCase();
            const rows = document.querySelectorAll('#logsTable tbody tr');
            
            rows.forEach(row => {
                const text = row.textContent.toLowerCase();
                row.style.display = text.includes(term) ? '' : 'none';
                
                if (text.includes(term)) {
                    row.style.animation = 'pulseHighlight 0.5s ease';
                }
            });
        });

        function exportData() {
            Swal.fire({
                title: 'Gerar Relatório CSV?',
                text: "Será criado um ficheiro auditável com todos os dados de conformidade.",
                icon: 'info',
                showCancelButton: true,
                confirmButtonColor: '#f7941d',
                cancelButtonColor: rgba(255,255,255,0.05),
                confirmButtonText: 'Exportar Agora',
                background: '#020617',
                color: '#fff'
            }).then((result) => {
                if (result.isConfirmed) {
                    Swal.fire({
                        title: 'A compilar dados...',
                        timer: 2000,
                        timerProgressBar: true,
                        didOpen: () => { Swal.showLoading(); },
                        background: '#020617',
                        color: '#fff'
                    }).then(() => {
                        window.location.href = '../../interface_programacao/admin/export_terms_logs.php';
                    });
                }
            });
        }
    </script>
</body>
</html>




