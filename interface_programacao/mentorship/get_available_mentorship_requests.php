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

    $user_stmt = $db->prepare("SELECT user_type, mentorship_status FROM users WHERE user_id = ?");
    $user_stmt->execute([$user_id]);
    $current_user = $user_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    if (!canActAsMentor($current_user)) {
        echo json_encode(['success' => true, 'requests' => []]);
        exit;
    }

    $query = "SELECT r.*, u.full_name, u.profile_pic,
              (SELECT COUNT(*) FROM free_mentorship_applications WHERE request_id = r.request_id) as application_count,
              (SELECT status FROM free_mentorship_applications WHERE request_id = r.request_id AND mentor_id = ?) as user_application_status
              FROM free_mentorship_requests r
              JOIN users u ON r.student_id = u.user_id
              WHERE r.status = 'open'
                AND r.archived_at IS NULL
                AND r.student_id != ?
              ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as &$req) {
        $req['user_type_label'] = 'Estudante';
    }

    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
