<?php
/**
 * interface_programacao/admin/toggle_evaluation_featured.php
 * Endpoint para destacar ou ocultar avaliações no portal público.
 */
@session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

$evaluation_id = isset($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : 0;

if (!$evaluation_id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    // Inverte o estado atual de is_featured
    $db->exec("UPDATE platform_evaluations SET is_featured = NOT is_featured WHERE id = $evaluation_id");
    
    echo json_encode(['success' => true, 'message' => 'Estado de destaque atualizado!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
