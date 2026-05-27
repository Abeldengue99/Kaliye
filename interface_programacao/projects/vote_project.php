<?php
/**
 * vote_project.php - Aksanti
 * Toggle de voto comunitario numa ideia.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/project_votes_schema.php';

if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Sessao expirada. Faz login novamente.']);
    exit;
}

$voter_id = (int) $_SESSION['user_id'];
requireValidCSRFTokenJson();
$input = json_decode(file_get_contents('php://input'), true);
$project_id = isset($input['project_id']) ? (int)$input['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de projecto invalido.']);
    exit;
}

$db = (new Database())->getConnection();

try {
    ensureProjectVotesTable($db);

    $voter_stmt = $db->prepare("
        SELECT user_type, verification_status, mentorship_status
        FROM users
        WHERE user_id = ?
    ");
    $voter_stmt->execute([$voter_id]);
    $voter = $voter_stmt->fetch(PDO::FETCH_ASSOC) ?: [];

    $user_type = $voter['user_type'] ?? 'student';
    $verification_status = $voter['verification_status'] ?? 'unsubmitted';
    $mentorship_status = $voter['mentorship_status'] ?? 'unsubmitted';

    $daily_vote_limit = 10;
    if ($verification_status === 'verified' || $verification_status === 'approved') {
        $daily_vote_limit = 15;
    }
    if ($user_type === 'mentor' || $user_type === 'investor' || $mentorship_status === 'approved') {
        $daily_vote_limit = 20;
    }
    if ($user_type === 'admin') {
        $daily_vote_limit = 50;
    }

    $project_stmt = $db->prepare("SELECT project_id, owner_id, title FROM projects WHERE project_id = ?");
    $project_stmt->execute([$project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projecto nao encontrado.']);
        exit;
    }

    if ((int)$project['owner_id'] === $voter_id) {
        echo json_encode(['success' => false, 'message' => 'Nao podes votar na tua propria ideia.']);
        exit;
    }

    $check_vote = $db->prepare("SELECT vote_id FROM project_votes WHERE project_id = ? AND voter_id = ?");
    $check_vote->execute([$project_id, $voter_id]);
    $existing_vote = $check_vote->fetchColumn();

    if ($existing_vote) {
        $delete = $db->prepare("DELETE FROM project_votes WHERE project_id = ? AND voter_id = ?");
        $delete->execute([$project_id, $voter_id]);
        $voted = false;
    } else {
        $daily_count_stmt = $db->prepare("
            SELECT COUNT(*)
            FROM project_vote_events
            WHERE voter_id = ?
              AND created_at >= date_trunc('day', CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Luanda')
              AND created_at < date_trunc('day', CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Luanda') + INTERVAL '1 day'
        ");
        $daily_count_stmt->execute([$voter_id]);
        $votes_today = (int)$daily_count_stmt->fetchColumn();

        if ($votes_today >= $daily_vote_limit) {
            echo json_encode([
                'success' => false,
                'limit_reached' => true,
                'daily_limit' => $daily_vote_limit,
                'remaining_votes' => 0,
                'message' => 'Atingiste o limite diario de ' . $daily_vote_limit . ' votos. Amanhã podes voltar a votar em novos projectos.'
            ]);
            exit;
        }

        $insert = $db->prepare("INSERT INTO project_votes (project_id, voter_id) VALUES (?, ?)");
        $insert->execute([$project_id, $voter_id]);

        $event = $db->prepare("
            INSERT INTO project_vote_events (project_id, voter_id, created_at)
            VALUES (?, ?, CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Luanda')
        ");
        $event->execute([$project_id, $voter_id]);

        $voted = true;
    }

    $count_stmt = $db->prepare("SELECT COUNT(*) FROM project_votes WHERE project_id = ?");
    $count_stmt->execute([$project_id]);
    $total_votes = (int)$count_stmt->fetchColumn();

    if ($voted) {
        try {
            $sender_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
            $sender_stmt->execute([$voter_id]);
            $sender_name = $sender_stmt->fetchColumn() ?: 'Um utilizador';

            $notif = $db->prepare("
                INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at)
                VALUES (?, ?, ?, ?, 'project_vote', ?, NOW())
            ");
            $notif->execute([
                (int)$project['owner_id'],
                $voter_id,
                'A sua ideia recebeu um voto',
                $sender_name . " votou na sua ideia: '" . $project['title'] . "'.",
                'index.php?project_modal=' . $project_id
            ]);
        } catch (Exception $e) {
            error_log('Vote notification skipped: ' . $e->getMessage());
        }
    }

    echo json_encode([
        'success' => true,
        'voted' => $voted,
        'total_votes' => $total_votes,
        'daily_limit' => $daily_vote_limit,
        'remaining_votes' => $voted ? max(0, $daily_vote_limit - (($votes_today ?? 0) + 1)) : null,
        'message' => $voted ? 'Voto registado!' : 'Voto removido.'
    ]);
} catch (PDOException $e) {
    error_log('Vote Project Error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao registar voto.']);
}
