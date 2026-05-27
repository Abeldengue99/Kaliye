<?php
// servicos/mentorship/add_mentor_resource.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem enviar materiais.']);
    exit;
}

$mentor_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? null;
$description = $_POST['description'] ?? '';
$type = $_POST['resource_type'] ?? 'file';

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

$file_url = $_POST['link_url'] ?? '';

if ($type === 'link' && !filter_var($file_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Informe um link valido para o material.']);
    exit;
}

if ($type === 'file' && isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK) {
    $stored = Security::storeUploadedFile(
        $_FILES['file'],
        __DIR__ . '/../../carregamentos/resources/' . $mentor_id,
        'carregamentos/resources/' . $mentor_id,
        [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
        ],
        20 * 1024 * 1024,
        'resource'
    );

    if (!$stored['ok']) {
        echo json_encode(['success' => false, 'error' => $stored['error']]);
        exit;
    }

    $file_url = $stored['path'];
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_resources (
        resource_id SERIAL PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        resource_type VARCHAR(20) DEFAULT 'file' CHECK (resource_type IN ('file', 'link')),
        file_url VARCHAR(500),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    $query = "INSERT INTO mentorship_resources (mentor_id, title, description, resource_type, file_url) VALUES (?, ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$mentor_id, $title, $description, $type, $file_url]);
    $resource_id = $db->lastInsertId();

    // Distribution Logic: Handle selected students
    $mentee_ids = $_POST['mentee_ids'] ?? [];
    if (!empty($mentee_ids)) {
        // Create table for visibility if not exists
        $db->exec("CREATE TABLE IF NOT EXISTS mentorship_resource_visibility (
            id SERIAL PRIMARY KEY,
            resource_id INT NOT NULL,
            user_id INT NOT NULL,
            FOREIGN KEY (resource_id) REFERENCES mentorship_resources(resource_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");

        $dist_stmt = $db->prepare("INSERT INTO mentorship_resource_visibility (resource_id, user_id) VALUES (?, ?)");
        foreach ($mentee_ids as $sid) {
            $dist_stmt->execute([$resource_id, $sid]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Material enviado e partilhado com sucesso!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>

