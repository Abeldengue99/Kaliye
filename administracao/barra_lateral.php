<?php
// admin/barra_lateral.php (agora a Navbar de Topo do Admin)
require_once dirname(__DIR__) . '/inclusoes/auth_check.php';

// Auto-detect base_url if not defined to prevent broken headers in subfolders
if (!isset($base_url)) {
    $current_path = $_SERVER['PHP_SELF'];
    if (strpos($current_path, '/users/') !== false || 
        strpos($current_path, '/moderation/') !== false || 
        strpos($current_path, '/finance/') !== false ||
        strpos($current_path, '/system/') !== false ||
        strpos($current_path, '/newsletter/') !== false ||
        strpos($current_path, '/marketing/') !== false) {
        $base_url = '../../';
    } else {
        $base_url = '../';
    }
}

// Ensure admin_base is also consistent
if (!isset($admin_base)) {
    $admin_base = (strpos($_SERVER['PHP_SELF'], '/administracao/') !== false && 
                   (strpos($_SERVER['PHP_SELF'], '/users/') !== false || 
                    strpos($_SERVER['PHP_SELF'], '/moderation/') !== false ||
                    strpos($_SERVER['PHP_SELF'], '/finance/') !== false ||
                    strpos($_SERVER['PHP_SELF'], '/system/') !== false)) ? '../' : './';
}

// Busca as contagens de Badges
$badge_counts = ['kyc' => 0, 'mentors' => 0, 'investments' => 0, 'support' => 0, 'moderation' => 0, 'progress' => 0, 'chat_reports' => 0];
if (isset($db)) {
    try {
        $badge_counts['kyc'] = $db->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'")->fetchColumn();
        $badge_counts['mentors'] = $db->query("SELECT COUNT(*) FROM users WHERE mentor_status = 'pending'")->fetchColumn();
        $badge_counts['investments'] = $db->query("SELECT COUNT(*) FROM project_investments WHERE status = 'pending'")->fetchColumn();
        $badge_counts['support'] = $db->query("SELECT COUNT(*) FROM support_messages WHERE CAST(is_read AS INTEGER) = 0")->fetchColumn();
        $badge_counts['progress'] = $db->query("SELECT COUNT(*) FROM project_progress_reports WHERE report_status = 'pending_admin'")->fetchColumn();
         try {
             $badge_counts['moderation'] = $db->query("SELECT COUNT(*) FROM projects WHERE approval_status = 'pending'")->fetchColumn();
         } catch (Exception $e) {
             $badge_counts['moderation'] = $db->query("SELECT COUNT(*) FROM projects WHERE is_public = false")->fetchColumn();
         }
         try {
             $badge_counts['chat_reports'] = (int)$db->query("SELECT COUNT(*) FROM chat_reports WHERE status = 'pending'")->fetchColumn();
         } catch (Exception $e) {}
     } catch (Exception $e) {}
 }

function renderBadge($count, $color = '#f7941d', $id = '') {
    $display = ($count > 0) ? 'inline-block' : 'none';
    $idAttr = $id ? 'id="'.$id.'"' : '';
    return '<span '.$idAttr.' style="background: '.$color.'; color: #000; font-size: 0.65rem; font-weight: 900; padding: 2px 7px; border-radius: 6px; margin-left: 8px; box-shadow: 0 2px 10px rgba(247, 148, 29, 0.3); display: '.$display.';">'.$count.'</span>';
}

?>
<!-- Forçar o carregamento do cérebro responsivo em todas as páginas da administração -->
<link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/mobile-elite.css?v=<?php echo filemtime(__DIR__ . '/../recursos/css/mobile-elite.css'); ?>">

