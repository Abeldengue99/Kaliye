<?php
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/free_mentorship_schema.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

try {
    ensureFreeMentorshipTables($db);

    // Get scheduled mentorships for the current user (either as student or mentor)
    $query = "SELECT 
                r.request_id,
                r.title,
                r.student_id,
                r.selected_mentor_id,
                r.session_date,
                r.duration_minutes,
                r.meeting_link,
                r.status,
                student.full_name as student_name,
                student.profile_pic as student_profile_pic,
                mentor.full_name as mentor_name,
                mentor.profile_pic as mentor_profile_pic
              FROM free_mentorship_requests r
              JOIN users student ON r.student_id = student.user_id
              LEFT JOIN users mentor ON r.selected_mentor_id = mentor.user_id
              WHERE (r.student_id = ? OR r.selected_mentor_id = ?)
                AND r.session_date IS NOT NULL
                AND r.archived_at IS NULL
                AND r.status IN ('in_progress', 'completed')
              ORDER BY r.session_date DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $user_id]);
    $mentorships = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'mentorships' => $mentorships]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
