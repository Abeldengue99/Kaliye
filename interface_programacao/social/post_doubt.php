<?php
// servicos/social/post_doubt.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado']);
    exit();
}
requireValidCSRFTokenJson();

$user_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? '';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? 'other';
$tags = $_POST['tags'] ?? '';

if (empty($title) || empty($description)) {
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios em falta']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $media_url = null;
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $stored = Security::storeUploadedFile(
            $_FILES['image'],
            __DIR__ . '/../../carregamentos/doubts',
            'carregamentos/doubts',
            [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/webp' => 'webp',
            ],
            8 * 1024 * 1024,
            'doubt'
        );

        if (!$stored['ok']) {
            echo json_encode(['success' => false, 'message' => $stored['error']]);
            exit();
        }

        $media_url = $stored['path'];
    }

    $query = "INSERT INTO doubts (user_id, title, description, category, tags, media_url, created_at) 
              VALUES (?, ?, ?, ?, ?, ?, NOW())";
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id, $title, $description, $category, $tags, $media_url]);

    echo json_encode(['success' => true, 'message' => 'Dúvida publicada com sucesso']);

} catch (PDOException $e) {
    error_log('post_doubt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao publicar duvida.']);
}

