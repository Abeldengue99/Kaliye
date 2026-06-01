<?php
// servicos/social/edit_doubt.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

error_log('edit_doubt: START - User ID: ' . ($_SESSION['user_id'] ?? 'NOT_SET'));

if (!isset($_SESSION['user_id'])) {
    error_log('edit_doubt: FAIL - User not authenticated');
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

// Validate CSRF token
$token = getRequestCSRFToken();
if (!verifyCSRFToken($token)) {
    error_log('edit_doubt: FAIL - CSRF validation failed');
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Pedido bloqueado por segurança. Atualize a página e tente novamente.']);
    exit();
}

$doubt_id = $_POST['doubt_id'] ?? null;
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;
$category = $_POST['category'] ?? null;
$tags = $_POST['tags'] ?? null; // Optional
$status = $_POST['status'] ?? null; // Optional - new parameter

// If only status is being updated (for marking as answered), that's valid
if (!$doubt_id) {
    error_log('edit_doubt: FAIL - Missing doubt_id');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

// If doing a full update, require all fields except status/tags
if (!$status && (!$title || !$description || !$category)) {
    error_log('edit_doubt: FAIL - Missing required fields for full update');
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

error_log('edit_doubt: doubt_id=' . $doubt_id . ', status=' . ($status ?? 'null'));

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verificar se a dúvida pertence ao usuário ou se é admin
    $check_query = "SELECT user_id FROM doubts WHERE doubt_id = ?";
    $stmt = $db->prepare($check_query);
    $stmt->execute([$doubt_id]);
    $doubt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doubt) {
        error_log('edit_doubt: FAIL - Doubt not found: ' . $doubt_id);
        echo json_encode(['success' => false, 'message' => 'Dúvida não encontrada']);
        exit();
    }

    if ($doubt['user_id'] != $_SESSION['user_id'] && $_SESSION['user_type'] !== 'admin') {
        error_log('edit_doubt: FAIL - Access denied. Owner: ' . $doubt['user_id'] . ', Current: ' . $_SESSION['user_id']);
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit();
    }

    // Build dynamic query based on what's being updated
    if ($status) {
        // Simple status update
        error_log('edit_doubt: Updating status to ' . $status);
        $query = "UPDATE doubts SET status = :status, updated_at = NOW() WHERE doubt_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':status', $status);
        $stmt->bindParam(':id', $doubt_id);
    } else {
        // Full content update
        error_log('edit_doubt: Updating full content');
        $query = "UPDATE doubts SET title = :title, description = :desc, category = :cat, tags = :tags, updated_at = NOW() WHERE doubt_id = :id";
        $stmt = $db->prepare($query);
        $stmt->bindParam(':title', $title);
        $stmt->bindParam(':desc', $description);
        $stmt->bindParam(':cat', $category);
        $stmt->bindParam(':tags', $tags);
        $stmt->bindParam(':id', $doubt_id);
    }

    if ($stmt->execute()) {
        error_log('edit_doubt: SUCCESS - Doubt ' . $doubt_id . ' updated');
        echo json_encode(['success' => true]);
    } else {
        error_log('edit_doubt: FAIL - Update failed: ' . implode(', ', $stmt->errorInfo()));
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }

} catch (PDOException $e) {
    error_log('edit_doubt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao editar dúvida.']);
}

    echo json_encode(['success' => false, 'message' => 'Erro interno ao atualizar dúvida.']);
}

