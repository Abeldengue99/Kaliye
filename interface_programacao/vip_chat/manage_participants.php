<?php
/**
 * manage_participants.php
 * Adiciona ou remove utilizadores de uma sala VIP.
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
    $user_id = (int)($data['user_id'] ?? 0);
    $action = $data['action'] ?? ''; // 'add' ou 'remove'

    if (!$chat_id || !$user_id) {
        echo json_encode(['success' => false, 'error' => 'Dados inválidos.']);
        exit;
    }

    if ($action === 'add') {
        // Verificar o role do user
        $stmt_role = $db->prepare("SELECT user_type, full_name FROM users WHERE user_id = ?");
        $stmt_role->execute([$user_id]);
        $user_info = $stmt_role->fetch(PDO::FETCH_ASSOC);
        $role = $user_info['user_type'] ?? 'student';
        $full_name = $user_info['full_name'] ?? 'Membro';

        $stmt = $db->prepare("INSERT INTO vip_chat_participants (chat_id, user_id, role_added_as) VALUES (?, ?, ?) ON CONFLICT DO NOTHING");
        $stmt->execute([$chat_id, $user_id, $role]);
        
        // Obter titulo da sala para a notificação
        $stmt_room = $db->prepare("SELECT title FROM vip_chats WHERE id = ?");
        $stmt_room->execute([$chat_id]);
        $room_title = $stmt_room->fetchColumn() ?: 'Sala VIP';

        // Enviar Notificação
        $title = "Acesso VIP Concedido";
        $content = "Foste adicionado à sala: " . $room_title . ". Acede ao Chat para veres as mensagens.";
        $link = "paginas/comunicacao/salas_vip.php?room_id=" . $chat_id;
        $stmt_notif = $db->prepare("INSERT INTO notifications (user_id, type, title, content, link) VALUES (?, 'vip_chat_invite', ?, ?, ?)");
        $stmt_notif->execute([$user_id, $title, $content, $link]);

        // Enviar Mensagem do Sistema na própria sala VIP (Opcional, em nome do admin atual)
        $admin_id = $_SESSION['user_id'];
        $sys_msg = "Aviso do Sistema: " . $full_name . " juntou-se à sala.";
        $stmt_sys = $db->prepare("INSERT INTO vip_chat_messages (chat_id, sender_id, message_text) VALUES (?, ?, ?)");
        $stmt_sys->execute([$chat_id, $admin_id, $sys_msg]);
        
        // Notificar badge incrementando unread_count para os outros participantes
        $stmt_unread = $db->prepare("UPDATE vip_chat_participants SET unread_count = unread_count + 1 WHERE chat_id = ? AND user_id != ?");
        $stmt_unread->execute([$chat_id, $admin_id]);

        echo json_encode(['success' => true, 'message' => 'Utilizador adicionado.']);
    } elseif ($action === 'remove') {
        $stmt = $db->prepare("DELETE FROM vip_chat_participants WHERE chat_id = ? AND user_id = ?");
        $stmt->execute([$chat_id, $user_id]);
        echo json_encode(['success' => true, 'message' => 'Utilizador removido.']);
    } else {
        echo json_encode(['success' => false, 'error' => 'Ação desconhecida.']);
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno ao gerir participantes.']);
}
?>
