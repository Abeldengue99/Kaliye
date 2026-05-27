<?php
// servicos/mentorship/get_mentor_resources.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'mentee';
$requested_mentor_id = $_GET['mentor_id'] ?? null;
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // 1. Ensure Table
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

    // 2. Query
    if ($view === 'mentor') {
        $query = "SELECT mr.*, u.full_name as author_name 
                  FROM mentorship_resources mr 
                  JOIN users u ON mr.mentor_id = u.user_id
                  WHERE mr.mentor_id = ? 
                  ORDER BY mr.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    } else {
        // Mentee View: See resources from my mentors
        $query = "SELECT mr.*, u.full_name as author_name 
                  FROM mentorship_resources mr 
                  JOIN users u ON mr.mentor_id = u.user_id
                  WHERE mr.mentor_id IN (SELECT DISTINCT mentor_id FROM mentorship_tasks WHERE mentee_id = ?) 
                  ORDER BY mr.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    }

    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'resources' => $resources]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>

