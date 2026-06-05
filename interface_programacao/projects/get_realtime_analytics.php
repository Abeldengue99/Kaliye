<?php
/**
 * API: Obter Analytics em Tempo Real para Projetos
 * Usado via AJAX Polling para atualizar a dashboard sem reload.
 */
session_start();
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['status' => 'error', 'message' => 'Unauthorized']);
    exit();
}

$base_url = '../../';
require_once '../../configuracoes/base_dados.php';

$user_id = $_SESSION['user_id'];

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Estatísticas Globais
    $stmt = $db->prepare("
        SELECT 
            COUNT(p.project_id) as total_projects,
            COALESCE(SUM((SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id)), 0) as total_likes,
            COALESCE(SUM((SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id)), 0) as total_comments,
            COALESCE(SUM((SELECT COUNT(*) FROM project_views_log WHERE project_id = p.project_id)), 0) as total_views
        FROM projects p
        WHERE p.owner_id = ?
    ");
    $stmt->execute([$user_id]);
    $global_stats = $stmt->fetch(PDO::FETCH_ASSOC);

    // 2. Performance por Projecto (Top 5)
    $top_stmt = $db->prepare("
        SELECT * FROM (
            SELECT p.project_id, p.title, 
                   (SELECT COUNT(*) FROM project_likes WHERE project_id = p.project_id) as likes,
                   (SELECT COUNT(*) FROM project_comments WHERE project_id = p.project_id) as comments,
                   (SELECT COUNT(*) FROM project_views_log WHERE project_id = p.project_id) as views
            FROM projects p
            WHERE p.owner_id = ?
        ) as project_rank
        ORDER BY (likes + views + comments) DESC
        LIMIT 50
    ");
    $top_stmt->execute([$user_id]);
    $performance = $top_stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'status' => 'success',
        'global' => $global_stats,
        'projects' => $performance
    ]);

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
?>
