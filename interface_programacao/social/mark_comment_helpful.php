<?php
// servicos/social/mark_comment_helpful.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}
requireValidCSRFTokenJson();

$doubt_id = $_POST['doubt_id'] ?? null;
$comment_id = $_POST['comment_id'] ?? null;

if (!$doubt_id || !$comment_id) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Check ownership of doubt
    $check = $db->prepare("SELECT user_id FROM doubts WHERE doubt_id = ?");
    $check->execute([$doubt_id]);
    $doubt = $check->fetch();

    if (!$doubt || $doubt['user_id'] != $_SESSION['user_id']) {
        echo json_encode(['success' => false, 'message' => 'Apenas o autor pode marcar como útil']);
        exit();
    }

    // Mark comment as helpful
    $update = $db->prepare("UPDATE doubt_comments SET is_helpful = true WHERE comment_id = ?");
    $update->execute([$comment_id]);
    
    // Mark doubt as resolved
    $resolve = $db->prepare("UPDATE doubts SET status = 'resolved' WHERE doubt_id = ?");
    $resolve->execute([$doubt_id]);

    echo json_encode(['success' => true]);

} catch (PDOException $e) {
    error_log('mark_comment_helpful error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao marcar comentario.']);
}

