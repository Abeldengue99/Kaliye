<?php
/**
 * interface_programacao/mentorship/delete_task.php
 * Permite ao mentor deletar uma tarefa
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
    echo json_encode(['success' => false, 'error' => 'Apenas mentores podem deletar tarefas.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$task_id = isset($_POST['task_id']) ? (int)$_POST['task_id'] : 0;

if (!$task_id) {
    echo json_encode(['success' => false, 'error' => 'Tarefa não especificada']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Verificar se a tarefa pertence ao mentor
    $check = $db->prepare("SELECT task_id FROM mentorship_tasks WHERE task_id = ? AND mentor_id = ?");
    $check->execute([$task_id, $mentor_id]);
    
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Tarefa não encontrada ou sem permissões']);
        exit;
    }

    // Deletar tarefa
    $stmt = $db->prepare("DELETE FROM mentorship_tasks WHERE task_id = ? AND mentor_id = ?");
    
    if (!$stmt->execute([$task_id, $mentor_id])) {
        throw new Exception('Falha ao deletar tarefa');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Tarefa eliminada com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
