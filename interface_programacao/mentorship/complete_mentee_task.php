<?php
// servicos/mentorship/complete_mentee_task.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$task_id = $_POST['task_id'] ?? null;

if (!$task_id) {
    echo json_encode(['success' => false, 'error' => 'Missing task ID']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // 1. Check Ownership (Task must belong to user as mentee or mentor)
    $query = "SELECT * FROM mentorship_tasks WHERE task_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$task_id]);
    $task = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$task) {
        echo json_encode(['success' => false, 'error' => 'Task not found']);
        exit;
    }

    if ($task['mentee_id'] != $user_id && $task['mentor_id'] != $user_id) {
        echo json_encode(['success' => false, 'error' => 'Unauthorized']);
        exit;
    }

    // 2. Mark as Completed
    $update = "UPDATE mentorship_tasks SET status = 'completed' WHERE task_id = ?";
    $stmt = $db->prepare($update);
    $stmt->execute([$task_id]);

    echo json_encode(['success' => true, 'message' => 'Task marked as completed']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error']);
}
?>

