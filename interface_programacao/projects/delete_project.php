<?php
// servicos/projects/delete_project.php
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_GET['id'])) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'Missing ID']);
    exit();
}

$project_id = $_GET['id'];
$user_id = $_SESSION['user_id'];

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Check ownership
    $stmt = $db->prepare("SELECT owner_id FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        http_response_code(404);
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit();
    }

    if ($project['owner_id'] != $user_id) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Forbidden']);
        exit();
    }

    $db->beginTransaction();

    // Delete related records (manual cascade to be safe)
    $db->prepare("DELETE FROM project_tags WHERE project_id = ?")->execute([$project_id]);
    $db->prepare("DELETE FROM project_media WHERE project_id = ?")->execute([$project_id]);
    $db->prepare("DELETE FROM project_likes WHERE project_id = ?")->execute([$project_id]);

    // Check if comments exist before deleting (optional, but good practice)
    $has_comments_table = false;
    try {
        $db->query("SELECT 1 FROM project_comments LIMIT 1");
        $has_comments_table = true;
    } catch (Exception $e) { $has_comments_table = false; }
    
    if ($has_comments_table) {
        $stmt_comments = $db->prepare("DELETE FROM project_comments WHERE project_id = ?");
        $stmt_comments->execute([$project_id]);
    }

    try {
        $db->prepare("DELETE FROM project_progress WHERE project_id = ?")->execute([$project_id]);
    } catch (Exception $e) {}

    // Delete the project
    $del = $db->prepare("DELETE FROM projects WHERE project_id = ?");
    $del->execute([$project_id]);

    $db->commit();
    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}
?>