<style>
    /* Premium Top Nav for Admin */
    .admin-nav-container {
        position: fixed;
        top: 0; left: 0; right: 0;
        height: 70px;
        background: rgba(3, 7, 18, 0.85);
        backdrop-filter: blur(20px);
        -webkit-backdrop-filter: blur(20px);
        border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        z-index: 2000;
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 0 2rem;
        box-shadow: 0 10px 30px rgba(0, 0, 0, 0.3);
    }
    
    .admin-logo-wrapper {
        display: flex;
        align-items: center;
        gap: 0.75rem;
        text-decoration: none;
    }
    
    .admin-logo-icon {
        width: 36px; height: 36px;
        background: var(--aksanti-orange, #f7941d);
        border-radius: 8px;
        display: flex; align-items: center; justify-content: center;
        padding: 4px;
        box-shadow: 0 0 15px rgba(247, 148, 29, 0.3);
    }
    
    .admin-logo-text {
        font-family: 'Outfit', sans-serif;
        color: #fff;
        font-weight: 800;
        font-size: 1.2rem;
        letter-spacing: -0.5px;
    }
    
    .admin-nav-links {
        display: flex;
        align-items: center;
        gap: 1.5rem;
    }
    
    .admin-nav-item {
        position: relative;
        color: rgba(255,255,255,0.8);
        text-decoration: none;
        font-weight: 600;
        font-size: 0.9rem;
        display: flex;
        align-items: center;
        gap: 0.5rem;
        padding: 0.5rem;
        border-radius: 8px;
        transition: all 0.3s;
        cursor: pointer;
    }
    
    .admin-nav-item:hover, .admin-nav-item.active {
        color: #fff;
        background: rgba(255,255,255,0.05);
    }
    
    /* Dropdowns */
    .admin-dropdown-content {
        position: absolute;
        top: 100%; left: 0;
        margin-top: 10px;
        background: #0d1628;
        border: 1px solid rgba(247,148,29,0.2);
        box-shadow: 0 20px 50px rgba(0,0,0,0.5);
        border-radius: 16px;
        min-width: 220px;
        padding: 0.75rem;
        opacity: 0;
        visibility: hidden;
        transform: translateY(10px);
        transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        z-index: 2001;
    }
    
    .admin-nav-item.open .admin-dropdown-content {
        opacity: 1;
        visibility: visible;
        transform: translateY(0);
    }
    
    .admin-dropdown-content a {
        color: rgba(255,255,255,0.8);
        padding: 0.75rem 1rem;
        text-decoration: none;
        display: flex;
        align-items: center;
        gap: 0.75rem;
        font-size: 0.85rem;
        border-radius: 10px;
        transition: all 0.2s;
        font-weight: 500;
    }
    
    .admin-dropdown-content a i {
        width: 18px;
        text-align: center;
        font-size: 1rem;
        color: #94a3b8;
    }
    
    .admin-dropdown-content a:hover, .admin-dropdown-content a.active-link {
        background: rgba(247, 148, 29, 0.1);
        color: #f7941d;
    }
    .admin-dropdown-content a:hover i, .admin-dropdown-content a.active-link i { color: #f7941d; }

    /* Mobile Menu Toggle */
    .admin-mobile-toggle {
        display: none;
        background: transparent;
        border: none;
        color: #fff;
        font-size: 1.5rem;
        cursor: pointer;
    }
    
    @media (max-width: 1024px) {
        .admin-nav-links {
            position: absolute;
            top: 70px; left: 0; right: 0;
            background: #0d1628;
            border-bottom: 1px solid rgba(247,148,29,0.2);
            flex-direction: column;
            align-items: stretch;
            padding: 1rem;
            gap: 0;
            opacity: 0;
            visibility: hidden;
            transform: translateY(-10px);
            transition: all 0.3s cubic-bezier(0.16, 1, 0.3, 1);
        }
        .admin-nav-links.show {
            opacity: 1; visibility: visible; transform: translateY(0);
        }
        .admin-mobile-toggle { display: block; }
        .admin-dropdown-content {
            position: static;
            opacity: 1; visibility: visible; transform: none; display: none;
            box-shadow: none; border: none; background: rgba(0,0,0,0.2);
            margin-top: 0;
        }
        .admin-nav-item.open .admin-dropdown-content { display: block; }
    }
</style>

<nav class="admin-nav-container">
    <a href="<?= $admin_base ?>index.php" class="admin-logo-wrapper" style="text-decoration: none; display: flex; align-items: center; gap: 0.75rem;">
        <div class="admin-logo-icon" style="background: transparent; width: 42px; height: 42px; padding: 0; box-shadow: none; border: 1px solid rgba(255,255,255,0.15); border-radius: 10px; display: flex; align-items: center; justify-content: center; overflow: hidden;">
            <img src="<?= $base_url ?>recursos/images/marca/favicon-k-192x192.png" alt="KALIYE" style="width: 100%; height: 100%; object-fit: cover;">
        </div>
        <div class="admin-logo-text" style="display: flex; flex-direction: column; line-height: 1;">
            <span style="font-size: 1.35rem; color: #fff; font-weight: 800; font-family: 'Outfit', sans-serif;">KALIYE</span>
            <span style="color: #fff; opacity: 0.8; font-family: 'Inter', sans-serif; font-size: 0.55rem; font-weight: 800; text-transform: uppercase; letter-spacing: 2px; margin-top: 2px; text-shadow: 0 0 10px rgba(255,255,255,0.2);">Admin</span>
        </div>
    </a>

    
    <button class="admin-mobile-toggle" onclick="toggleAdminMobileMenu()"><i class="fas fa-bars"></i></button>
    
    <div class="admin-nav-links" id="adminNavLinks">
        
        <!-- ADMINISTRAÇÃO -->
        <div class="admin-nav-item" onclick="toggleAdminDropdown(this, event)">
            <i class="fas fa-shield-halved"></i> Administração <i class="fas fa-chevron-down" style="font-size: 0.6rem; transition: transform 0.3s;"></i>
            <div class="admin-dropdown-content">
                <a href="<?= $admin_base ?>index.php" class="<?= basename($_SERVER['PHP_SELF']) == 'index.php' ? 'active-link' : '' ?>"><i class="fas fa-chart-line"></i> Dashboard</a>
                <a href="<?= $admin_base ?>users/manage_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active-link' : '' ?>"><i class="fas fa-users"></i> Utilizadores</a>
                <a href="<?= $admin_base ?>users/admins.php" class="<?= basename($_SERVER['PHP_SELF']) == 'admins.php' ? 'active-link' : '' ?>"><i class="fas fa-user-shield"></i> Corpo Admin</a>
                <a href="<?= $admin_base ?>marketing/manage_ads.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_ads.php' ? 'active-link' : '' ?>"><i class="fas fa-bullhorn"></i> Publicidade</a>
                <a href="<?= $admin_base ?>newsletter/subscribers.php" class="<?= basename($_SERVER['PHP_SELF']) == 'subscribers.php' ? 'active-link' : '' ?>"><i class="fas fa-envelope-open-text"></i> Newsletter</a>
                <a href="<?= $admin_base ?>newsletter/broadcast.php" class="<?= basename($_SERVER['PHP_SELF']) == 'broadcast.php' ? 'active-link' : '' ?>"><i class="fas fa-bullhorn"></i> Enviar Broadcast</a>
                <a href="<?= $admin_base ?>moderation/chat_monitor.php" class="<?= basename($_SERVER['PHP_SELF']) == 'chat_monitor.php' ? 'active-link' : '' ?>"><i class="fas fa-eye"></i> Monitorizar Chats <?= renderBadge($badge_counts['chat_reports'], '#f7941d', 'badge-nav-chat-monitor') ?></a>
            </div>
        </div>

        <!-- GESTÃO DE FLUXO -->
        <div class="admin-nav-item" onclick="toggleAdminDropdown(this, event)">
            <i class="fas fa-tasks"></i> Gestão Operacional 
            <?php 
                $totalFlowBadges = $badge_counts['kyc'] + $badge_counts['mentors'] + $badge_counts['investments'] + $badge_counts['support'] + $badge_counts['moderation'] + $badge_counts['progress'] + $badge_counts['chat_reports'];
                if ($totalFlowBadges > 0) echo '<span style="width: 8px; height: 8px; background: #ef4444; border-radius: 50%; display: inline-block;"></span>';
            ?>
            <i class="fas fa-chevron-down" style="font-size: 0.6rem; transition: transform 0.3s;"></i>
            <div class="admin-dropdown-content">
                <a href="<?= $admin_base ?>moderation/doubts.php" class="<?= basename($_SERVER['PHP_SELF']) == 'doubts.php' ? 'active-link' : '' ?>"><i class="fas fa-question-circle"></i> Fórum / Dúvidas</a>
                <a href="<?= $admin_base ?>manage_progress.php" class="<?= basename($_SERVER['PHP_SELF']) == 'manage_progress.php' ? 'active-link' : '' ?>">
                    <i class="fas fa-spinner"></i> Revisão de Progresso <?= renderBadge($badge_counts['progress'], '#f7941d', 'badge-nav-progress') ?>
                </a>
                <a href="<?= $admin_base ?>moderation/moderation.php" class="<?= basename($_SERVER['PHP_SELF']) == 'moderation.php' ? 'active-link' : '' ?>">
                    <i class="fas fa-user-check"></i> Moderação Projectos <?= renderBadge($badge_counts['moderation'], '#f7941d', 'badge-nav-moderation') ?>
                </a>
                <a href="<?= $admin_base ?>users/kyc_requests.php" class="<?= basename($_SERVER['PHP_SELF']) == 'kyc_requests.php' ? 'active-link' : '' ?>">
                    <i class="fas fa-id-card"></i> Validar KYC <?= renderBadge($badge_counts['kyc'], '#f7941d', 'badge-nav-kyc') ?>
                </a>
                <a href="<?= $admin_base ?>users/mentor_applications.php" class="<?= basename($_SERVER['PHP_SELF']) == 'mentor_applications.php' ? 'active-link' : '' ?>">
                    <i class="fas fa-user-graduate"></i> Mentores <?= renderBadge($badge_counts['mentors'], '#f7941d', 'badge-nav-mentors') ?>
                </a>
                <a href="<?= $admin_base ?>users/verified_users.php" class="<?= basename($_SERVER['PHP_SELF']) == 'verified_users.php' ? 'active-link' : '' ?>"><i class="fas fa-crown"></i> Comunidade Elite</a>
                <a href="<?= $admin_base ?>finance/finance_dashboard.php" class="<?= basename($_SERVER['PHP_SELF']) == 'finance_dashboard.php' ? 'active-link' : '' ?>">
                    <i class="fas fa-file-invoice-dollar"></i> Financeiro <?= renderBadge($badge_counts['investments'], '#fbbf24', 'badge-nav-finance') ?>
                </a>
                <a href="<?= $admin_base ?>moderation/support.php" class="<?= basename($_SERVER['PHP_SELF']) == 'support.php' ? 'active-link' : '' ?>">
                    <i class="fas fa-headset"></i> Suporte <?= renderBadge($badge_counts['support'], '#f7941d', 'badge-nav-support') ?>
                </a>

            </div>
        </div>

        <!-- MONITORAMENTO -->
        <div class="admin-nav-item" onclick="toggleAdminDropdown(this, event)">
            <i class="fas fa-chart-pie"></i> Monitoramento <i class="fas fa-chevron-down" style="font-size: 0.6rem; transition: transform 0.3s;"></i>
            <div class="admin-dropdown-content">
                <a href="<?= $admin_base ?>system/telemetry.php" class="<?= basename($_SERVER['PHP_SELF']) == 'telemetry.php' ? 'active-link' : '' ?>"><i class="fas fa-satellite-dish"></i> Telemetria</a>
                <a href="<?= $admin_base ?>system/war_room.php" class="<?= basename($_SERVER['PHP_SELF']) == 'war_room.php' ? 'active-link' : '' ?>"><i class="fas fa-map-marked-alt" style="color: #f7941d;"></i> War Room Real-time</a>
                <a href="<?= $admin_base ?>system/logs.php" class="<?= basename($_SERVER['PHP_SELF']) == 'logs.php' ? 'active-link' : '' ?>"><i class="fas fa-fingerprint"></i> Auditoria</a>
                <a href="<?= $admin_base ?>project_analytics.php" class="<?= basename($_SERVER['PHP_SELF']) == 'project_analytics.php' ? 'active-link' : '' ?>"><i class="fas fa-chart-line"></i> Inteligência de Projectos</a>
                <a href="<?= $admin_base ?>moderation/evaluations.php" class="<?= basename($_SERVER['PHP_SELF']) == 'evaluations.php' ? 'active-link' : '' ?>"><i class="fas fa-star"></i> Feedback da Plataforma</a>
                                <a href="<?= $admin_base ?>system/content_audit.php" class="<?= basename($_SERVER['PHP_SELF']) == 'content_audit.php' ? 'active-link' : '' ?>"><i class="fas fa-language"></i> Auditoria Linguística</a>
                <a href="<?= $admin_base ?>system/reports.php" class="<?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active-link' : '' ?>"><i class="fas fa-file-invoice"></i> Relatórios Hub</a>
            </div>
        </div>

        <!-- CONFIGURAÇÕES E SAÍDA -->
        <div class="admin-nav-item" onclick="toggleAdminDropdown(this, event)">
            <div style="position: relative; display: flex; align-items: center;">
                <img src="<?= isset($_SESSION['user_pic']) ? $base_url.$_SESSION['user_pic'] : $base_url.'recursos/images/default_profile.png' ?>" 
                     onerror="this.src='<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png'; this.style.padding='4px'; this.style.background='#fff';"
                     style="width:28px; height:28px; border-radius:50%; object-fit:cover; border:1px solid rgba(255,255,255,0.2);">
            </div>
            Admin <i class="fas fa-chevron-down" style="font-size: 0.6rem; transition: transform 0.3s;"></i>
            <div class="admin-dropdown-content" style="right: 0; left: auto;">
                <a href="<?= $admin_base ?>system/settings.php" class="<?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active-link' : '' ?>"><i class="fas fa-sliders"></i> Definições de Plataforma</a>
                <hr style="border-top:1px solid rgba(255,255,255,0.05); margin:0.5rem 0;">
                <a href="<?= $base_url ?>index.php"><i class="fas fa-external-link-alt"></i> Voltar ao Portal</a>
                <a href="<?= $base_url ?>autenticacao/sair.php" style="color:#f87171; background:rgba(239, 68, 68, 0.05);"><i class="fas fa-power-off"></i> Encerrar Sessão</a>
            </div>
        </div>


    </div>
</nav>

<script>
function toggleAdminMobileMenu() {
    document.getElementById('adminNavLinks').classList.toggle('show');
}

function toggleAdminDropdown(element, event) {
    event.stopPropagation(); // Sempre bloquear propagação para evitar o fecho imediato pelo window.onclick
    
    // Close other dropdowns
    document.querySelectorAll('.admin-nav-item').forEach(item => {
        if(item !== element) {
            item.classList.remove('open');
            let icon = item.querySelector('.fa-chevron-down');
            if(icon) icon.style.transform = 'rotate(0deg)';
        }
    });
    
    element.classList.toggle('open');
    let icon = element.querySelector('.fa-chevron-down');
    if(icon) {
        icon.style.transform = element.classList.contains('open') ? 'rotate(180deg)' : 'rotate(0deg)';
    }
}

// Close Dropdowns globally
window.onclick = function(e) {
    if(!e.target.closest('.admin-nav-item')) {
        document.querySelectorAll('.admin-nav-item').forEach(item => {
            item.classList.remove('open');
            let icon = item.querySelector('.fa-chevron-down');
            if(icon) icon.style.transform = 'rotate(0deg)';
        });
    }
};

// Global Real-time Badges
function refreshAdminBadges() {
    fetch('<?= $base_url ?>interface_programacao/admin/get_badge_counts.php')
        .then(res => res.json())
        .then(data => {
            if (data.success) {
                updateBadgeElement('badge-nav-progress', data.counts.progress);
                updateBadgeElement('badge-nav-moderation', data.counts.moderation);
                updateBadgeElement('badge-nav-kyc', data.counts.kyc);
                updateBadgeElement('badge-nav-mentors', data.counts.mentors);
                updateBadgeElement('badge-nav-finance', data.counts.investments);
                updateBadgeElement('badge-nav-support', data.counts.support);
                updateBadgeElement('badge-nav-chat-monitor', data.counts.chat_reports);
            }
        }).catch(err => console.debug('Badge refresh error:', err));
}

function updateBadgeElement(id, count) {
    const el = document.getElementById(id);
    if (!el) return;
    if (count > 0) {
        el.innerText = count;
        el.style.display = 'inline-block';
    } else {
        el.style.display = 'none';
    }
}

document.addEventListener('DOMContentLoaded', () => {
    refreshAdminBadges();
    setInterval(refreshAdminBadges, 15000); // Polling every 15s for high performance
    
    document.querySelectorAll('.admin-dropdown-content').forEach(dropdown => {
        if(dropdown.querySelector('.active-link')) {
            dropdown.parentElement.classList.add('active'); // highlight parent
        }
    });
});
</script>
<!-- ==============================================
     NOVA BARRA INFERIOR PARA ADMINISTRAÇÃO (MOBILE-ELITE)
     ============================================== -->
<nav class="bottom-nav admin-bottom-nav">
    <a href="<?php echo $admin_base; ?>index.php" class="bottom-nav-item <?php echo (basename($_SERVER['PHP_SELF']) == 'index.php') ? 'active' : ''; ?>">
        <i class="fas fa-home"></i>
        <span>Início</span>
    </a>
    <a href="<?php echo $admin_base; ?>users/manage_users.php" class="bottom-nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/users/') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-users"></i>
        <span>Membros</span>
        <span id="mobile-badge-users" style="position: absolute; top: 2px; right: 20%; background: #ef4444; width: 8px; height: 8px; border-radius: 50%; display: none; box-shadow: 0 0 10px #ef4444;"></span>
    </a>
    <a href="<?php echo $admin_base; ?>finance/finances.php" class="bottom-nav-item <?php echo (strpos($_SERVER['PHP_SELF'], '/finance/') !== false) ? 'active' : ''; ?>">
        <i class="fas fa-wallet"></i>
        <span>Finanças</span>
        <span id="mobile-badge-finance" style="position: absolute; top: 2px; right: 20%; background: #ef4444; width: 8px; height: 8px; border-radius: 50%; display: none; box-shadow: 0 0 10px #ef4444;"></span>
    </a>
    <a href="javascript:void(0)" onclick="openAdminMobileMenu()" class="bottom-nav-item">
        <i class="fas fa-bars"></i>
        <span>Menu</span>
    </a>
</nav>

<style>
/* CSS para ocultar a barra de topo no mobile e mostrar a bottom nav */
.admin-bottom-nav { display: none !important; }
@media (max-width: 768px) {
    .admin-nav-container { display: none !important; }
    .admin-bottom-nav { display: grid !important; } /* Usa o grid definido globalmente em .bottom-nav */
}

/* Estilos para o Modal Action Sheet (SweetAlert2) da Administração */
.elite-mobile-menu {
    display: flex;
    flex-direction: column;
    gap: 0.65rem;
    text-align: left;
}
.elite-menu-btn {
    display: flex;
    align-items: center;
    gap: 1rem;
    padding: 1rem;
    background: rgba(255,255,255,0.02);
    border: 1px solid rgba(255,255,255,0.05);
    border-radius: 16px;
    text-decoration: none;
    color: #fff;
    transition: 0.3s;
}
.elite-menu-btn:active {
    background: rgba(255,255,255,0.05);
    transform: scale(0.98);
}
.menu-icon {
    width: 42px; height: 42px;
    border-radius: 12px;
    background: rgba(255,255,255,0.05);
    display: flex; align-items: center; justify-content: center;
    font-size: 1.25rem;
    flex-shrink: 0;
}
.menu-text {
    display: flex;
    flex-direction: column;
    gap: 2px;
}
.menu-text strong {
    font-size: 1rem;
    font-weight: 700;
}
.menu-text span {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.5);
    line-height: 1.2;
}
</style>

<script>
function openAdminMobileMenu() {
    Swal.fire({
        title: 'Menu Administrativo',
        html: `
            <div class="elite-mobile-menu">
                <a href="<?php echo $admin_base; ?>moderation/moderation.php" class="elite-menu-btn">
                    <div class="menu-icon"><i class="fas fa-shield-alt"></i></div>
                    <div class="menu-text">
                        <strong>Moderação</strong>
                        <span>Projectos e Suporte</span>
                    </div>
                </a>
                <a href="<?php echo $admin_base; ?>manage_progress.php" class="elite-menu-btn">
                    <div class="menu-icon" style="background: rgba(247, 148, 29, 0.1); color: #f7941d;"><i class="fas fa-tasks"></i></div>
                    <div class="menu-text">
                        <strong>Trilhas & Tarefas</strong>
                        <span>Mentoria e Acompanhamento</span>
                    </div>
                </a>
                <a href="<?php echo $admin_base; ?>newsletter/broadcast.php" class="elite-menu-btn">
                    <div class="menu-icon" style="background: rgba(16, 185, 129, 0.1); color: #10b981;"><i class="fas fa-envelope-open-text"></i></div>
                    <div class="menu-text">
                        <strong>Comunicação</strong>
                        <span>Newsletters e Divulgações</span>
                    </div>
                </a>
                <a href="<?php echo $admin_base; ?>system/settings.php" class="elite-menu-btn">
                    <div class="menu-icon" style="background: rgba(148, 163, 184, 0.1); color: #94a3b8;"><i class="fas fa-cogs"></i></div>
                    <div class="menu-text">
                        <strong>Sistema</strong>
                        <span>Configurações Globais</span>
                    </div>
                </a>
                <div style="height: 1px; background: rgba(255,255,255,0.05); margin: 15px 0;"></div>
                <a href="<?php echo $base_url; ?>index.php" class="elite-menu-btn" style="border-color: rgba(59, 130, 246, 0.2);">
                    <div class="menu-icon" style="background: rgba(59, 130, 246, 0.1); color: #3b82f6;"><i class="fas fa-desktop"></i></div>
                    <div class="menu-text">
                        <strong style="color: #3b82f6;">Sair da Administração</strong>
                        <span>Voltar à plataforma pública</span>
                    </div>
                </a>
                <a href="<?php echo $base_url; ?>autenticacao/sair.php" class="elite-menu-btn" style="border-color: rgba(239, 68, 68, 0.2); margin-top: 8px;">
                    <div class="menu-icon" style="background: rgba(239, 68, 68, 0.1); color: #ef4444;"><i class="fas fa-power-off"></i></div>
                    <div class="menu-text">
                        <strong style="color: #ef4444;">Terminar Sessão</strong>
                        <span>Sair em segurança</span>
                    </div>
                </a>
            </div>
        `,
        showConfirmButton: false,
        showCloseButton: true,
        background: '#0d1628',
        color: '#fff',
        position: 'bottom',
        width: '100%',
        margin: 0,
        padding: '20px 20px 40px 20px',
        customClass: {
            popup: 'elite-action-sheet',
            closeButton: 'elite-swal-close',
            backdrop: 'swal-backdrop-intense-blur'
        },
        showClass: { popup: 'animate__animated animate__slideInUp animate__faster' },
        hideClass: { popup: 'animate__animated animate__slideOutDown animate__faster' }
    });
}

// Extensão do sistema de Badges para a nova barra
document.addEventListener('DOMContentLoaded', () => {
    const originalRefresh = window.refreshAdminBadges;
    if (typeof originalRefresh === 'function') {
        window.refreshAdminBadges = function() {
            originalRefresh(); // Chama o atual
            // Adiciona a injeção nas "bolinhas vermelhas" invisíveis da bottom nav
            fetch('<?php echo $base_url; ?>interface_programacao/admin/get_badge_counts.php')
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        const mUsers = document.getElementById('mobile-badge-users');
                        const mFin = document.getElementById('mobile-badge-finance');
                        if(mUsers) mUsers.style.display = (data.counts.kyc > 0 || data.counts.mentors > 0) ? 'block' : 'none';
                        if(mFin) mFin.style.display = (data.counts.investments > 0) ? 'block' : 'none';
                    }
                }).catch(()=>{});
        };
    }
});
</script>
