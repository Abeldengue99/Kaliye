<?php
/**
 * get_user_rooms.php
 * Retorna as salas VIP onde o utilizador atual é participante.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $user_id = (int)$_SESSION['user_id'];
    
    // Busca salas onde o utilizador é participante
    $stmt = $db->prepare("
        SELECT c.id, c.title, c.description, c.status,
               (SELECT COUNT(*) FROM vip_chat_participants WHERE chat_id = c.id) as total_participants
        FROM vip_chats c
        INNER JOIN vip_chat_participants p ON p.chat_id = c.id
        WHERE p.user_id = ?
        ORDER BY c.created_at DESC
    ");
    $stmt->execute([$user_id]);
    $rooms = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'rooms' => $rooms]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno.']);
}
?>
