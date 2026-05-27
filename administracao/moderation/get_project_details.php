<?php
/**
 * get_project_details.php - Returns all project data for moderation modal.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isAdmin() || !hasPermission('moderation')) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit();
}

$id = intval($_GET['id'] ?? 0);
if (!$id) {
    echo json_encode(['success' => false, 'error' => 'ID de projeto inválido.']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    $stmt = $db->prepare("
        SELECT p.*, u.full_name, u.profile_pic, u.user_type
        FROM projects p
        JOIN users u ON p.owner_id = u.user_id
        WHERE p.project_id = ?
    ");
    $stmt->execute([$id]);
    $project = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'error' => 'Projeto não encontrado.']);
        exit();
    }

    // Fetch tags - Join with skills table as per schema
    try {
        $tags_stmt = $db->prepare("
            SELECT s.name 
            FROM skills s 
            JOIN project_tags pt ON s.skill_id = pt.skill_id 
            WHERE pt.project_id = ?
        ");
        $tags_stmt->execute([$id]);
        $project['tags'] = $tags_stmt->fetchAll(PDO::FETCH_COLUMN);
    } catch (Exception $tagError) {
        // Fallback or empty if table/column differs
        $project['tags'] = [];
    }

    echo json_encode(['success' => true, 'project' => $project]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro ao procurar projecto: ' . $e->getMessage()]);
}
?>

