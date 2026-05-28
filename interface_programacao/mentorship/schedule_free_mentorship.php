<?php
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/free_mentorship_schema.php';
require_once '../../inclusoes/SimpleMailer.php';

session_start();
require_once '../../inclusoes/auth_check.php';
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$db = (new Database())->getConnection();
ensureFreeMentorshipTables($db);

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit;
}

$request_id = isset($_POST['request_id']) ? intval($_POST['request_id']) : 0;
$session_date = trim($_POST['session_date'] ?? '');
$duration = isset($_POST['duration']) ? max(15, min(240, intval($_POST['duration']))) : 60;
$meeting_link = trim($_POST['meeting_link'] ?? '');

if (!$request_id || $session_date === '') {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit;
}

$timestamp = strtotime($session_date);
if (!$timestamp) {
    echo json_encode(['success' => false, 'message' => 'Data da sessão invalida.']);
    exit;
}

if ($meeting_link !== '' && !filter_var($meeting_link, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Informe um link de reuniao valido.']);
    exit;
}

try {
    $stmt_req = $db->prepare("
        SELECT r.*, u.full_name as student_name, u.email as student_email
        FROM free_mentorship_requests r
        JOIN users u ON r.student_id = u.user_id
        WHERE r.request_id = ?
    ");
    $stmt_req->execute([$request_id]);
    $req = $stmt_req->fetch(PDO::FETCH_ASSOC);

    if (!$req || (int)$req['selected_mentor_id'] !== $user_id) {
        echo json_encode(['success' => false, 'message' => 'Apenas o mentor selecionado pode agendar a sessão.']);
        exit;
    }

    if ($req['status'] !== 'in_progress') {
        echo json_encode(['success' => false, 'message' => 'Esta mentoria não esta em progresso.']);
        exit;
    }

    $db->beginTransaction();

    $start = date('Y-m-d H:i:s', $timestamp);
    $end = date('Y-m-d H:i:s', $timestamp + ($duration * 60));
    $room_name = 'Aksanti_Free_' . substr(md5($request_id . '_' . $user_id . '_' . $start), 0, 16);
    $internal_link = '../../paginas/mentoria/meeting.php?room=' . $room_name;
    $final_link = $meeting_link !== '' ? $meeting_link : $internal_link;

    $stmt_session = $db->prepare("SELECT session_id, mentorship_slot_id FROM free_mentorship_sessions WHERE request_id = ? ORDER BY session_id ASC LIMIT 1");
    $stmt_session->execute([$request_id]);
    $session = $stmt_session->fetch(PDO::FETCH_ASSOC);

    $slot_id = $session['mentorship_slot_id'] ?? null;
    if ($slot_id) {
        $upd_slot = $db->prepare("
            UPDATE mentorship_slots
            SET start_time = ?, end_time = ?, status = 'confirmed', participant_id = ?, meeting_link = ?, meeting_room = ?, platform = ?, title = ?, description = ?, category = ?, duration = ?
            WHERE slot_id = ? AND mentor_id = ?
        ");
        $upd_slot->execute([
            $start,
            $end,
            $req['student_id'],
            $final_link,
            $room_name,
            $meeting_link !== '' ? 'external' : 'jitsi',
            $req['title'],
            $req['description'],
            $req['category'],
            $duration,
            $slot_id,
            $user_id,
        ]);
    } else {
        $ins_slot = $db->prepare("
            INSERT INTO mentorship_slots
                (mentor_id, participant_id, start_time, end_time, status, meeting_link, meeting_room, platform, title, description, category, duration, created_at)
            VALUES (?, ?, ?, ?, 'confirmed', ?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $ins_slot->execute([
            $user_id,
            $req['student_id'],
            $start,
            $end,
            $final_link,
            $room_name,
            $meeting_link !== '' ? 'external' : 'jitsi',
            $req['title'],
            $req['description'],
            $req['category'],
            $duration,
        ]);
        $slot_id = (int)$db->lastInsertId();
    }

    if ($session) {
        $upd_s = $db->prepare("UPDATE free_mentorship_sessions SET session_date = ?, duration_minutes = ?, meeting_link = ?, mentorship_slot_id = ?, created_at = CURRENT_TIMESTAMP WHERE session_id = ?");
        $upd_s->execute([$start, $duration, $final_link, $slot_id, $session['session_id']]);
    } else {
        $ins_s = $db->prepare("
            INSERT INTO free_mentorship_sessions (request_id, mentor_id, student_id, mentorship_slot_id, session_date, duration_minutes, meeting_link, created_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)
        ");
        $ins_s->execute([$request_id, $user_id, $req['student_id'], $slot_id, $start, $duration, $final_link]);
    }

    $mentor_name = $_SESSION['user_name'] ?? 'O seu mentor';
    $formatted_date = date('d/m/Y H:i', $timestamp);
    $msg = "A sua mentoria com $mentor_name foi agendada para $formatted_date.";

    $ins_notif = $db->prepare("
        INSERT INTO notifications (user_id, sender_id, title, content, type, link, is_read, created_at)
        VALUES (?, ?, 'Mentoria agendada', ?, 'mentorship_scheduled', ?, false, CURRENT_TIMESTAMP)
    ");
    $ins_notif->execute([$req['student_id'], $user_id, $msg, $final_link]);

    $mailer = new SimpleMailer();
    $safe_name = htmlspecialchars($req['student_name'] ?? 'Estudante', ENT_QUOTES, 'UTF-8');
    $safe_title = htmlspecialchars($req['title'], ENT_QUOTES, 'UTF-8');
    $safe_link = htmlspecialchars($final_link, ENT_QUOTES, 'UTF-8');
    $email_body = "
        <div style='font-family: Arial, sans-serif; padding: 20px; color: #333;'>
            <h2 style='color: #f7941d;'>Mentoria Agendada!</h2>
            <p>Ola, <b>$safe_name</b>,</p>
            <p>A sua sessão de mentoria gratuita para o pedido: <b>$safe_title</b> foi agendada.</p>
            <div style='background: #f4f4f4; padding: 15px; border-radius: 8px; margin: 20px 0;'>
                <p><b>Data e Hora:</b> $formatted_date</p>
                <p><b>Duracao:</b> $duration minutos</p>
                <p><b>Link da Reuniao:</b> <a href='$safe_link'>$safe_link</a></p>
            </div>
            <p>Prepare as suas dúvidas e esteja online no horario combinado.</p>
            <p>Equipa KALIYE</p>
        </div>
    ";

    if (!empty($req['student_email'])) {
        $mailer->send($req['student_email'], $req['student_name'], 'Mentoria Agendada: ' . $req['title'], $email_body);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Mentoria agendada com sucesso! O estudante foi notificado.', 'meeting_link' => $final_link]);
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}
