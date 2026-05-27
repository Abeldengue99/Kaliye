<?php
// interface_programacao/admin/admin_save_user_permissions.php
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Nao autorizado']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: [];
$user_id = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$permissions = $input['permissions'] ?? [];

$allowed = [
    'dashboard', 'users', 'ads', 'moderation', 'support', 'kyc',
    'mentor_approval', 'mentor_assignment', 'finance_docs', 'finances',
    'legal', 'settings', 'chat_monitor', 'mentorship_quality', 'audit'
];

$permissions = array_values(array_unique(array_filter(array_map('trim', (array)$permissions), function ($slug) use ($allowed) {
    return in_array($slug, $allowed, true);
})));

if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID invalido']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $db->beginTransaction();

    $db->prepare('DELETE FROM admin_permissions WHERE user_id = ?')->execute([$user_id]);

    if (!empty($permissions)) {
        $insert = $db->prepare('INSERT INTO admin_permissions (user_id, permission_slug, created_at) VALUES (?, ?, NOW())');
        foreach ($permissions as $permission) {
            $insert->execute([$user_id, $permission]);
        }
    }

    $db->commit();

    if ((int)($_SESSION['user_id'] ?? 0) === $user_id) {
        unset($_SESSION['admin_permissions']);
    }

    echo json_encode(['success' => true, 'message' => 'Permissoes guardadas com sucesso']);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('admin_save_user_permissions error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar permissoes: ' . $e->getMessage()]);
}
