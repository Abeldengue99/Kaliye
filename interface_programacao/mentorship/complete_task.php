<?php
// servicos/mentorship/complete_task.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$task_id = $_GET['id'] ?? null;
$user_id = $_SESSION['user_id'];

if (!$task_id) {
    echo json_encode(['success' => false, 'message' => 'Task ID required']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Check if task belongs to user (as mentee)
    $stmt = $db->prepare("UPDATE mentorship_tasks SET status = 'completed' WHERE task_id = ? AND mentee_id = ?");
    $stmt->execute([$task_id, $user_id]);

    if ($stmt->rowCount() > 0) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Task not found or already completed']);
    }

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}

