<?php
/**
 * interface_programacao/mentorship/edit_mentor_resource.php
 * Permite ao mentor editar um recurso (material) compartilhado
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
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem editar materiais.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));

if (!$resource_id || !$title) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Verificar se o recurso pertence ao mentor
    $check = $db->prepare("SELECT resource_id FROM mentorship_resources WHERE resource_id = ? AND mentor_id = ?");
    $check->execute([$resource_id, $mentor_id]);
    
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Recurso não encontrado ou sem permissões']);
        exit;
    }

    // Atualizar recurso
    $stmt = $db->prepare("
        UPDATE mentorship_resources
        SET title = ?, description = ?
        WHERE resource_id = ? AND mentor_id = ?
    ");
    
    $stmt->execute([
        mb_substr($title, 0, 255),
        $description,
        $resource_id,
        $mentor_id
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Material atualizado com sucesso.']);

} catch (Exception $e) {
    $db->rollBack();
    error_log('[EDIT_RESOURCE_ERROR] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao atualizar o material.']);
}
?>
