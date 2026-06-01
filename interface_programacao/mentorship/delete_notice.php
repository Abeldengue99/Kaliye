<?php
/**
 * interface_programacao/mentorship/delete_notice.php
 * Permite ao mentor deletar um aviso
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
    echo json_encode(['success' => false, 'error' => 'Apenas mentores podem deletar avisos.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$notice_id = isset($_POST['notice_id']) ? (int)$_POST['notice_id'] : 0;

if (!$notice_id) {
    echo json_encode(['success' => false, 'error' => 'Aviso não especificado']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Verificar se o aviso pertence ao mentor
    $check = $db->prepare("SELECT notice_id FROM mentorship_notices WHERE notice_id = ? AND mentor_id = ?");
    $check->execute([$notice_id, $mentor_id]);
    
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Aviso não encontrado ou sem permissões']);
        exit;
    }

    // Deletar aviso
    $stmt = $db->prepare("DELETE FROM mentorship_notices WHERE notice_id = ? AND mentor_id = ?");
    
    if (!$stmt->execute([$notice_id, $mentor_id])) {
        throw new Exception('Falha ao deletar aviso');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Aviso eliminado com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
