<?php
// servicos/projects/get_project.php
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing ID']);
    exit();
}

$project_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Check ownership
    // Note: Assuming only owner can edit. If admins can edit, add check here.
    $query = "SELECT * FROM projects WHERE project_id = :id";
    $stmt = $db->prepare($query);
    $stmt->execute([':id' => $project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        http_response_code(404);
        echo json_encode(['error' => 'Project not found']);
        exit();
    }

    if ($project['owner_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['error' => 'Forbidden']);
        exit();
    }

    // Get Tags (Skills)
    $tags_query = "SELECT s.name 
                   FROM skills s 
                   JOIN project_tags pt ON s.skill_id = pt.skill_id 
                   WHERE pt.project_id = :id";
    $t_stmt = $db->prepare($tags_query);
    $t_stmt->execute([':id' => $project_id]);
    $tags = $t_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    $project['tags'] = implode(', ', $tags);

    echo json_encode(['success' => true, 'project' => $project]);


} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error: ' . $e->getMessage()]);
}
?>

