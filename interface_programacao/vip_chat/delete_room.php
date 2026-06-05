<?php
/**
 * delete_room.php
 * Elimina uma Sala VIP e tudo relacionado (Apenas Admin).
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $data = json_decode(file_get_contents("php://input"), true) ?? $_POST;
    
    $chat_id = (int)($data['chat_id'] ?? 0);
    if (!$chat_id) {
        echo json_encode(['success' => false, 'error' => 'ID inválido.']);
        exit;
    }

    $stmt = $db->prepare("DELETE FROM vip_chats WHERE id = ?");
    $stmt->execute([$chat_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao eliminar sala.']);
}
?>
