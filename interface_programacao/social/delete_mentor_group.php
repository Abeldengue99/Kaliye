<?php
/**
 * delete_mentor_group.php
 * Deleta um grupo de mentoria (apenas o proprietário)
 */
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $user_id = (int)$_SESSION['user_id'];
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    
    if ($group_id <= 0) {
        throw new Exception('Grupo não especificado.');
    }
    
    // Verificar se o usuário é o proprietário
    $owner_check = $db->prepare("SELECT mentor_id FROM mentor_chat_groups WHERE id = ?");
    $owner_check->execute([$group_id]);
    $group = $owner_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$group || $group['mentor_id'] != $user_id) {
        throw new Exception('Sem permissão para excluir este grupo.');
    }
    
    // Deletar grupo (cascata delete cuida dos membros e mensagens)
    $delete_stmt = $db->prepare("DELETE FROM mentor_chat_groups WHERE id = ?");
    $delete_stmt->execute([$group_id]);
    
    echo json_encode([
        'success' => true,
        'message' => 'Grupo excluído com sucesso!'
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
