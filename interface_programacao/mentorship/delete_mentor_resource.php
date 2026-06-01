<?php
/**
 * interface_programacao/mentorship/delete_mentor_resource.php
 * Permite ao mentor apagar um recurso (material) compartilhado
 */
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Utilizador não autenticado']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem apagar materiais.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$resource_id = isset($_POST['resource_id']) ? (int)$_POST['resource_id'] : 0;

if (!$resource_id) {
    echo json_encode(['success' => false, 'error' => 'ID do recurso inválido']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Verificar se o recurso pertence ao mentor
    $check = $db->prepare("SELECT resource_id, file_url FROM mentorship_resources WHERE resource_id = ? AND mentor_id = ?");
    $check->execute([$resource_id, $mentor_id]);
    $resource = $check->fetch();
    
    if (!$resource) {
        echo json_encode(['success' => false, 'error' => 'Recurso não encontrado ou sem permissões']);
        exit;
    }

    // Deletar ficheiro se for ficheiro local
    $file_url = $resource['file_url'];
    if ($file_url && strpos($file_url, 'carregamentos/') === 0) {
        $file_path = __DIR__ . '/../../' . $file_url;
        if (file_exists($file_path)) {
            @unlink($file_path);
        }
    }

    // Deletar visibilidade
    $db->prepare("DELETE FROM mentorship_resource_visibility WHERE resource_id = ?")->execute([$resource_id]);

    // Deletar recurso
    $stmt = $db->prepare("DELETE FROM mentorship_resources WHERE resource_id = ? AND mentor_id = ?");
    $stmt->execute([$resource_id, $mentor_id]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Material apagado com sucesso.']);

} catch (Exception $e) {
    $db->rollBack();
    error_log('[DELETE_RESOURCE_ERROR] ' . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro ao apagar o material.']);
}
?>
