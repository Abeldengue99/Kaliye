<?php
/**
 * inclusoes/components/header/navbar.php - Sistema de Navegação Principal
 */

$dashboard_link = $base_url . 'index.php';
$current_page = basename($_SERVER['PHP_SELF']);

// n_count e final_pic já vêm do logic.php ou calculamos aqui se necessário
$display_pic = isset($header_user_pic) ? $header_user_pic : getUserAvatarUrl($user_role ?? 'student', $m_status ?? 'unsubmitted');
$display_pic_src = (strpos($display_pic, 'http') === 0) ? $display_pic : $base_url . $display_pic;

// ── Lógica de Permissões por Perfil  ──
$user_role   = $_SESSION['user_type'] ?? 'student';
$m_status    = $_SESSION['mentor_status'] ?? 'unsubmitted';

$is_student    = (strpos($user_role, 'student') !== false);
// Mentor Oficial: Apenas se o tipo for mentor E estiver aprovado OU se for estudante E estiver aprovado
$is_mentor     = ($user_role === 'mentor' || $is_student) && ($m_status === 'approved');
$is_investor   = ($user_role === 'investor');
$is_admin      = ($user_role === 'admin' || $user_role === 'superadmin');
$is_mixed      = ($is_student && ($m_status === 'approved')); // Estudante que também é Mentor
if ($is_mentor || $is_admin) {
    $mentorship_link = $base_url . 'paginas/mentoria/mentorship.php';
} elseif ($is_student) {
    $mentorship_link = $base_url . 'paginas/explorar/explore_mentors.php';
} else {
    $mentorship_link = $base_url . 'paginas/explorar/explore_mentors.php';
}
?>

