<?php
// servicos/social/post_doubt_comment.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

// DEBUG: Log incoming request
error_log('post_doubt_comment: START - User ID: ' . ($_SESSION['user_id'] ?? 'NOT_SET'));

if (!isset($_SESSION['user_id'])) {
    error_log('post_doubt_comment: FAIL - User not authenticated');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Validate CSRF token
$token = getRequestCSRFToken();
error_log('post_doubt_comment: CSRF Token received: ' . substr($token, 0, 10) . '... (session: ' . substr($_SESSION['csrf_token'] ?? '', 0, 10) . '...)');

if (!verifyCSRFToken($token)) {
    error_log('post_doubt_comment: FAIL - CSRF validation failed');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Pedido bloqueado por segurança. Atualize a página e tente novamente.']);
    exit();
}

$doubt_id = $_POST['doubt_id'] ?? null;
$content = $_POST['content'] ?? null;
$parent_id = !empty($_POST['parent_id']) ? $_POST['parent_id'] : null;

error_log('post_doubt_comment: doubt_id=' . $doubt_id . ', content_length=' . strlen($content ?? '') . ', parent_id=' . $parent_id);

if (!$doubt_id || !$content) {
    error_log('post_doubt_comment: FAIL - Missing doubt_id or content');
    echo json_encode(['success' => false, 'message' => 'Conteúdo obrigatório']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verify that the doubt exists and is open
    $checkDoubt = $db->prepare("SELECT doubt_id, status FROM doubts WHERE doubt_id = ?");
    $checkDoubt->execute([$doubt_id]);
    $doubt = $checkDoubt->fetch();
    
    if (!$doubt) {
        error_log('post_doubt_comment: FAIL - Doubt not found: ' . $doubt_id);
        echo json_encode(['success' => false, 'message' => 'Dúvida não encontrada']);
        exit();
    }

    if ($doubt['status'] !== 'open') {
        error_log('post_doubt_comment: FAIL - Doubt not open, status: ' . $doubt['status']);
        echo json_encode(['success' => false, 'message' => 'Esta dúvida já foi respondida e não aceita mais comentários.']);
        exit();
    }

    $query = "INSERT INTO doubt_comments (doubt_id, user_id, parent_id, content, created_at) VALUES (:did, :uid, :pid, :content, NOW())";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':did', $doubt_id, PDO::PARAM_INT);
    $stmt->bindParam(':uid', $_SESSION['user_id'], PDO::PARAM_INT);
    $stmt->bindParam(':pid', $parent_id, PDO::PARAM_INT);
    $stmt->bindParam(':content', $content, PDO::PARAM_STR);

    if ($stmt->execute()) {
        error_log('post_doubt_comment: SUCCESS - Comment inserted for doubt ' . $doubt_id);
        
        $comment_id = $db->lastInsertId();
        
        // Get doubt info and owner
        $doubt_stmt = $db->prepare("SELECT user_id, title FROM doubts WHERE doubt_id = ?");
        $doubt_stmt->execute([$doubt_id]);
        $doubt_info = $doubt_stmt->fetch();
        
        // Get current user name
        $user_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $user_stmt->execute([$_SESSION['user_id']]);
        $current_user_name = $user_stmt->fetchColumn() ?: 'Membro KALIYE';
        
        // Notify doubt owner if not the commentator
        if ($doubt_info && $doubt_info['user_id'] != $_SESSION['user_id']) {
            try {
                $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'comment', ?)");
                $notif->execute([
                    $doubt_info['user_id'],
                    $_SESSION['user_id'],
                    'Novo comentário em: ' . mb_substr($doubt_info['title'], 0, 50),
                    $current_user_name . ' comentou na sua dúvida',
                    'paginas/explorar/doubts.php?doubt_id=' . $doubt_id
                ]);
                error_log('Notification sent to user ' . $doubt_info['user_id']);
            } catch (Throwable $e) {
                error_log('Notification error: ' . $e->getMessage());
            }
        }
        
        echo json_encode([
            'success' => true,
            'message' => 'Comentário deixado com sucesso! 🎉',
            'comment_id' => $comment_id
        ]);
    } else {
        error_log('post_doubt_comment: FAIL - Insert failed: ' . implode(', ', $stmt->errorInfo()));
        echo json_encode(['success' => false, 'message' => 'Erro ao comentar']);
    }

} catch (PDOException $e) {
    error_log('post_doubt_comment error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao publicar comentário.']);
}

