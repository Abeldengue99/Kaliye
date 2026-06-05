<?php
/**
 * get_all_users.php
 * Retorna utilizadores filtrados por tipo para o modal do Admin.
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
    
    $type = $_GET['type'] ?? 'student'; // 'student', 'mentor', 'investor'
    
    $stmt = $db->prepare("SELECT user_id, full_name, user_type, profile_pic FROM users WHERE user_type = ? ORDER BY full_name ASC");
    $stmt->execute([$type]);
    $users = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'users' => $users]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno ao listar utilizadores.']);
}
?>
