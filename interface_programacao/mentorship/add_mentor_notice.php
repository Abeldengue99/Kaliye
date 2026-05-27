<?php
// servicos/mentorship/add_mentor_notice.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem enviar avisos.']);
    exit;
}

$mentor_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? null;
$content = $_POST['message'] ?? '';
$importance = $_POST['importance'] ?? 'normal';

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_notices (
        notice_id SERIAL PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        importance VARCHAR(10) DEFAULT 'normal' CHECK (importance IN ('normal', 'high')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    $query = "INSERT INTO mentorship_notices (mentor_id, title, content, importance) VALUES (?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([$mentor_id, $title, $content, $importance]);
    $notice_id = $db->lastInsertId();

    // Handle selected students (Visibility)
    $mentee_ids = $_POST['mentee_ids'] ?? [];
    if (!empty($mentee_ids)) {
        $db->exec("CREATE TABLE IF NOT EXISTS mentorship_notice_visibility (
            id SERIAL PRIMARY KEY,
            notice_id INT NOT NULL,
            user_id INT NOT NULL,
            FOREIGN KEY (notice_id) REFERENCES mentorship_notices(notice_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");

        $dist_stmt = $db->prepare("INSERT INTO mentorship_notice_visibility (notice_id, user_id) VALUES (?, ?)");
        foreach ($mentee_ids as $sid) {
            $dist_stmt->execute([$notice_id, $sid]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Notice posted successfully']);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>

