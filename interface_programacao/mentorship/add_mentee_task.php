<?php
// servicos/mentorship/add_mentee_task.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem atribuir tarefas.']);
    exit;
}

$mentor_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

// Validate Input
if (empty($_POST['student_id']) || empty($_POST['task_name'])) {
    echo json_encode(['success' => false, 'error' => 'Missing required fields (student or task name)']);
    exit;
}

$mentee_id = $_POST['student_id'];
$task_name = $_POST['task_name'];
$description = $_POST['description'] ?? '';
$deadline = !empty($_POST['deadline']) ? $_POST['deadline'] : null;

try {
    // 1. Ensure Table Exists (Redundant but safe)
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

    $allowed = $db->prepare("
        SELECT 1
        FROM users u
        WHERE u.user_id = ?
          AND u.user_type <> 'investor'
          AND EXISTS (
              SELECT 1 FROM mentorship_slots ms
              WHERE ms.mentor_id = ? AND ms.participant_id = u.user_id
              UNION
              SELECT 1 FROM mentorship_tasks mt
              WHERE mt.mentor_id = ? AND mt.mentee_id = u.user_id
          )
        LIMIT 1
    ");
    $allowed->execute([$mentee_id, $mentor_id, $mentor_id]);
    if (!$allowed->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'Este estudante ainda não esta associado a sua mentoria.']);
        exit;
    }

    // 2. Insert Task
    $query = "INSERT INTO mentorship_tasks (mentor_id, mentee_id, task_name, description, deadline, status) 
              VALUES (?, ?, ?, ?, ?, 'pending')";
    $stmt = $db->prepare($query);
    $stmt->execute([$mentor_id, $mentee_id, $task_name, $description, $deadline]);

    echo json_encode(['success' => true, 'message' => 'Task assigned successfully', 'task_id' => $db->lastInsertId()]);

} catch (PDOException $e) {
    error_log("Add Task Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>

