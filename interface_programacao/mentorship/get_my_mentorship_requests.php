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

    // List requests created by the current user
    $query = "SELECT r.*, u.full_name, u.profile_pic, 
              (SELECT COUNT(*) FROM free_mentorship_applications WHERE request_id = r.request_id) as application_count
              FROM free_mentorship_requests r
              JOIN users u ON r.student_id = u.user_id
              WHERE r.student_id = ?
              ORDER BY r.created_at DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $requests = $stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach ($requests as &$req) {
        $req['user_type_label'] = 'Eu';
    }

    echo json_encode(['success' => true, 'requests' => $requests]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
