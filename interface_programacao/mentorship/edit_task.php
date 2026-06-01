<?php
/**
 * interface_programacao/mentorship/edit_task.php
 * Permite ao mentor editar uma tarefa
 */
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilizador não autenticado']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores podem editar tarefas.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;
$task_name = trim((string)($_POST['task_name'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if (!$task_id || !$task_name) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Ensure expires_at column exists
    $db->exec("ALTER TABLE mentorship_tasks ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");

    // Verificar se a tarefa pertence ao mentor
    $check = $db->prepare("SELECT task_id FROM mentorship_tasks WHERE task_id = ? AND mentor_id = ?");
    $check->execute([$task_id, $mentor_id]);
    
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Tarefa não encontrada ou sem permissões']);
        exit;
    }

    // Atualizar tarefa
    $stmt = $db->prepare("
        UPDATE mentorship_tasks
        SET task_name = ?, description = ?, updated_at = NOW()
        WHERE task_id = ? AND mentor_id = ?
    ");
    
    if (!$stmt->execute([$task_name, $description, $task_id, $mentor_id])) {
        throw new Exception('Falha ao atualizar tarefa');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Tarefa atualizada com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
