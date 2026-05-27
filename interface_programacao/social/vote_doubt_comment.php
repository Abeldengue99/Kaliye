<?php
// servicos/social/vote_doubt_comment.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}
requireValidCSRFTokenJson();

$comment_id = $_POST['comment_id'] ?? null;

if (!$comment_id) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Check if already voted
    $check = $db->prepare("SELECT vote_id FROM doubt_comment_votes WHERE user_id = ? AND comment_id = ?");
    $check->execute([$_SESSION['user_id'], $comment_id]);
    $vote = $check->fetch();

    if ($vote) {
        // Remove vote (toggle)
        $del = $db->prepare("DELETE FROM doubt_comment_votes WHERE vote_id = ?");
        $del->execute([$vote['vote_id']]);
        $action = 'removed';
    } else {
        // Add vote
        $ins = $db->prepare("INSERT INTO doubt_comment_votes (user_id, comment_id, created_at) VALUES (?, ?, NOW())");
        $ins->execute([$_SESSION['user_id'], $comment_id]);
        $action = 'added';
    }

    // Get updated count
    $cnt = $db->prepare("SELECT COUNT(*) FROM doubt_comment_votes WHERE comment_id = ?");
    $cnt->execute([$comment_id]);
    $new_count = (int)$cnt->fetchColumn();

    echo json_encode(['success' => true, 'action' => $action, 'new_count' => $new_count]);

} catch (PDOException $e) {
    error_log('vote_doubt_comment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao registar voto.']);
}
