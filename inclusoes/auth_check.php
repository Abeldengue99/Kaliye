<?php
// includes/auth_check.php
require_once __DIR__ . '/security_headers.php';
require_once __DIR__ . '/SystemSettings.php';

if (session_status() === PHP_SESSION_NONE) {
    @session_start();
}

function requestExpectsJson(): bool {
    $accept = strtolower($_SERVER['HTTP_ACCEPT'] ?? '');
    $requestedWith = strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '');
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');

    return strpos($accept, 'application/json') !== false
        || $requestedWith === 'xmlhttprequest'
        || strpos($script, '/interface_programacao/') !== false;
}

function configuredSessionIdleTimeoutSeconds(): int {
    static $timeout = null;
    if ($timeout !== null) {
        return $timeout;
    }

    $minutes = 30;
    try {
        $db = (new Database())->getConnection();
        if ($db) {
            $minutes = (int)(getSystemSetting($db, 'session_idle_timeout_minutes', '30') ?? '30');
        }
    } catch (Throwable $e) {
        error_log('Session timeout setting lookup failed: ' . $e->getMessage());
    }

    $timeout = max(5, min(1440, $minutes)) * 60;
    return $timeout;
}

function endSessionForInactivity(): void {
    $_SESSION = [];

    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $params['path'], $params['domain'], $params['secure'], $params['httponly']);
    }

    session_destroy();

    if (requestExpectsJson()) {
        http_response_code(401);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'session_expired' => true,
            'message' => 'A sua sessão expirou por inatividade. Inicie sessão novamente.'
        ]);
        exit();
    }

    $prefix = isset($GLOBALS['base_url']) ? $GLOBALS['base_url'] : '';
    header('Location: ' . $prefix . 'autenticacao/entrar.php?msg=session_expired');
    exit();
}

function enforceIdleSessionTimeout(): void {
    if (!isset($_SESSION['user_id'])) {
        return;
    }

    $now = time();
    $lastActivity = (int)($_SESSION['last_activity_at'] ?? $now);
    $timeout = configuredSessionIdleTimeoutSeconds();

    if (($now - $lastActivity) > $timeout) {
        endSessionForInactivity();
    }

    $_SESSION['last_activity_at'] = $now;
}

enforceIdleSessionTimeout();

function currentRequestPath(): string {
    return str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
}

function currentRequestIsProfileCompletionAllowed(): bool {
    $path = currentRequestPath();
    $allowed = [
        '/paginas/conta/completar_perfil.php',
        '/interface_programacao/auth/complete_google_profile.php',
        '/autenticacao/sair.php',
    ];

    foreach ($allowed as $suffix) {
        if (substr($path, -strlen($suffix)) === $suffix) {
            return true;
        }
    }

    return false;
}

function enforceGoogleProfileCompletion(): void {
    if (empty($_SESSION['user_id']) || empty($_SESSION['google_profile_incomplete'])) {
        return;
    }

    if (currentRequestIsProfileCompletionAllowed()) {
        return;
    }

    if (requestExpectsJson()) {
        http_response_code(403);
        header('Content-Type: application/json');
        echo json_encode([
            'success' => false,
            'profile_incomplete' => true,
            'message' => 'Complete o perfil antes de continuar.'
        ]);
        exit();
    }

    $prefix = isset($GLOBALS['base_url']) ? $GLOBALS['base_url'] : '';
    header('Location: ' . $prefix . 'paginas/conta/completar_perfil.php');
    exit();
}

enforceGoogleProfileCompletion();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function normalizeStoredProfilePic($profile_pic) {
    $profile_pic = trim((string)$profile_pic);
    if ($profile_pic === '' || $profile_pic === 'default_profile.png') {
        return '';
    }

    if (preg_match('#^(https?://|carregamentos/|recursos/)#', $profile_pic)) {
        return $profile_pic;
    }

    return 'carregamentos/profiles/' . $profile_pic;
}

function getUserAvatarUrl($user_type, $mentorship_status = 'unsubmitted', $profile_pic = '') {
    $stored_pic = normalizeStoredProfilePic($profile_pic);
    if ($stored_pic !== '') {
        return $stored_pic;
    }

    $is_student = in_array($user_type, ['univ_student', 'high_student', 'sec_student', 'student', 'entrepreneur']);
    $is_mentor  = ($user_type === 'mentor' || $mentorship_status === 'approved');

    if ($is_student && $is_mentor) {
        return 'recursos/images/avatars/student_mentor.png';
    } elseif ($is_mentor) {
        return 'recursos/images/avatars/mentor.png';
    } elseif ($user_type === 'investor') {
        return 'recursos/images/avatars/investor.png';
    } elseif (in_array($user_type, ['admin', 'superadmin'])) {
        return 'recursos/images/avatars/admin.png';
    } else {
        return 'recursos/images/avatars/student.png';
    }
}

