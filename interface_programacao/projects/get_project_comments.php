<?php
// interface_programacao/projects/get_project_comments.php
// Recupera comentários de um projeto e suas respostas
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';

$project_id = isset($_GET['project_id']) ? (int)$_GET['project_id'] : 0;

if ($project_id <= 0) {
    echo json_encode([]);
    exit;
}

$database = new Database();
$db = $database->getConnection();

function ensureProjectCommentsV2Table(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_comments_v2 (
            comment_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            parent_id INT DEFAULT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
}

try {
    ensureProjectCommentsV2Table($db);

    // Buscar comentários originais
    $query = "SELECT c.comment_id, c.parent_id, c.content, c.created_at, u.full_name, u.profile_pic, u.user_id, u.user_type
              FROM project_comments_v2 c
              JOIN users u ON c.user_id = u.user_id
              WHERE c.project_id = :project_id
                AND c.content NOT LIKE '%MENTORIA:%'
                AND c.content NOT LIKE '%CANDIDATURA:%'
              ORDER BY c.created_at ASC"; // ASC para manter ordem natural
    
    $stmt = $db->prepare($query);
    $stmt->execute([':project_id' => $project_id]);
    $all_comments = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $comments_tree = [];
    $replies = [];

    // Organizar em árvore (pais e filhos)
    foreach ($all_comments as $c) {
        $c['created_at_formatted'] = date('d/m/Y H:i', strtotime($c['created_at']));
        $c['replies'] = []; // preparar array de respostas

        if (empty($c['parent_id'])) {
            $comments_tree[$c['comment_id']] = $c;
        } else {
            $replies[] = $c;
        }
    }

    // Associar as respostas aos seus pais
    foreach ($replies as $reply) {
        if (isset($comments_tree[$reply['parent_id']])) {
            $comments_tree[$reply['parent_id']]['replies'][] = $reply;
        }
    }
    
    // Converter de volta para array indexado sequencial (invertido para os mais recentes primeiro)
    $final_array = array_values($comments_tree);
    $final_array = array_reverse($final_array);

    echo json_encode($final_array);

} catch (PDOException $e) {
    echo json_encode([]);
}
?>
