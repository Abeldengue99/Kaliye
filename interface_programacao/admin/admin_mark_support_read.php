<?php
/**
 * interface_programacao/admin/admin_mark_support_read.php
 * Handles marking support messages as read.
 */
@session_start();
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('support')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['msg_id'])) {
    $database = new Database();
    /** @var PDO $db */
$db = $database->getConnection();
    
    $msg_id = (int)$_POST['msg_id'];
    
    $stmt = $db->prepare("UPDATE support_messages SET is_read = '1' WHERE id = ?");
    $success = $stmt->execute([$msg_id]);
    
    echo json_encode(['success' => $success]);
} else {
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
}

