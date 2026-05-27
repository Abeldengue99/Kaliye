<?php
// servicos/mentorship/get_mentee_tasks.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'mentee'; // 'mentee' or 'mentor'

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // 1. Ensure Table Exists
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_tasks (
        task_id SERIAL PRIMARY KEY,
        mentor_id INT NOT NULL,
        mentee_id INT NOT NULL,
        task_name VARCHAR(255) NOT NULL,
        description TEXT,
        deadline TIMESTAMP NULL,
        status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (mentee_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    // 2. Query Tasks based on View
    if ($view === 'mentor') {
        // I am the Mentor, fetch tasks I assigned
        $query = "SELECT mt.*, 
                         m.full_name as mentor_name, 
                         s.full_name as student_name 
                  FROM mentorship_tasks mt
                  JOIN users m ON mt.mentor_id = m.user_id
                  JOIN users s ON mt.mentee_id = s.user_id
                  WHERE mt.mentor_id = ? 
                  ORDER BY mt.deadline ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    } else {
        // I am the Student, fetch tasks assigned to me
        $query = "SELECT mt.*, 
                         m.full_name as mentor_name, 
                         s.full_name as student_name 
                  FROM mentorship_tasks mt
                  JOIN users m ON mt.mentor_id = m.user_id
                  JOIN users s ON mt.mentee_id = s.user_id
                  WHERE mt.mentee_id = ? 
                  ORDER BY mt.deadline ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    }

    $tasks = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'tasks' => $tasks]);

} catch (PDOException $e) {
     error_log("Get Tasks Error: " . $e->getMessage());
     echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>

