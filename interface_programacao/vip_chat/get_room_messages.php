<?php
/**
 * get_room_messages.php
 * Retorna as mensagens de uma sala VIP.
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
    
    $chat_id = (int)($_GET['chat_id'] ?? 0);
    $user_id = (int)$_SESSION['user_id'];
    $last_id = (int)($_GET['last_id'] ?? 0);

    if (!$chat_id) {
        echo json_encode(['success' => false, 'error' => 'ID de sala inválido.']);
        exit;
    }

    // Verifica se o user pertence à sala
    $stmt_check = $db->prepare("SELECT 1 FROM vip_chat_participants WHERE chat_id = ? AND user_id = ?");
    $stmt_check->execute([$chat_id, $user_id]);
    if (!$stmt_check->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'Acesso negado à sala.']);
        exit;
    }

    // Paginação simples com last_id
    $sql = "
        SELECT m.id, m.message_text, m.sent_at, m.sender_id, u.full_name, u.profile_pic, u.user_type, m.file_path, m.file_name, m.file_type
        FROM vip_chat_messages m
        JOIN users u ON m.sender_id = u.user_id
        WHERE m.chat_id = :chat_id AND m.id > :last_id
        ORDER BY m.id ASC
    ";
    
    $stmt = $db->prepare($sql);
    $stmt->bindValue(':chat_id', $chat_id, PDO::PARAM_INT);
    $stmt->bindValue(':last_id', $last_id, PDO::PARAM_INT);
    $stmt->execute();
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    if ($messages && $last_id == 0) {
        // Se for a primeira vez que carregamos as mensagens (last_id = 0), resetamos o unread_count
        $stmt_reset = $db->prepare("UPDATE vip_chat_participants SET unread_count = 0 WHERE chat_id = ? AND user_id = ?");
        $stmt_reset->execute([$chat_id, $user_id]);
    } else if ($messages) {
        // Se houver novas mensagens a chegar via polling, também resetamos o contador para manter tudo a 0 enquanto a pessoa está com a sala aberta
        $stmt_reset = $db->prepare("UPDATE vip_chat_participants SET unread_count = 0 WHERE chat_id = ? AND user_id = ?");
        $stmt_reset->execute([$chat_id, $user_id]);
    }

    echo json_encode(['success' => true, 'messages' => $messages]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno.']);
}
?>
