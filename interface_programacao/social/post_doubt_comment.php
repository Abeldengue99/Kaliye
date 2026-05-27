<?php
// servicos/social/post_doubt_comment.php
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
$content = $_POST['content'] ?? null;
$parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

if (!$doubt_id || !$content) {
    echo json_encode(['success' => false, 'message' => 'Conteúdo obrigatório']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $query = "INSERT INTO doubt_comments (doubt_id, user_id, parent_id, content, created_at) VALUES (:did, :uid, :pid, :content, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':did', $doubt_id);
    $stmt->bindParam(':uid', $_SESSION['user_id']);
    $stmt->bindParam(':pid', $parent_id);
    $stmt->bindParam(':content', $content);

    if ($stmt->execute()) {
        // Notify owner if not self
        // TODO: Notification Logic
        
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao comentar']);
    }

} catch (PDOException $e) {
    error_log('post_doubt_comment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao publicar comentario.']);
}

