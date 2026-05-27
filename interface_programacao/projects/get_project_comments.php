<?php
// servicos/projects/get_project_comments.php
// Recupera comentários de um projeto
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id <= 0) {
    echo json_encode([]);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Buscar comentários com dados do usuário
    $query = "SELECT c.comment_id, c.content, c.created_at, u.full_name, u.profile_pic, u.user_id
              FROM social_comments c
              JOIN users u ON c.user_id = u.user_id
              WHERE c.project_id = :project_id
              ORDER BY c.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':project_id' => $project_id]);
    $comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Formatar dados se necessário (ex: data relativa)
    foreach ($comments as &$comment) {
        $comment['created_at_formatted'] = date('d/m/Y H:i', strtotime($comment['created_at']));
    }
    
    echo json_encode($comments);

} catch (PDOException $e) {
    echo json_encode([]);
}

