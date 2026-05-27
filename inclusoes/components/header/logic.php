<?php
// includes/components/header/logic.php

if (!$db) {
    die("Database connection missing.");
}

// Global settings
try {
    $settings_stmt = $db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ('site_name')");
    $app_settings = $settings_stmt ? $settings_stmt->fetchAll(PDO::FETCH_KEY_PAIR) : [];
} catch (Exception $e) {
    $app_settings = [];
}
$site_name = 'KALIYE';
$base_url = isset($base_url) ? $base_url : './';

// Public pages (no login required)
$public_pages = [
    'entrar.php', 'registar.php', 'termos.php', 'privacidade.php', 
    'legal.php', 'landing.php', 'forgot_password.php', 
    'reset_password.php', 'verify_2fa_entrar.php'
];

if (!isset($_SESSION['user_id']) && !in_array(basename($_SERVER['PHP_SELF']), $public_pages)) {
    header("Location: " . $base_url . "paginas/guest/landing.php");
    exit();
}

$header_user_id = $_SESSION['user_id'] ?? null;
$n_count = 0; 

if ($header_user_id) {
    // Refresh user data in session
    $u_stmt = $db->prepare("SELECT * FROM users WHERE user_id = ?");
    $u_stmt->execute([$header_user_id]);
    $user_data = $u_stmt->fetch(PDO::FETCH_ASSOC) ?: [];
    $header_user_pic = getUserAvatarUrl(
        $user_data['user_type'] ?? 'student',
        $user_data['mentorship_status'] ?? 'unsubmitted',
        $user_data['profile_pic'] ?? ''
    );

    // Os contadores do header sao atualizados pelo polling JS depois do load.
    // Evitamos 3 COUNTs bloqueantes no primeiro render da pagina.
    $m_count = (int)($_SESSION['header_counts']['messages'] ?? 0);
    $n_count = (int)($_SESSION['header_counts']['notifications'] ?? 0);
    $open_doubts = (int)($_SESSION['header_counts']['doubts'] ?? 0);
    
    // Opcional: n_count no navbar.php costuma representar notificações. 
    // Se quiser o total, use $total_notifs = $m_count + $n_count;

    // Verification & Identity Status Check
    $status_stmt = $db->prepare("SELECT is_verified, verification_status, mentorship_status FROM users WHERE user_id = ?");
    $status_stmt->execute([$header_user_id]);
    $u_status = $status_stmt->fetch(PDO::FETCH_ASSOC);
    
    $kyc_status = $u_status['verification_status'] ?? 'unsubmitted';
    $mentor_status = $u_status['mentorship_status'] ?? 'unsubmitted';
    
    $_SESSION['verification_status'] = $kyc_status;
    $_SESSION['email_verified'] = normalizeDbBool($u_status['is_verified'] ?? false);
    $_SESSION['is_email_verified'] = $_SESSION['email_verified'];
    $_SESSION['is_verified'] = hasVerifiedIdentity($u_status);
    $_SESSION['mentor_status'] = $mentor_status;
    $_SESSION['mentorship_status'] = $mentor_status; // Garante retrocompatibilidade com funções isMentor() em auth_check.php
    
    /*
     * --------------------------------------------------------------------------
     * FIREWALL DE IDENTIDADE (KYC & GATING)
     * --------------------------------------------------------------------------
     * Regra de Negócio: Utilizadores com email verificado podem navegar, mas ações 
     * financeiras e de autoridade exigem Verificação Documental Total (KYC).
     */

    $is_email_verified = hasVerifiedEmail($user_data);
    $is_verified = hasVerifiedIdentity($user_data);
    $kyc_status  = $user_data['verification_status'] ?? 'unsubmitted'; // approved, pending, rejected, unsubmitted

    // Páginas que exigem obrigatoriamente Verificação Documental Total
    $kyc_required_pages = [
        'wallet.php',
        'finance_dashboard.php',
        'my_commissions.php',
        'withdraw.php',
        'mentor_management.php',
        'admin_panel.php',
        'investor_dashboard.php',
        'messages.php',
        'analytics.php',
        'meeting.php',
        'explore_students.php',
        'explore_mentors.php',
        'my_projects.php',
        'project_analytics.php',
        'doubts.php'
    ];

    $current_page = basename($_SERVER['PHP_SELF']);
    $trigger_kyc_modal = false;

    // Bloqueio Preventivo: Se tentar aceder a uma página sensível sem KYC aprovado
    $is_admin = in_array($user_data['user_type'] ?? '', ['admin', 'superadmin'], true);

    if (in_array($current_page, $kyc_required_pages, true) && !hasVerifiedIdentity($user_data)) {
        // Se for admin, ignoramos a restrição para testes e gestão
        if (!$is_admin) {
            header("Location: " . $base_url . "index.php?kyc_required=1");
            exit();
        }
    }

    // Gatilho Manual via URL (ex: vindo de redirecionamentos de segurança ou links de perfil)
    if (isset($_GET['verify_required']) || isset($_GET['kyc_required'])) {
        $trigger_kyc_modal = true;
    }

    // Variável global para ser usada em componentes (Header, Sidebar, etc)
    $has_full_access = hasVerifiedIdentity($user_data) || $is_admin;

    // Force Email Verification Gate
    // NOTA: PostgreSQL retorna booleanos como string 't'/'f' via PDO — nunca o booleano PHP true.
    // Usamos filter_var para normalizar corretamente qualquer representação (true, 't', '1', 1).
    $is_email_page = (basename($_SERVER['PHP_SELF']) === 'verificar_email.php');
    $is_logout = (basename($_SERVER['PHP_SELF']) === 'sair.php');
    
    if (!$is_email_verified && !$is_email_page && !$is_logout && !$is_admin) {
        header("Location: " . $base_url . "autenticacao/verificar_email.php?email=" . urlencode($_SESSION['pending_email_verification']['email'] ?? $_SESSION['user_email'] ?? ''));
        exit();
    }

    $mentor_restricted_pages = ['my_expertise.php', 'explore_students.php'];
    if (in_array($current_page, $mentor_restricted_pages, true) && !canActAsMentor($user_data) && !$is_admin) {
        $trigger_mentor_modal = true;
    }
    
    // Definimos um array contendo os nomes das páginas exclusivas para mentores aprovados (áreas financeiras e expertise)
    $strict_mentor_pages = ['my_commissions.php', 'my_expertise.php'];
    
    // Verificamos se a página atual está restrita, se o usuário não é um mentor aprovado, e se não possui privilégios de administrador
    if (in_array($current_page, $strict_mentor_pages, true) && !canActAsMentor($user_data) && !$is_admin) {
        
        // Redirecionamos os usuários não autorizados para o seus perfis e passamos o parâmetro para despoletar a abertura do modal de candidatura a mentor
        header("Location: " . $base_url . "index.php?mentor_required=1");
        
        // Finalizamos imediatamente a execução subsequente de todo código PHP para garantir total segurança de não envio de respostas HTTP indevidas
        exit();
    }
}
?>
