<?php
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/free_mentorship_schema.php';

session_start();
require_once '../../inclusoes/auth_check.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$db = (new Database())->getConnection();

try {
    ensureFreeMentorshipTables($db);

    if (!canActAsMentor()) {
        echo json_encode(['success' => true, 'requests' => []]);
        exit;
    }

    $eligibility = buildFreeMentorshipMentorEligibilitySql('me');
    $query = "SELECT r.*, u.full_name, u.profile_pic,
              (SELECT COUNT(*) FROM free_mentorship_applications WHERE request_id = r.request_id) as application_count,
              (SELECT status FROM free_mentorship_applications WHERE request_id = r.request_id AND mentor_id = ?) as user_application_status
              FROM free_mentorship_requests r
              JOIN users u ON r.student_id = u.user_id
              JOIN users me ON me.user_id = ?
              WHERE r.status = 'open'
                AND r.student_id != ?
                AND $eligibility
                AND (
                    lower(COALESCE(r.category, '')) IN (
                        SELECT lower(COALESCE(ka.category, ''))
                        FROM user_expertises ue
                        JOIN knowledge_areas ka ON ka.area_id = ue.area_id
                        WHERE ue.user_id = ? AND COALESCE(ue.can_mentor, true) = true
                    )
                    OR lower(COALESCE(r.category, '')) IN (
                        SELECT lower(COALESCE(ka.name, ''))
                        FROM user_expertises ue
                        JOIN knowledge_areas ka ON ka.area_id = ue.area_id
                        WHERE ue.user_id = ? AND COALESCE(ue.can_mentor, true) = true
                    )
                    OR lower(COALESCE(me.specialization_tags, '')) LIKE '%' || lower(COALESCE(r.category, '')) || '%'
                    OR EXISTS (
                        SELECT 1
                        FROM user_expertises ue
                        JOIN knowledge_areas ka ON ka.area_id = ue.area_id
                        WHERE ue.user_id = ?
                          AND COALESCE(ue.can_mentor, true) = true
                          AND (
                              lower(COALESCE(r.title, '')) LIKE '%' || lower(ka.name) || '%'
                              OR lower(COALESCE(r.description, '')) LIKE '%' || lower(ka.name) || '%'
                          )
                    )
                )
              ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $user_id, $user_id, $user_id, $user_id, $user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Filter/Label the data
    foreach ($requests as &$req) {
        $req['user_type_label'] = 'Estudante'; // Simplification for now
    }

    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
