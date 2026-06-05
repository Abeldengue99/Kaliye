<?php
/**
 * get_room_participants.php
 * Retorna a lista de membros atuais de uma sala (Apenas Admin).
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
    $chat_id = (int)($_GET['chat_id'] ?? 0);
    
    if (!$chat_id) {
        echo json_encode(['success' => false, 'error' => 'ID inválido.']);
        exit;
    }

    $stmt = $db->prepare("
        SELECT u.user_id, u.full_name, u.user_type, p.role_added_as 
        FROM vip_chat_participants p
        JOIN users u ON u.user_id = p.user_id
        WHERE p.chat_id = ?
        ORDER BY u.full_name ASC
    ");
    $stmt->execute([$chat_id]);
    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'members' => $members]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno.']);
}
?>
