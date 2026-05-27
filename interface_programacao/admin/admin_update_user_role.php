<?php
/**
 * admin_update_user_role.php - Updates user type and institution text
 */
header('Content-Type: application/json');
@session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $user_id = $_POST['user_id'] ?? null;
    $user_type = $_POST['user_type'] ?? null;
    $institution = $_POST['institution'] ?? null;

    if (!$user_id || !$user_type) {
        echo json_encode(['success' => false, 'message' => 'Campos obrigatórios em falta']);
        exit;
    }

    $database = new Database();
    $db = $database->getConnection();

    try {
        $stmt = $db->prepare("UPDATE users SET user_type = ?, institution = ?, updated_at = CURRENT_TIMESTAMP WHERE user_id = ?");
        $stmt->execute([$user_type, $institution, $user_id]);

        echo json_encode(['success' => true, 'message' => 'Utilizador atualizado com sucesso']);
    } catch (Exception $e) {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido']);
}
