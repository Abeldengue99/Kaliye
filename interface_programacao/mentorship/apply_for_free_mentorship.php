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

$user_stmt = $db->prepare("SELECT user_type, mentorship_status FROM users WHERE user_id = ?");
$user_stmt->execute([$user_id]);
$current_user = $user_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

if (!canActAsMentor($current_user)) {
    echo json_encode(['success' => false, 'message' => 'Apenas mentores aprovados podem assumir pedidos de mentoria.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit;
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;

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

    if ((int)$req['student_id'] === $user_id) {
        echo json_encode(['success' => false, 'message' => 'Nao pode assumir o seu proprio pedido.']);
        exit;
    }

    $db->beginTransaction();

    $claim = $db->prepare("
        UPDATE free_mentorship_requests
        SET status = 'in_progress',
            selected_mentor_id = ?,
            started_at = CURRENT_TIMESTAMP,
            updated_at = CURRENT_TIMESTAMP
        WHERE request_id = ?
          AND status = 'open'
          AND selected_mentor_id IS NULL
    ");
    $claim->execute([$user_id, $request_id]);

    if ($claim->rowCount() !== 1) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Esta solicitacao ja foi atribuida a outro mentor.']);
        exit;
    }

    $stmt = $db->prepare("
        UPDATE free_mentorship_applications
        SET status = 'accepted',
            responded_at = CURRENT_TIMESTAMP
        WHERE request_id = ?
          AND mentor_id = ?
    ");
    $stmt->execute([$request_id, $user_id]);

    if ($stmt->rowCount() === 0) {
        $stmt = $db->prepare("
            INSERT INTO free_mentorship_applications (request_id, mentor_id, message, status, created_at, responded_at)
            VALUES (?, ?, '', 'accepted', CURRENT_TIMESTAMP, CURRENT_TIMESTAMP)
        ");
        $stmt->execute([$request_id, $user_id]);
    }

    $reject_others = $db->prepare("
        UPDATE free_mentorship_applications
        SET status = 'rejected', responded_at = CURRENT_TIMESTAMP
        WHERE request_id = ? AND mentor_id != ? AND status = 'pending'
    ");
    $reject_others->execute([$request_id, $user_id]);

    $check_m = $db->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND mentee_id = ? AND status = 'active'");
    $check_m->execute([$user_id, $req['student_id']]);
    if ($check_m->fetchColumn() == 0) {
        $ins_m = $db->prepare("INSERT INTO mentorships (mentor_id, mentee_id, status) VALUES (?, ?, 'active')");
        $ins_m->execute([$user_id, $req['student_id']]);
    }

    if (!empty($req['doubt_id'])) {
        $upd_doubt = $db->prepare("UPDATE doubts SET status = 'mentorship_requested' WHERE doubt_id = ?");
        $upd_doubt->execute([$req['doubt_id']]);
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'Pedido assumido com sucesso. Escolha agora a data da mentoria.',
        'request_id' => $request_id,
        'next_action' => 'schedule',
    ]);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
