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
$request_id = isset($_GET['request_id']) ? intval($_GET['request_id']) : 0;

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
    exit;
}

$db = (new Database())->getConnection();

try {
    ensureFreeMentorshipTables($db);

    // Get request details with session info if exists
    $query = "SELECT r.*, u.full_name, u.profile_pic, 
              s.session_date, s.duration_minutes, s.meeting_link, s.session_id
              FROM free_mentorship_requests r
              JOIN users u ON r.student_id = u.user_id
              LEFT JOIN free_mentorship_sessions s ON r.request_id = s.request_id
              WHERE r.request_id = ?";
    $req_stmt = $db->prepare($query);
    $req_stmt->execute([$request_id]);
    $request = $req_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$request) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
        exit;
    }

    $request['user_type_label'] = 'Estudante';
    $request['user_has_applied'] = false;

    // Check if current user has applied (if user is a mentor candidate)
    $stmt_check = $db->prepare("SELECT COUNT(*) FROM free_mentorship_applications WHERE request_id = ? AND mentor_id = ?");
    $stmt_check->execute([$request_id, $user_id]);
    $request['user_has_applied'] = ($stmt_check->fetchColumn() > 0);

    // Get applications (only if user is student or has applied or is admin)
    // For now, let's keep it simple: student sees all, others see only if allowed
    $applications = [];
    if ($user_id == $request['student_id'] || $_SESSION['user_type'] === 'admin') {
        $app_stmt = $db->prepare("SELECT a.*, u.full_name, u.profile_pic, u.avaliacao 
                                 FROM free_mentorship_applications a
                                 JOIN users u ON a.mentor_id = u.user_id
                                 WHERE a.request_id = ?
                                 ORDER BY a.created_at ASC");
        $app_stmt->execute([$request_id]);
        $applications = $app_stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($applications as &$app) {
            $app['user_type_label'] = 'Mentor';
        }
    }

    echo json_encode(['success' => true, 'request' => $request, 'applications' => $applications]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
