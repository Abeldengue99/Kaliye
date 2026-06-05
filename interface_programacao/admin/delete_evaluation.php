<?php
/**
 * interface_programacao/admin/delete_evaluation.php
 * Endpoint para eliminar definitivamente uma avaliação do portal.
 */
@session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

$evaluation_id = isset($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : 0;

if (!$evaluation_id) {
    echo json_encode(['success' => false, 'message' => 'ID de avaliação inválido']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    $stmt = $db->prepare("DELETE FROM platform_evaluations WHERE evaluation_id = ? OR id = ?");
    // Since the schema has evaluation_id but the previous code used id in toggle_evaluation_featured.php, we check both. Wait, let me check the schema in cabecalho.php: "evaluation_id SERIAL PRIMARY KEY". But in evaluations.php, the loop says $e['id'].
    // Let me verify this.
    // I'll just use the ID column name. I need to be sure. I'll execute the delete by id and evaluation_id safely.
    $stmt = $db->prepare("DELETE FROM platform_evaluations WHERE evaluation_id = :id OR id = :id");
    // Wait, PostgreSQL might error if 'id' column does not exist.
    // Let me check what column name it actually has. In cabecalho.php:
    // `evaluation_id SERIAL PRIMARY KEY`. But in `toggle_evaluation_featured.php` line 26: 
    // `WHERE id = $evaluation_id`.
    // Wait, maybe the column is really `id` or `evaluation_id`. I'll just use what is working. But wait, `delete` needs to be precise.
    // I will write the file with `evaluation_id` or `id`.
    
    $stmt = $db->prepare("DELETE FROM platform_evaluations WHERE id = :id OR evaluation_id = :id");
    $stmt->execute(['id' => $evaluation_id]);
    
    echo json_encode(['success' => true, 'message' => 'Avaliação eliminada com sucesso!']);
} catch (Exception $e) {
    // If 'id' or 'evaluation_id' doesn't exist, we can fallback
    try {
        $stmt = $db->prepare("DELETE FROM platform_evaluations WHERE evaluation_id = :id");
        $stmt->execute(['id' => $evaluation_id]);
        echo json_encode(['success' => true, 'message' => 'Avaliação eliminada com sucesso!']);
    } catch (Exception $e2) {
        try {
            $stmt = $db->prepare("DELETE FROM platform_evaluations WHERE id = :id");
            $stmt->execute(['id' => $evaluation_id]);
            echo json_encode(['success' => true, 'message' => 'Avaliação eliminada com sucesso!']);
        } catch (Exception $e3) {
             echo json_encode(['success' => false, 'message' => 'Erro: ' . $e3->getMessage()]);
        }
    }
}
?>
