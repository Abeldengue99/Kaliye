<?php
/**
 * interface_programacao/mentorship/edit_notice.php
 * Permite ao mentor editar um aviso
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
    echo json_encode(['success' => false, 'error' => 'Apenas mentores podem editar avisos.']);
    exit;
}

$mentor_id = (int)$_SESSION['user_id'];
$notice_id = isset($_POST['notice_id']) ? (int)$_POST['notice_id'] : 0;
$content = trim((string)($_POST['content'] ?? ''));

if (!$notice_id || !$content) {
    echo json_encode(['success' => false, 'error' => 'Dados inválidos']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Ensure expires_at column exists
    $db->exec("ALTER TABLE mentorship_notices ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");

    // Verificar se o aviso pertence ao mentor
    $check = $db->prepare("SELECT notice_id FROM mentorship_notices WHERE notice_id = ? AND mentor_id = ?");
    $check->execute([$notice_id, $mentor_id]);
    
    if (!$check->fetch()) {
        echo json_encode(['success' => false, 'error' => 'Aviso não encontrado ou sem permissões']);
        exit;
    }

    // Atualizar aviso
    $stmt = $db->prepare("
        UPDATE mentorship_notices
        SET content = ?, updated_at = NOW()
        WHERE notice_id = ? AND mentor_id = ?
    ");
    
    if (!$stmt->execute([$content, $notice_id, $mentor_id])) {
        throw new Exception('Falha ao atualizar aviso');
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Aviso atualizado com sucesso']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
?>
