<?php
/**
 * interface_programacao/admin/boost_project.php
 * Toggles the "Featured/Boosted" status for a project.
 */
@session_start();
header('Content-Type: application/json');

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('moderation')) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

if (!isset($_POST['project_id'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid Request']);
    exit();
}

$project_id = (int)$_POST['project_id'];

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid project id']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    // Check current status
    $stmt = $db->prepare("SELECT is_featured FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Project not found']);
        exit();
    }
    
    $new_status = !((bool)$project['is_featured']);

    $updateStmt = $db->prepare("UPDATE projects SET is_featured = ? WHERE project_id = ?");
    $updateStmt->execute([$new_status, $project_id]);
    
    echo json_encode([
        'success' => true, 
        'is_featured' => $new_status,
        'message' => 'Project status updated successfully'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error: ' . $e->getMessage()]);
}
