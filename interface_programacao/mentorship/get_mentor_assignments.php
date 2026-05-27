<?php
// servicos/mentorship/get_mentor_assignments.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $table_check = $db->prepare("SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = 'mentor_assignments'");
    $table_check->execute();
    if ($table_check->rowCount() == 0) {
        // Table missing, return empty
        echo json_encode(['success' => true, 'assignments' => []]);
        exit;
    }

    // Standard query for assignments
    $query = "SELECT ma.*, u.full_name as student_name, p.title as project_title, p.description as project_description
              FROM mentor_assignments ma
              JOIN users u ON ma.student_id = u.user_id
              LEFT JOIN projects p ON ma.project_id = p.project_id
              WHERE ma.mentor_id = ? AND ma.status = 'pending'";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $assignments = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'assignments' => $assignments]);

} catch (PDOException $e) {
     echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

