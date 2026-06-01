<?php
/**
 * add_member_to_group.php
 * Adiciona mentorandos (ou outros mentores) à sala VIP
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $mentor_id = (int)$_SESSION['user_id'];
    
    // Validar dados recebidos
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $identifier = isset($_POST['identifier']) ? trim($_POST['identifier']) : '';
    $user_id_param = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
    
    // Aceitar tanto user_id direto quanto identifier
    if ($group_id <= 0) {
        throw new Exception('Dados inválidos.');
    }
    
    if ($user_id_param <= 0 && empty($identifier)) {
        throw new Exception('ID de utilizador ou identificador não fornecido.');
    }
    
    // Verificar se o mentor é dono do grupo
    $group_stmt = $db->prepare("SELECT mentor_id FROM mentor_chat_groups WHERE id = ? LIMIT 1");
    $group_stmt->execute([$group_id]);
    $group = $group_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group || $group['mentor_id'] != $mentor_id) {
        throw new Exception('Sem permissão para modificar este grupo.');
    }
    
    // Procurar utilizador
    $user = null;
    if ($user_id_param > 0) {
        // Se foi fornecido user_id direto
        $user_stmt = $db->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ? LIMIT 1");
        $user_stmt->execute([$user_id_param]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    } else {
        // Se foi fornecido identifier (ID ou email)
        $identifier = is_numeric($identifier) ? (int)$identifier : $identifier;
        $user_stmt = $db->prepare("SELECT user_id, full_name, email FROM users WHERE user_id = ? OR email = ? LIMIT 1");
        $user_stmt->execute([$identifier, $identifier]);
        $user = $user_stmt->fetch(PDO::FETCH_ASSOC);
    }
    
    if (!$user) {
        throw new Exception('Utilizador não encontrado.');
    }
    
    $user_id = (int)$user['user_id'];
    
    // Verificar se já é membro
    $check_stmt = $db->prepare("SELECT id FROM mentor_group_members WHERE group_id = ? AND user_id = ? LIMIT 1");
    $check_stmt->execute([$group_id, $user_id]);
    if ($check_stmt->fetch()) {
        throw new Exception('Utilizador já é membro deste grupo.');
    }
    
    // Adicionar membro
    $insert_stmt = $db->prepare("INSERT INTO mentor_group_members (group_id, user_id, role) VALUES (?, ?, 'member')");
    $insert_stmt->execute([$group_id, $user_id]);
    
    // Notificar o novo membro
    $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) 
                                VALUES (?, ?, 'Nova Sala VIP', 'Foi adicionado a uma sala de mentoria VIP', 'group_invite', ?)");
    $notif_stmt->execute([$user_id, $mentor_id, "paginas/social/messages.php?group={$group_id}"]);
    
    echo json_encode([
        'success' => true,
        'message' => htmlspecialchars($user['full_name']) . ' foi adicionado com sucesso à sala.',
        'member_id' => $user_id,
        'member_name' => htmlspecialchars($user['full_name'])
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
