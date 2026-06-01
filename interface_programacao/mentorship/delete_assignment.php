<?php
/**
 * interface_programacao/mentorship/delete_assignment.php
 * Permite ao mentor deletar uma atribuição
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
    echo json_encode(['success' => false, 'error' => 'Apenas mentores podem deletar atribuições.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$assignment_id = isset($_POST['assignment_id']) ? (int)$_POST['assignment_id'] : 0;

if (!$assignment_id) {
    echo json_encode(['success' => false, 'error' => 'Atribuição não especificada']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Ensure expires_at column exists
    $db->exec("ALTER TABLE mentor_assignments ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");

    // Verificar se a atribuição pertence ao mentor
    $check = $db->prepare("SELECT assignment_id FROM mentor_assignments WHERE assignment_id = ? AND mentor_id = ?");
    $check->execute([$assignment_id, $mentor_id]);
    
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Atribuição não encontrada ou sem permissões']);
        exit;
    }

    // Deletar atribuição
    $stmt = $db->prepare("DELETE FROM mentor_assignments WHERE assignment_id = ? AND mentor_id = ?");
    
    if (!$stmt->execute([$assignment_id, $mentor_id])) {
        throw new Exception('Falha ao deletar atribuição');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Atribuição eliminada com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
