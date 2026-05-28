<?php
// interface_programacao/admin/admin_update_admin_access.php
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isSuperAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Apenas SuperAdmins podem gerir acessos administrativos.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$userId = isset($input['user_id']) ? (int)$input['user_id'] : 0;
$action = trim((string)($input['action'] ?? ''));

if ($userId <= 0 || !in_array($action, ['remove_access', 'disable_account'], true)) {
    echo json_encode(['success' => false, 'message' => 'Pedido invalido.']);
    exit;
}

if ($userId === (int)($_SESSION['user_id'] ?? 0)) {
    echo json_encode(['success' => false, 'message' => 'Não pode remover ou desativar o seu próprio acesso.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    ensureUsersActiveColumn($db);

    $stmt = $db->prepare("SELECT user_id, full_name, user_type FROM users WHERE user_id = ? LIMIT 1");
    $stmt->execute([$userId]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado.']);
        exit;
    }

    if (($user['user_type'] ?? '') === 'superadmin') {
        echo json_encode(['success' => false, 'message' => 'Não pode alterar outro SuperAdmin por esta acção.']);
        exit;
    }

    if (($user['user_type'] ?? '') !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Este utilizador ja não tem acesso administrativo.']);
        exit;
    }

    $db->beginTransaction();
    $db->prepare('DELETE FROM admin_permissions WHERE user_id = ?')->execute([$userId]);

    if ($action === 'disable_account') {
        $update = $db->prepare("UPDATE users SET user_type = 'univ_student', is_active = false, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        $update->execute([$userId]);
        $message = 'Conta desativada e permissoes administrativas removidas.';
    } else {
        $update = $db->prepare("UPDATE users SET user_type = 'univ_student', updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        $update->execute([$userId]);
        $message = 'Permissoes administrativas removidas. A conta deixou de fazer parte da equipa.';
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => $message]);
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('admin_update_admin_access error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao atualizar acesso administrativo.']);
}


