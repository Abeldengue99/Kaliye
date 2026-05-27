<?php
/**
 * interface_programacao/admin/create_admin.php
 * Creates an administrator from the dedicated admin screen.
 */
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Nao autorizado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit;
}

$full_name = trim($_POST['full_name'] ?? '');
$email = trim($_POST['email'] ?? '');
$password = (string)($_POST['password'] ?? '');

if ($full_name === '' || $email === '' || $password === '') {
    echo json_encode(['success' => false, 'message' => 'Preencha nome, email e senha.']);
    exit;
}

if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Email invalido.']);
    exit;
}

if (strlen($password) < 8) {
    echo json_encode(['success' => false, 'message' => 'A senha deve ter pelo menos 8 caracteres.']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $check = $db->prepare('SELECT user_id FROM users WHERE email = ?');
    $check->execute([$email]);
    if ($check->fetchColumn()) {
        echo json_encode(['success' => false, 'message' => 'Este email ja esta registado.']);
        exit;
    }

    $hash = password_hash($password, PASSWORD_DEFAULT);
    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO users (full_name, email, password, user_type, is_active, is_verified, created_at, updated_at)
        VALUES (?, ?, ?, 'admin', true, true, NOW(), NOW())
        RETURNING user_id
    ");
    $stmt->execute([$full_name, $email, $hash]);
    $new_id = (int)$stmt->fetchColumn();

    $defaultPermissions = ['dashboard', 'users', 'ads', 'moderation', 'support', 'kyc', 'mentor_approval', 'finance_docs', 'finances'];
    $permStmt = $db->prepare('INSERT INTO admin_permissions (user_id, permission_slug, created_at) VALUES (?, ?, NOW())');
    foreach ($defaultPermissions as $permission) {
        $permStmt->execute([$new_id, $permission]);
    }

    $db->commit();

    echo json_encode(['success' => true, 'message' => 'Administrador criado com sucesso.']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('create_admin error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao criar administrador: ' . $e->getMessage()]);
}