<nav class="nav-container navbar-header">
        <!-- Grupo Logo e Pesquisa -->
        <div class="header-main-box">
            <a href="<?php echo $dashboard_link; ?>" class="marca-box header-logo">
                <div class="logo-symbol">
                    <img src="<?php echo $base_url; ?>recursos/images/marca/logotipo.png" alt="KALIYE">
                </div>
            </a>

            <!-- Campo de Pesquisa -->
            <div class="header-search">
                <div class="search-input-field">
                    <i class="fas fa-search"></i>
                    <input type="text" id="globalSearchInput" placeholder="Pesquisar..." onfocus="if(enforceKYC()) showCommandCenter()" onkeyup="handleGlobalSearch(event)" autocomplete="off">
                    <!-- Painel de Comandos Inteligente -->
                    <div id="commandCenterDropdown" class="command-dropdown glass-effect">
                        <div class="command-section">
                            <span class="command-section-title">Ações Rápidas</span>
                            <div id="quickActionsList">
                                <!-- Preenchido via JS -->
                            </div>
                        </div>
                        <div class="command-divider"></div>
                        <div class="command-section">
                            <span class="command-section-title">Resultados da Busca</span>
                            <div id="searchResultsList">
                                <div class="command-item empty">Pesquise por projectos ou pessoas...</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Links de Navegação -->
        <div class="navbar-links" id="nav-links">
            <a href="<?php echo $dashboard_link; ?>" class="nav-link <?php echo ($current_page == 'index.php') ? 'active' : ''; ?>">
                <i class="fas fa-th-large"></i> Home
            </a>

            <!-- Menu: Explorar (Discovery & Ecosystem) -->
            <div class="nav-dropdown-modal">
                <a href="javascript:void(0)" onclick="openExplorarModal()" class="nav-link">
                    <i class="fas fa-compass"></i> Explorar
                </a>
            </div>

            <!-- Menu: Mentorias (Education & Growth) -->
            <?php if (($_SESSION['user_type'] ?? '') !== 'investor'): ?>
            <div class="nav-dropdown-modal">
                <a href="javascript:void(0)" onclick="openMentoriaModal()" class="nav-link">
                    <i class="fas fa-chalkboard-teacher"></i> Mentorias
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- Ações da Direita -->
        <div class="header-actions">
            <!-- Ícone de Pesquisa (Apenas Mobile) -->
            <a href="javascript:void(0)" class="btn-action mobile-only-search" onclick="if(!enforceKYC(event)) return false; toggleMobileSearch()" title="Pesquisar">
                <i class="fas fa-search"></i>
            </a>

            <div class="action-group">
                <button type="button" class="btn-action btn-action--theme" data-pref-theme-toggle title="Modo claro/escuro" aria-label="Modo claro/escuro">
                    <i class="fas fa-circle-half-stroke"></i>
                </button>

                <!-- Canal de Mensagens -->
                <a href="<?php echo $base_url; ?>paginas/social/messages.php" onclick="return enforceKYC(event);" class="btn-action btn-action--msg" title="Mensagens">
                    <i class="fas fa-comment-dots"></i>
                    <?php if (isset($m_count) && (int)$m_count > 0): ?>
                        <span class="elite-badge-pulse badge-msg" id="msgBadge"><?php echo (int)$m_count; ?></span>
                    <?php else: ?>
                        <span class="elite-badge-pulse badge-msg" id="msgBadge" style="display:none;"></span>
                    <?php endif; ?>
                </a>
                
                <!-- Central de Notificações -->
                <a href="javascript:void(0)" onclick="if(!enforceKYC(event)) return false; toggleNotifs(event)" class="btn-action btn-action--notif" title="Notificações">
                    <i class="fas fa-bell"></i>
                    <?php if (isset($n_count) && (int)$n_count > 0): ?>
                        <span class="elite-badge-pulse badge-notif" id="notifBadge"><?php echo (int)$n_count; ?></span>
                    <?php else: ?>
                        <span class="elite-badge-pulse badge-notif" id="notifBadge" style="display:none;"></span>
                    <?php endif; ?>
                </a>

                <!-- Dúvidas (Comunidade) -->
                <a href="<?php echo $base_url; ?>paginas/explorar/doubts.php" onclick="return enforceKYC(event);" class="btn-action btn-action--doubt" title="Dúvidas na Comunidade">
                    <i class="fas fa-question-circle"></i>
                    <?php if (isset($open_doubts) && (int)$open_doubts > 0): ?>
                        <span class="elite-badge-pulse badge-doubt" id="doubtBadge"><?php echo (int)$open_doubts; ?></span>
                    <?php else: ?>
                        <span class="elite-badge-pulse badge-doubt" id="doubtBadge" style="display:none;"></span>
                    <?php endif; ?>
                </a>
            </div>

            <!-- Dropdown Notificações -->
            <div class="dropdown-menu notif-panel glass-effect" id="notifContent">
                <div class="dropdown-header" style="display:flex; justify-content:space-between; align-items:center;">
                    <span class="user-name" style="font-size:0.9rem;">Notificações</span>
                    <a href="javascript:void(0)" onclick="markAllRead()" style="font-size:0.7rem; color:var(--brand-primary); padding:0; background:none;">Marcar como lidas</a>
                </div>
                <div class="dd-divider"></div>
                <div id="notifList" style="max-height: 350px; overflow-y: auto;">
                    <!-- Via AJAX -->
                </div>
            </div>

            <!-- Botão de Perfil (Central Modal Trigger) -->
            <div class="profile-container">
                <button onclick="openPerfilMenuModal()" class="btn-profile" title="A Minha Conta">
                    <img src="<?php echo htmlspecialchars($display_pic_src); ?>" width="38" height="38" loading="eager" decoding="async" alt="Avatar" onerror="this.src='<?php echo $base_url; ?>recursos/images/avatars/student.png'">
                </button>
            </div>
        </div>
</nav>

<style>
/* Sistema de Navegação - Estrutura Principal */
.navbar-header {
    height: 80px;
    background: var(--bg-0) !important;
    border-bottom: 1px solid var(--surface-8);
    display: flex;
    align-items: center;
    justify-content: space-between;
    position: fixed;
    top: 0;
    left: 0;
    right: 0;
    width: 100%;
    padding: 0 7.5%;
    z-index: 9999;
    transition: all 0.4s ease;
}
.navbar-header.nav-scrolled {
    background: var(--glass-bg) !important;
    backdrop-filter: blur(15px) !important;
    height: 70px;
}
.header-main-box { display: flex; align-items: center; gap: 2.5rem; }
.header-logo { display: flex; align-items: center; gap: 0.8rem; text-decoration: none; }
.logo-symbol { width: 58px; height: 44px; background: transparent; border-radius: 10px; padding: 0; overflow: hidden; }
.logo-symbol img { width: 100%; height: 100%; object-fit: cover; object-position: center; border-radius: 10px; }

