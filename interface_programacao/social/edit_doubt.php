<?php
// servicos/social/edit_doubt.php
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
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? null;
$category = $_POST['category'] ?? null;
$tags = $_POST['tags'] ?? null; // Optional

if (!$doubt_id || !$title || !$description || !$category) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

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
        echo json_encode(['success' => false, 'message' => 'Dúvida não encontrada']);
        exit();
    }

    if ($doubt['user_id'] != $_SESSION['user_id'] && $_SESSION['user_type'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit();
    }

    $query = "UPDATE doubts SET title = :title, description = :desc, category = :cat, tags = :tags, updated_at = NOW() WHERE doubt_id = :id";
    $stmt = $db->prepare($query);
    $stmt->bindParam(':title', $title);
    $stmt->bindParam(':desc', $description);
    $stmt->bindParam(':cat', $category);
    $stmt->bindParam(':tags', $tags);
    $stmt->bindParam(':id', $doubt_id);

    if ($stmt->execute()) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao atualizar']);
    }

} catch (PDOException $e) {
    error_log('edit_doubt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao atualizar duvida.']);
}