function requireLogin() {
    if (!isLoggedIn()) {
        $prefix = isset($GLOBALS['base_url']) ? $GLOBALS['base_url'] : '';
        header("Location: " . $prefix . "autenticacao/entrar.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin', 'superadmin'], true);
}

function normalizeDbBool($value): bool {
    if (is_bool($value)) {
        return $value;
    }

    if (is_string($value)) {
        return in_array(strtolower($value), ['1', 'true', 't', 'yes', 'y', 'on'], true);
    }

    return (bool)$value;
}

function hasVerifiedEmail(?array $user = null): bool {
    if ($user !== null && array_key_exists('is_verified', $user)) {
        return normalizeDbBool($user['is_verified']);
    }

    return normalizeDbBool($_SESSION['email_verified'] ?? $_SESSION['is_email_verified'] ?? false);
}

function getIdentityVerificationStatus(?array $user = null): string {
    $status = $user['verification_status'] ?? ($_SESSION['verification_status'] ?? 'unsubmitted');
    $status = strtolower(trim((string)$status));

    return $status !== '' ? $status : 'unsubmitted';
}

function hasVerifiedIdentity(?array $user = null): bool {
    return getIdentityVerificationStatus($user) === 'verified';
}

function getMentorshipStatus(?array $user = null): string {
    $status = $user['mentorship_status'] ?? ($_SESSION['mentorship_status'] ?? $_SESSION['mentor_status'] ?? 'unsubmitted');
    $status = strtolower(trim((string)$status));

    return $status !== '' ? $status : 'unsubmitted';
}

function canActAsMentor(?array $user = null): bool {
    $type = $user['user_type'] ?? ($_SESSION['user_type'] ?? '');

    return in_array($type, ['admin', 'superadmin'], true)
        || $type === 'mentor'
        || getMentorshipStatus($user) === 'approved';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: ../index.php");
        exit();
    }
}

function requireAdminPermissionJson(string $permission) {
    if (!isAdmin() || !hasPermission($permission)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Acesso negado. Permissão insuficiente.']);
        exit();
    }
}

function isMentor() {
    // A user is only a functional mentor if their status is explicitly 'approved'
    return canActAsMentor();
}

function isInvestor() {
    return isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'investor';
}

function isPrivileged() {
    return isAdmin() || isInvestor() || isMentor();
}

function hasPermission($slug) {
    if (!isAdmin()) return false;
    
    // Super Admin (IDs 1 and 15) have all permissions
    $super_admins = [1, 15];
    if (isset($_SESSION['user_id']) && in_array($_SESSION['user_id'], $super_admins)) return true;
    
    // Cache permissions in session to avoid DB overload
    if (!isset($_SESSION['admin_permissions'])) {
        require_once __DIR__ . '/../configuracoes/base_dados.php';
        $db = (new Database())->getConnection();
        if (!$db) return false;
        $stmt = $db->prepare("SELECT permission_slug FROM admin_permissions WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $_SESSION['admin_permissions'] = $stmt->fetchAll(PDO::FETCH_COLUMN);
    }
    
    return in_array($slug, $_SESSION['admin_permissions']);
}

// CSRF PROTECTION
function generateCSRFToken() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCSRFToken($token) {
    if (!isset($_SESSION['csrf_token']) || empty($token)) return false;
    return hash_equals($_SESSION['csrf_token'], $token);
}

function getRequestCSRFToken() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $headerToken = '';
    foreach ($headers as $key => $value) {
        if (strtolower($key) === 'x-csrf-token') {
            $headerToken = $value;
            break;
        }
    }

    if ($headerToken !== '') {
        return $headerToken;
    }

    return $_POST['csrf_token'] ?? $_GET['csrf_token'] ?? '';
}

function requireValidCSRFTokenJson() {
    if ($_SERVER['REQUEST_METHOD'] === 'GET') {
        return;
    }

    if (!verifyCSRFToken(getRequestCSRFToken())) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Pedido bloqueado por segurança. Atualize a pagina e tente novamente.']);
        exit();
    }
}

function getCSRFHiddenInput() {
    return '<input type="hidden" name="csrf_token" value="' . generateCSRFToken() . '">';
}

/**
 * Returns the first page an admin has permission to view.
 */
function getFirstAllowedAdminPage() {
    if (!isAdmin()) return '../autenticacao/entrar.php';
    $super_admins = [1, 15];
    if (in_array($_SESSION['user_id'], $super_admins)) return 'index.php';
    if (hasPermission('dashboard')) return 'index.php';
    
    $map = [
        'users' => 'manage_users.php',
        'ads' => 'manage_ads.php',
        'moderation' => 'moderation.php',
        'support' => 'support.php',
        'kyc' => 'kyc_requests.php',
        'mentor_approval' => 'mentor_applications.php',
        'mentor_assignment' => 'assign_mentors.php',
        'ideia_quality' => 'project_quality.php',
        'finance_docs' => 'finance_dashboard.php',
        'finances' => 'finances.php',
        'legal' => 'legal_management.php',
        'chat_monitor' => 'chat_monitor.php',
        'mentorship_quality' => 'mentorship_reviews.php',
        'audit' => 'logs.php'
    ];

    foreach ($map as $perm => $page) {
        if (hasPermission($perm)) return $page;
    }

    return '../index.php'; // No permissions found at all!
}