.search-input-field { 
    position: relative; /* Crítico para conter o dropdown */
    background: var(--surface-5); 
    padding: 0.6rem 1.2rem; 
    border-radius: 12px; 
    display: flex; 
    align-items: center; 
    gap: 0.8rem; 
    width: 180px; 
    transition: 0.3s; 
}
.search-input-field:focus-within { background: var(--surface-10); width: 240px; box-shadow: 0 0 15px rgba(247,148,29,0.15); }
.search-input-field input { background: none; border: none; color: var(--text-primary); font-size: 0.9rem; outline: none; flex: 1; }
.search-input-field kbd { background: var(--surface-10); border-radius: 4px; padding: 2px 6px; font-size: 0.7rem; color: var(--text-secondary); }

.navbar-links { 
    display: flex; 
    gap: 0.5rem; 
    margin-left: 1rem; 
    align-items: center;
}

.nav-link { 
    color: var(--text-muted); 
    text-decoration: none; 
    font-size: 0.9rem; 
    font-weight: 600; 
    padding: 0.5rem 0.8rem; 
    border-radius: 12px;
    transition: all 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
    display: flex;
    align-items: center;
    gap: 0.7rem;
}

.nav-link i { font-size: 1rem; opacity: 0.8; transition: 0.3s; }
.nav-link:hover { 
    color: var(--text-primary); 
    background: var(--surface-5); 
}
.nav-link.active { 
    color: var(--elite-orange); 
    background: var(--surface-8);
    box-shadow: 0 4px 15px rgba(0,0,0,0.2);
}

.nav-link[href*="index.php"].active {
    background: var(--surface-8);
    border: 1px solid var(--surface-10);
}

.nav-dropdown { position: relative; }
.dropdown-panel {
    position: absolute;
    top: 100%;
    left: 0;
    min-width: 240px;
    background: var(--bg-1);
    backdrop-filter: blur(25px);
    border: 1px solid var(--surface-10);
    border-radius: 18px;
    padding: 0.8rem 0;
    box-shadow: 0 25px 50px rgba(0,0,0,0.4);
    display: none;
    z-index: 10000;
    animation: ddFadeIn 0.3s ease-out;
    /* Ponte invisível para cobrir o gap entre trigger e painel */
    margin-top: 0;
    padding-top: 1.2rem;
}
/* Ponte invisível acima do painel para cobrir qualquer gap */
.dropdown-panel::before {
    content: '';
    position: absolute;
    top: -20px;
    left: 0;
    right: 0;
    height: 20px;
    background: transparent;
}

@keyframes ddFadeIn { from { opacity: 0; transform: translateY(8px); } to { opacity: 1; transform: translateY(0); } }

.nav-dropdown:hover .dropdown-panel,
.nav-dropdown.dd-open .dropdown-panel { display: block !important; overflow: visible; }
.dropdown-menu.active { display: block; }

/* Nested Submenus - Design de Segundo Nível */
.nav-sub-dropdown {
    position: relative;
    width: 100%;
}
.nav-sub-dropdown > a {
    display: flex !important;
    justify-content: space-between !important;
    align-items: center !important;
    padding-right: 1.25rem !important;
}
.sub-icon {
    font-size: 0.65rem;
    opacity: 0.4;
    transition: all 0.3s ease;
}
.nav-sub-dropdown:hover .sub-icon { transform: translateX(4px); opacity: 1; color: #f7941d; }

.sub-dropdown-panel {
    display: none;
    position: absolute;
    left: 100%;
    top: -10px;
    min-width: 260px;
    background: var(--bg-1) !important;
    backdrop-filter: blur(35px) !important;
    -webkit-backdrop-filter: blur(35px) !important;
    border-radius: 18px !important;
    border: 1px solid rgba(247, 148, 29, 0.4) !important;
    box-shadow: 20px 20px 60px rgba(0,0,0,0.8) !important;
    padding: 10px 0;
    z-index: 20000 !important;
    /* Ponte invisível à esquerda para cobrir o gap lateral */
    margin-left: 0;
    padding-left: 0;
}
/* Ponte invisível à esquerda do sub-painel */
.sub-dropdown-panel::before {
    content: '';
    position: absolute;
    left: -16px;
    top: 0;
    bottom: 0;
    width: 16px;
    background: transparent;
}
.nav-sub-dropdown:hover .sub-dropdown-panel,
.nav-sub-dropdown.sub-open .sub-dropdown-panel {
    display: block !important;
    animation: subReveal 0.3s cubic-bezier(0.16, 1, 0.3, 1) forwards;
}

@keyframes subReveal {
    from { opacity: 0; transform: translateX(15px) scale(0.96); }
    to { opacity: 1; transform: translateX(0) scale(1); }
}

/* Inverter direcção para menus à direita (Perfil/Notificações) */
.nav-dropdown:last-child .sub-dropdown-panel {
    left: auto;
    right: 100%;
}
#profileDropdown .nav-sub-dropdown .sub-dropdown-panel {
    left: auto;
    right: 105%;
}
.notif-panel { 
    right: 0; 
    top: 75px; 
    width: 320px !important; 
    min-width: 320px !important; 
    background: var(--bg-1) !important; 
    border: 1px solid rgba(255,107,43,0.3) !important;
    box-shadow: 0 15px 40px rgba(0,0,0,0.6) !important;
    padding: 0 !important;
    overflow: hidden;
    border-radius: 18px !important;
}

