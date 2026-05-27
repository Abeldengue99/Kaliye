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

$application_id = isset($_POST['application_id']) ? intval($_POST['application_id']) : 0;
$action = $_POST['action'] ?? ''; // 'accept' or 'reject'

if (!$application_id || !in_array($action, ['accept', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Ação ou ID da candidatura inválido.']);
    exit;
}

try {
    // Get application and request info to verify ownership and fetch doubt context
    $stmt_app = $db->prepare("SELECT a.*, r.title as request_title, r.student_id, r.status as request_status, r.doubt_id 
                             FROM free_mentorship_applications a
                             JOIN free_mentorship_requests r ON a.request_id = r.request_id
                             WHERE a.application_id = ?");
    $stmt_app->execute([$application_id]);
    $app = $stmt_app->fetch();

    if (!$app) {
        echo json_encode(['success' => false, 'message' => 'Candidatura não encontrada.']);
        exit;
    }

    if ($app['student_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Não tem permissão para responder a esta candidatura.']);
        exit;
    }

    if ($action === 'accept') {
        if ($app['request_status'] !== 'open') {
            echo json_encode(['success' => false, 'message' => 'Este pedido já não está aberto.']);
            exit;
        }

        $db->beginTransaction();

        // 1. Update the application status
        $upd_app = $db->prepare("UPDATE free_mentorship_applications SET status = 'accepted', responded_at = CURRENT_TIMESTAMP WHERE application_id = ?");
        $upd_app->execute([$application_id]);

        // 2. Reject all other applications for this request
        $rej_others = $db->prepare("UPDATE free_mentorship_applications SET status = 'rejected', responded_at = CURRENT_TIMESTAMP WHERE request_id = ? AND application_id != ? AND status = 'pending'");
        $rej_others->execute([$app['request_id'], $application_id]);

        // 3. Update request status to 'in_progress' and set the selected mentor
        $upd_req = $db->prepare("UPDATE free_mentorship_requests SET status = 'in_progress', selected_mentor_id = ?, started_at = CURRENT_TIMESTAMP, updated_at = CURRENT_TIMESTAMP WHERE request_id = ?");
        $upd_req->execute([$app['mentor_id'], $app['request_id']]);

        // 4. Also insert into general 'mentorships' table if not exists to link them
        $check_m = $db->prepare("SELECT COUNT(*) FROM mentorships WHERE mentor_id = ? AND mentee_id = ?");
        $check_m->execute([$app['mentor_id'], $app['student_id']]);
        if ($check_m->fetchColumn() == 0) {
            $ins_m = $db->prepare("INSERT INTO mentorships (mentor_id, mentee_id, status, started_at) VALUES (?, ?, 'active', CURRENT_TIMESTAMP)");
            $ins_m->execute([$app['mentor_id'], $app['student_id']]);
        }
        
        // 4.1 Auto-resolver a Dúvida Inicial para ninguém mais se candidatar!
        if (!empty($app['doubt_id'])) {
            $upd_doubt = $db->prepare("UPDATE doubts SET status = 'resolved' WHERE doubt_id = ?");
            $upd_doubt->execute([$app['doubt_id']]);
        }

        // 5. Create notification for the MENTOR (Notificamos o mentor que a sua candidatura foi escolhida)
        $notif_title = "✅ Candidatura Aceite!";
        $notif_msg = "A sua candidatura de mentoria para ajudar no pedido '" . $app['request_title'] . "' foi aceite pelo aluno! Por favor, entre em contacto e agende a sessão.";
        $link = 'paginas/mentoria/free_mentorship_requests.php?request_id=' . $app['request_id'];
        
        $ins_notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, is_read, created_at) 
                                   VALUES (?, ?, ?, ?, 'mentorship_accepted', ?, false, CURRENT_TIMESTAMP)");
        $ins_notif->execute([$app['mentor_id'], $app['student_id'], $notif_title, $notif_msg, $link]);

        $db->commit();
        echo json_encode(['success' => true, 'message' => 'Candidatura aceite! A mentoria começou.']);
    } else {
        // Just reject this specific application
        $upd_app = $db->prepare("UPDATE free_mentorship_applications SET status = 'rejected', responded_at = CURRENT_TIMESTAMP WHERE application_id = ?");
        $upd_app->execute([$application_id]);
        echo json_encode(['success' => true, 'message' => 'Candidatura recusada.']);
    }

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
