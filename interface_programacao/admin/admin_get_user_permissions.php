<?php
// interface_programacao/admin/admin_get_user_permissions.php
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$user_id = isset($_GET['user_id']) ? (int)$_GET['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalido']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare('SELECT permission_slug FROM admin_permissions WHERE user_id = ? ORDER BY permission_slug');
    $stmt->execute([$user_id]);
    $permissions = $stmt->fetchAll(PDO::FETCH_COLUMN);

    echo json_encode(['success' => true, 'permissions' => $permissions]);
} catch (Exception $e) {
    error_log('admin_get_user_permissions error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao carregar permissoes']);
}