/* =============================================
   SISTEMA NOTIFICAÇÕES (BELEZA E UX)
   ============================================= */
.notif-item {
    display: flex;
    gap: 12px;
    align-items: flex-start;
    padding: 14px 16px;
    border-bottom: 1px solid rgba(255,255,255,0.05);
    cursor: pointer;
    transition: background 0.2s ease, transform 0.2s ease;
    text-decoration: none;
}
.notif-item:hover {
    background: rgba(255, 107, 53, 0.05) !important;
}
.notif-pfp-container {
    flex-shrink: 0;
    width: 42px;
    height: 42px;
    border-radius: 50%;
    overflow: hidden;
    background: rgba(0,0,0,0.3);
    border: 1px solid rgba(255,255,255,0.1);
    box-shadow: 0 4px 10px rgba(0,0,0,0.3);
    display: flex;
    align-items: center;
    justify-content: center;
}
.notif-title {
    font-family: 'Outfit', sans-serif;
    font-weight: 800;
    font-size: 0.85rem;
    color: var(--text-primary);
    line-height: 1.2;
    margin-bottom: 4px;
}
.notif-body {
    font-size: 0.75rem;
    color: var(--surface-60);
    line-height: 1.4;
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    text-overflow: ellipsis;
}

.profile-panel { 
    right: 0; 
    top: calc(100% + 8px); /* Posiciona logo abaixo do botão (profile-container é a âncora) */
    width: 280px !important; 
    background: var(--bg-1) !important; 
    z-index: 99999 !important;
}

.dropdown-panel a {
    display: flex;
    align-items: center;
    gap: 0.9rem;
    padding: 0.85rem 1.5rem;
    color: var(--text-primary);
    text-decoration: none;
    font-size: 0.92rem;
    font-weight: 600;
    transition: all 0.25s ease;
}

.dropdown-panel a:hover {
    background: rgba(247,148,29,0.1);
    color: var(--text-primary);
    padding-left: 1.8rem;
}

.dropdown-panel a i { color: #f7941d; width: 18px; text-align: center; font-size: 1rem; }
.dd-icon { font-size: 0.75rem !important; transition: 0.3s; opacity: 0.5 !important; }
.highlight-dd { border-top: 1px solid var(--surface-5); margin-top: 5px; }

.header-actions { display: flex; align-items: center; gap: 1rem; }
.action-group { display: flex; align-items: center; gap: 0.4rem; background: var(--surface-3); padding: 4px; border-radius: 14px; border: 1px solid var(--surface-5); }
.btn-action { width: 38px; height: 38px; border-radius: 10px; display: flex; align-items: center; justify-content: center; color: var(--surface-60); font-size: 1rem; transition: 0.3s; position: relative; }
.btn-action:hover { background: var(--surface-8); color: var(--text-primary); }

/* =============================================
   SISTEMA DE BADGES DE CONTAGEM - PREMIUM
   ============================================= */
.elite-badge-pulse {
    position: absolute;
    top: -6px;
    right: -6px;
    min-width: 20px;
    height: 20px;
    padding: 0 5px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    font-size: 0.62rem;
    font-weight: 900;
    font-family: 'Outfit', sans-serif;
    letter-spacing: 0.3px;
    color: #fff;
    border: 2.5px solid var(--bg-0);
    animation: badgePop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275) forwards;
    z-index: 10;
    line-height: 1;
}

