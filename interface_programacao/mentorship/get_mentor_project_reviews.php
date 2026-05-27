<?php
// interface_programacao/mentorship/get_mentor_project_reviews.php
// Busca progressos pendentes de validação pelo mentor

header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit();
}

$mentor_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

try {
    // Buscar relatórios pendentes de mentor ou com feedback do mentor (em curso) que pertencem a projectos deste mentor
    $query = "SELECT r.*, p.title as project_name, u.full_name as author_name, u.profile_pic 
              FROM project_progress_reports r
              JOIN projects p ON r.project_id = p.project_id
              JOIN users u ON r.author_id = u.user_id
              WHERE p.assigned_mentor_id = ? 
              AND r.report_status IN ('pending_mentor', 'mentor_feedback')
              ORDER BY r.created_at DESC";
    $stmt = $db->prepare($query);
    $stmt->execute([$mentor_id]);
    $reviews = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'reviews' => $reviews]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
