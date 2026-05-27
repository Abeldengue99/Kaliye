<?php
// servicos/projects/get_project_likes.php
// Recupera a contagem de likes de um projeto E se usuário atual curtiu
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;
$user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : 0;

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'count' => 0, 'is_liked' => false]);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Contar likes
    $count_query = "SELECT COUNT(*) as total FROM project_likes WHERE project_id = :project_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([':project_id' => $project_id]);
    $total_likes = (int)$count_stmt->fetchColumn();
    
    // Verificar se usuário curtiu
    $is_liked = false;
    if ($user_id > 0) {
        $check_query = "SELECT 1 FROM project_likes WHERE project_id = :project_id AND user_id = :user_id LIMIT 1";
        $check_stmt = $db->prepare($check_query);
        $check_stmt->execute([':project_id' => $project_id, ':user_id' => $user_id]);
        if ($check_stmt->fetchColumn()) {
            $is_liked = true;
        }
    }

    // Obter lista de usuários que curtiram
    $users_query = "SELECT u.user_id, u.full_name, u.profile_pic FROM project_likes l JOIN users u ON l.user_id = u.user_id WHERE l.project_id = :p_id ORDER BY l.created_at DESC LIMIT 20";
    $users_stmt = $db->prepare($users_query);
    $users_stmt->execute([':p_id' => $project_id]);
    $likers = $users_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode([
        'success' => true, 
        'count' => $total_likes, 
        'is_liked' => $is_liked,
        'likes' => $likers
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao buscar likes']);
}

