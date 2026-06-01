<?php
// servicos/social/mark_comment_helpful.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

error_log('mark_comment_helpful: START - User ID: ' . ($_SESSION['user_id'] ?? 'NOT_SET'));

if (!isset($_SESSION['user_id'])) {
    error_log('mark_comment_helpful: FAIL - User not authenticated');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Validate CSRF token
$token = getRequestCSRFToken();
error_log('mark_comment_helpful: CSRF validation');

if (!verifyCSRFToken($token)) {
    error_log('mark_comment_helpful: FAIL - CSRF validation failed');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Pedido bloqueado por segurança. Atualize a página e tente novamente.']);
    exit();
}

$doubt_id = $_POST['doubt_id'] ?? null;
$comment_id = $_POST['comment_id'] ?? null;

error_log('mark_comment_helpful: doubt_id=' . $doubt_id . ', comment_id=' . $comment_id);

if (!$doubt_id || !$comment_id) {
    error_log('mark_comment_helpful: FAIL - Missing parameters');
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

    if (!$doubt) {
        error_log('mark_comment_helpful: FAIL - Doubt not found: ' . $doubt_id);
        echo json_encode(['success' => false, 'message' => 'Dúvida não encontrada']);
        exit();
    }

    if ($doubt['user_id'] != $_SESSION['user_id']) {
        error_log('mark_comment_helpful: FAIL - Not doubt owner. Owner: ' . $doubt['user_id'] . ', Current: ' . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'message' => 'Apenas o autor pode marcar como útil']);
        exit();
    }

    // Mark comment as helpful
    $update = $db->prepare("UPDATE doubt_comments SET is_helpful = true WHERE comment_id = ?");
    $update->execute([$comment_id]);
    
    // Mark doubt as answered
    $resolve = $db->prepare("UPDATE doubts SET status = 'answered' WHERE doubt_id = ?");
    $resolve->execute([$doubt_id]);
    
    // Get comment info and author
    $comment_check = $db->prepare("SELECT user_id, content FROM doubt_comments WHERE comment_id = ?");
    $comment_check->execute([$comment_id]);
    $comment_info = $comment_check->fetch();
    
    // Get doubt title
    $doubt_title_stmt = $db->prepare("SELECT title FROM doubts WHERE doubt_id = ?");
    $doubt_title_stmt->execute([$doubt_id]);
    $doubt_title = $doubt_title_stmt->fetchColumn() ?: 'uma dúvida';
    
    // Get author name
    $author_name_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
    $author_name_stmt->execute([$_SESSION['user_id']]);
    $author_name = $author_name_stmt->fetchColumn() ?: 'Membro KALIYE';
    
    // Notify comment author if not the doubt owner
    if ($comment_info && $comment_info['user_id'] != $_SESSION['user_id']) {
        try {
            $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'best_comment', ?)");
            $notif->execute([
                $comment_info['user_id'],
                $_SESSION['user_id'],
                '🎉 Parabéns! Seu comentário foi selecionado',
                'Seu comentário em \"' . mb_substr($doubt_title, 0, 40) . '...\" foi marcado como melhor por ' . $author_name . '. Continue contribuindo para a comunidade!',
                'paginas/explorar/doubts.php?doubt_id=' . $doubt_id
            ]);
            error_log('Best comment notification sent to user ' . $comment_info['user_id']);
        } catch (Throwable $e) {
            error_log('Best comment notification error: ' . $e->getMessage());
        }
    }

    error_log('mark_comment_helpful: SUCCESS - Doubt ' . $doubt_id . ' marked as answered');
    echo json_encode(['success' => true, 'message' => 'Comentário marcado como melhor! ✅']);

} catch (PDOException $e) {
    error_log('mark_comment_helpful error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao marcar comentário.']);
}

