<?php
/**
 * create_room.php
 * Criação de uma nova Sala VIP (Apenas Administrador)
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
    
    $title = trim($data['title'] ?? '');
    $description = trim($data['description'] ?? '');
    
    if (empty($title)) {
        echo json_encode(['success' => false, 'error' => 'O título da sala é obrigatório.']);
        exit;
    }
    
    $admin_id = (int)$_SESSION['user_id'];
    
    $returning = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql' ? ' RETURNING id' : '';
    $stmt = $db->prepare("INSERT INTO vip_chats (admin_creator_id, title, description) VALUES (?, ?, ?)" . $returning);
    $stmt->execute([$admin_id, $title, $description]);
    $chat_id = $returning ? $stmt->fetchColumn() : $db->lastInsertId();

    // Adiciona o próprio administrador automaticamente à sala
    $stmt_participant = $db->prepare("INSERT INTO vip_chat_participants (chat_id, user_id, role_added_as) VALUES (?, ?, 'admin')");
    $stmt_participant->execute([$chat_id, $admin_id]);

    echo json_encode(['success' => true, 'chat_id' => $chat_id, 'message' => 'Sala VIP criada com sucesso.']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno ao criar sala.']);
}
?>