/* Notificações — Vermelho vibrante */
.badge-notif {
    background: linear-gradient(135deg, #ff3a2d, #ff6b35);
    box-shadow: 0 0 10px rgba(255, 58, 45, 0.7), 0 0 20px rgba(255, 58, 45, 0.3);
}

/* Mensagens — Azul elétrico */
.badge-msg {
    background: linear-gradient(135deg, #2563eb, #06b6d4);
    box-shadow: 0 0 10px rgba(37, 99, 235, 0.7), 0 0 20px rgba(6, 182, 212, 0.3);
}

/* Dúvidas — Âmbar/Laranja */
.badge-doubt {
    background: linear-gradient(135deg, #f7941d, #fbbf24);
    box-shadow: 0 0 10px rgba(247, 148, 29, 0.7), 0 0 20px rgba(251, 191, 36, 0.3);
    color: #1a0a00;
}

/* Ícone personalizado por tipo de ação */
.btn-action--notif i { color: #ff6b6b; }
.btn-action--msg i { color: #60a5fa; }
.btn-action--doubt i { color: #f7941d; }
.btn-action--notif:hover i { color: #fff; }
.btn-action--msg:hover i { color: #fff; }
.btn-action--doubt:hover i { color: #fff; }

/* Retrocompatibilidade */
.badge-status { 
    position: absolute; 
    top: -6px; 
    right: -6px;
    background: linear-gradient(135deg, #ff3a2d, #ff6b35);
    color: #fff; 
    font-size: 0.62rem; 
    font-weight: 900; 
    min-width: 20px;
    height: 20px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
    border: 2.5px solid #050a15;
    box-shadow: 0 0 10px rgba(255, 58, 45, 0.7);
    animation: badgePop 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
}

@keyframes badgePop {
    from { transform: scale(0) rotate(-15deg); opacity: 0; }
    to { transform: scale(1); }
}

.btn-profile { 
    display: flex; 
    align-items: center; 
    gap: 0.8rem;
    padding-right: 0.5rem;
    height: 44px; 
    border-radius: 14px; 
    overflow: hidden; 
    border: 1px solid var(--surface-8); 
    background: var(--surface-3);
    cursor: pointer; 
    transition: 0.3s; 
}
.btn-profile:hover { border-color: #f7941d; background: rgba(255,255,255,0.06); }
.btn-profile img { width: 38px; height: 38px; object-fit: cover; border-radius: 10px; }
.dd-chevron-tiny { font-size: 0.7rem; color: var(--surface-40); transition: 0.3s; }
.btn-profile:hover .dd-chevron-tiny { color: #fff; }
.profile-container { position: relative; overflow: visible; } /* CRÍTICO: Âncora para o dropdown absoluto */
.profile-container.active .dd-chevron-tiny { transform: rotate(180deg); color: #f7941d; }

.dropdown-menu {
    position: absolute; top: 100%; right: 0; min-width: 240px; background: rgba(10, 17, 34, 0.98);
    backdrop-filter: blur(25px); border: 1px solid var(--surface-10); border-radius: 16px; 
    padding: 1.2rem 0; display: none; box-shadow: 0 20px 50px rgba(0,0,0,0.6); margin-top: 15px;
    animation: dropdownSlide 0.3s ease-out;
}
@keyframes dropdownSlide { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
.dropdown-menu.active { display: block; }
.dropdown-menu a { display: flex; align-items: center; gap: 0.8rem; padding: 0.9rem 1.5rem; color: var(--surface-70); text-decoration: none; font-size: 0.95rem; font-weight: 600; transition: 0.3s; }
.dropdown-menu a:hover { background: var(--surface-5); color: #fff; padding-left: 1.8rem; }
.dropdown-menu a i { color: #f7941d; width: 20px; }

.dropdown-header { padding: 0 1.5rem 1rem; }
.user-name { color: #fff; font-weight: 800; font-size: 1rem; margin: 0; }
.user-role { color: #f7941d; font-size: 0.7rem; font-weight: 700; margin: 2px 0 0; }
.dd-divider { height: 1px; background: var(--surface-5); margin: 0.5rem 0; }
/* Garante que os Alertas (SweetAlert) apareçam sempre à frente de qualquer modal ou dropdown */
.swal2-container {
    z-index: 1000001 !important;
}
</style>
<?php include_once __DIR__ . '/../explorar_mentoria_modals.php'; ?>
