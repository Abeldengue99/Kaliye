<?php
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/free_mentorship_schema.php';

session_start();
require_once '../../inclusoes/auth_check.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessao expirada.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();
ensureFreeMentorshipTables($db);

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'message' => 'Apenas mentores aprovados podem candidatar-se a pedidos de mentoria.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit;
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$message = trim($_POST['message'] ?? '');

if (!$request_id) {
    echo json_encode(['success' => false, 'message' => 'ID do pedido invalido.']);
    exit;
}

try {
    $stmt_req = $db->prepare("SELECT * FROM free_mentorship_requests WHERE request_id = ?");
    $stmt_req->execute([$request_id]);
    $req = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$req) {
        echo json_encode(['success' => false, 'message' => 'Pedido nao encontrado.']);
        exit;
    }

    if ($req['status'] !== 'open') {
        echo json_encode(['success' => false, 'message' => 'Este pedido ja nao esta aberto para candidaturas.']);
        exit;
    }

    if ((int)$req['student_id'] === $user_id) {
        echo json_encode(['success' => false, 'message' => 'Nao pode candidatar-se ao seu proprio pedido.']);
        exit;
    }

    if (!isEligibleForFreeMentorshipRequest($db, $user_id, $req)) {
        echo json_encode(['success' => false, 'message' => 'Este pedido foi direcionado a mentores com experiencia nesta categoria/tema. Atualize as suas especialidades se domina esta area.']);
        exit;
    }

    $stmt_check = $db->prepare("SELECT COUNT(*) FROM free_mentorship_applications WHERE request_id = ? AND mentor_id = ?");
    $stmt_check->execute([$request_id, $user_id]);
    if ($stmt_check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Ja se candidatou a este pedido.']);
        exit;
    }

    $stmt = $db->prepare("INSERT INTO free_mentorship_applications (request_id, mentor_id, message, status, created_at) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP)");
    $result = $stmt->execute([$request_id, $user_id, $message]);

    if (!$result) {
        echo json_encode(['success' => false, 'message' => 'Erro ao enviar candidatura.']);
        exit;
    }

    $info_stmt = $db->prepare("
        SELECT u.full_name, r.title, r.student_id
        FROM users u, free_mentorship_requests r
        WHERE u.user_id = :mentor_id AND r.request_id = :request_id
    ");
    $info_stmt->execute(['mentor_id' => $user_id, 'request_id' => $request_id]);
    $info = $info_stmt->fetch(PDO::FETCH_ASSOC);

    if ($info) {
        $notif_title = 'Nova candidatura de mentoria';
        $notif_content = $info['full_name'] . " candidatou-se para ajudar no seu pedido: '" . $info['title'] . "'. Clique para analisar e aceitar.";
        $link = 'paginas/mentoria/free_mentorship_requests.php?request_id=' . $request_id;

        $ins_notif = $db->prepare("
            INSERT INTO notifications (user_id, sender_id, title, content, type, link, is_read, created_at)
            VALUES (?, ?, ?, ?, 'mentorship_application', ?, false, CURRENT_TIMESTAMP)
        ");
        $ins_notif->execute([$info['student_id'], $user_id, $notif_title, $notif_content, $link]);
    }

    echo json_encode(['success' => true, 'message' => 'Candidatura enviada com sucesso!']);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
