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
ensureFreeMentorshipTables($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$rating = isset($_POST['rating']) ? intval($_POST['rating']) : 5; // 1-5
$feedback = $_POST['feedback'] ?? '';

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido inválido.']);
    exit;
}

try {
    // Get request info to verify ownership and get mentor_id
    $stmt_req = $db->prepare("SELECT * FROM free_mentorship_requests WHERE request_id = ?");
    $stmt_req->execute([$request_id]);
    $req = $stmt_req->fetch();

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Pedido não encontrado.']);
        exit;
    }

    if ($req['student_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Apenas o estudante pode completar esta mentoria.']);
        exit;
    }

    if ($req['status'] !== 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Esta mentoria não está em progresso.']);
        exit;
    }

    $mentor_id = $req['selected_mentor_id'];

    $db->beginTransaction();

    // 1. Update request status
    $upd_req = $db->prepare("UPDATE free_mentorship_requests SET status = 'completed', completed_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE request_id = ?");
    $upd_req->execute([$request_id]);

    // 2. Attach feedback to the scheduled session when it exists; otherwise create a completion record.
    $stmt_existing = $db->prepare("SELECT session_id, mentorship_slot_id FROM free_mentorship_sessions WHERE request_id = ? ORDER BY session_id ASC LIMIT 1");
    $stmt_existing->execute([$request_id]);
    $existing_session = $stmt_existing->fetch(PDO::FETCH_ASSOC);
    if ($existing_session) {
        $stmt_session = $db->prepare("UPDATE free_mentorship_sessions SET student_feedback = ?, student_rating = ? WHERE session_id = ?");
        $stmt_session->execute([$feedback, $rating, $existing_session['session_id']]);

        if (!empty($existing_session['mentorship_slot_id'])) {
            $upd_slot = $db->prepare("UPDATE mentorship_slots SET status = 'completed' WHERE slot_id = ?");
            $upd_slot->execute([$existing_session['mentorship_slot_id']]);
        }
    } else {
        $stmt_session = $db->prepare("INSERT INTO free_mentorship_sessions (request_id, mentor_id, student_id, session_date, duration_minutes, student_feedback, student_rating, created_at) VALUES (?, ?, ?, CURRENT_TIMESTAMP, 0, ?, ?, CURRENT_TIMESTAMP)");
        $stmt_session->execute([$request_id, $mentor_id, $user_id, $feedback, $rating]);
    }

    // 3. Update associated doubt status to 'resolved' if any
    if (!empty($req['doubt_id'])) {
        $upd_doubt = $db->prepare("UPDATE doubts SET status = 'resolved' WHERE doubt_id = ?");
        $upd_doubt->execute([$req['doubt_id']]);
    }

    // 4. Award Avaliacao to Mentor
    // Formula: (Rating * 10) + difficulty bonus
    $difficulty_bonus = 0;
    if ($req['difficulty_level'] === 'intermediate') $difficulty_bonus = 15;
    elseif ($req['difficulty_level'] === 'advanced') $difficulty_bonus = 30;
    
    $points = ($rating * 10) + $difficulty_bonus;

    $upd_mentor = $db->prepare("UPDATE users SET avaliacao = avaliacao + ? WHERE user_id = ?");
    $upd_mentor->execute([$points, $mentor_id]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => "Mentoria concluída! Atribuiu $points pontos de avaliação ao mentor.", 'points_awarded' => $points]);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
