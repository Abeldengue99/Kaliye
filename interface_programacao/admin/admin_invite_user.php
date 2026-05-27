<?php
// interface_programacao/admin/admin_invite_user.php
// Cria um novo administrador com credenciais e permissões
@session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true);
$full_name   = trim($input['full_name'] ?? '');
$email       = trim($input['email'] ?? '');
$permissions = $input['permissions'] ?? [];

if (!$full_name || !$email) {
    echo json_encode(['success' => false, 'message' => 'Nome e email são obrigatórios']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email inválido']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verificar se o email já existe
    $check = $db->prepare("SELECT user_id FROM users WHERE email = :email");
    $check->execute([':email' => $email]);
    if ($check->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Este email já está registado']);
        exit;
    }

    // Gerar senha temporária
    $password = substr(str_shuffle('ABCDEFGHJKMNPQRSTUVWXYZabcdefghjkmnpqrstuvwxyz23456789!@#'), 0, 10);
    $hashed = password_hash($password, PASSWORD_BCRYPT);

    $db->beginTransaction();

    // Criar o utilizador admin
    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, password, user_type, is_active, created_at)
        VALUES (:name, :email, :password, 'admin', true, NOW())
        RETURNING user_id
    ");
    $stmt->execute([
        ':name'     => $full_name,
        ':email'    => $email,
        ':password' => $hashed,
    ]);
    $row     = $stmt->fetch(PDO::FETCH_ASSOC);
    $new_id  = $row['user_id'] ?? null;

    if (!$new_id) {
        throw new Exception('Falha ao criar utilizador');
    }

    // Guardar permissoes no mesmo formato usado por hasPermission().
    if (!empty($permissions)) {
        $allowed = [
            'dashboard', 'users', 'ads', 'moderation', 'support', 'kyc',
            'mentor_approval', 'mentor_assignment', 'finance_docs', 'finances',
            'legal', 'settings', 'chat_monitor', 'mentorship_quality', 'audit'
        ];
        $perm_stmt = $db->prepare("INSERT INTO admin_permissions (user_id, permission_slug, created_at) VALUES (?, ?, NOW())");
        foreach (array_unique(array_map('trim', $permissions)) as $permission) {
            if (in_array($permission, $allowed, true)) {
                $perm_stmt->execute([$new_id, $permission]);
            }
        }
    }
    $db->commit();

    echo json_encode([
        'success' => true,
        'credentials' => [
            'email'    => $email,
            'password' => $password
        ]
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    error_log("admin_invite_user error: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao criar administrador: ' . $e->getMessage()]);
}
