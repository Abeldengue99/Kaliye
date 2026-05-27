<?php
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/free_mentorship_schema.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessao expirada.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();
ensureFreeMentorshipTables($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit;
}

$title = trim($_POST['title'] ?? '');
$description = trim($_POST['description'] ?? '');
$category = trim($_POST['category'] ?? '');
$difficulty_level = trim($_POST['difficulty_level'] ?? 'beginner');
$estimated_duration = trim($_POST['estimated_duration'] ?? '');
$doubt_id = !empty($_POST['doubt_id']) ? intval($_POST['doubt_id']) : null;

if ($title === '' || $description === '') {
    echo json_encode(['success' => false, 'message' => 'Titulo e descricao sao obrigatorios.']);
    exit;
}

if (!in_array($difficulty_level, ['beginner', 'intermediate', 'advanced'], true)) {
    $difficulty_level = 'beginner';
}

if ($doubt_id) {
    $check = $db->prepare("SELECT COUNT(*) FROM free_mentorship_requests WHERE student_id = ? AND doubt_id = ? AND status IN ('open', 'in_progress')");
    $check->execute([$user_id, $doubt_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ja tens um pedido de mentoria ativo para esta duvida.']);
        exit;
    }
}

try {
    $stmt = $db->prepare("
        INSERT INTO free_mentorship_requests
            (student_id, doubt_id, title, description, category, difficulty_level, estimated_duration, status, created_at, updated_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, 'open', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
    ");
    $result = $stmt->execute([$user_id, $doubt_id, $title, $description, $category, $difficulty_level, $estimated_duration]);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Erro ao criar pedido.']);
        exit;
    }

    $request_id = (int)$db->lastInsertId();

    if ($doubt_id) {
        $update_doubt = $db->prepare("UPDATE doubts SET status = 'mentorship_requested' WHERE doubt_id = ? AND user_id = ?");
        $update_doubt->execute([$doubt_id, $user_id]);
    }

    $request_context = [
        'request_id' => $request_id,
        'student_id' => $user_id,
        'title' => $title,
        'description' => $description,
        'category' => $category,
    ];
    $eligible_users = getEligibleFreeMentorshipMentorIds($db, $request_context, $user_id, false);

    if ($eligible_users) {
        $notif_title = 'Nova oportunidade de mentoria';
        $notif_content = "Um estudante pediu ajuda no tema: '" . mb_strimwidth($title, 0, 70, '...') . "'. Candidate-se se esta area faz parte da sua experiencia.";
        $link = 'paginas/mentoria/free_mentorship_requests.php?request_id=' . $request_id;
        $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, is_read, created_at) VALUES (?, ?, ?, ?, 'mentorship_request', ?, false, CURRENT_TIMESTAMP)");
        foreach ($eligible_users as $mentor_id) {
            $notif_ins->execute([$mentor_id, $user_id, $notif_title, $notif_content, $link]);
        }
    }

    $message = $eligible_users
        ? 'Pedido de mentoria criado com sucesso! Os mentores mais alinhados foram notificados.'
        : 'Pedido de mentoria criado com sucesso! Nenhum mentor compativel foi encontrado para notificar agora.';

    echo json_encode(['success' => true, 'message' => $message, 'request_id' => $request_id, 'notified_mentors' => count($eligible_users)]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
