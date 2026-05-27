<?php
// interface_programacao/admin/mark_commission_paid.php
// API para marcar comissões como pagas (apenas admin)
header('Content-Type: application/json');
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Verificar se é admin
if (!isAdmin() || !hasPermission('finances')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit;
}

// Obter dados da requisição
$input = json_decode(file_get_contents('php://input'), true);
$commission_id = isset($input['commission_id']) ? (int)$input['commission_id'] : 0;
$notes = isset($input['notes']) ? trim($input['notes']) : '';

// Validar
if ($commission_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de comissão inválido']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();
    
    // Verificar se a comissão existe e está pendente
    $check_query = "SELECT * FROM commission_history WHERE commission_id = :id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':id' => $commission_id]);
    $commission = $check_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$commission) {
        throw new Exception("Comissão não encontrada");
    }
    
    if ($commission['status'] == 'paid') {
        throw new Exception("Esta comissão já foi marcada como paga");
    }
    
    // Marcar como paga
    $update_query = "UPDATE commission_history 
                     SET status = 'paid', 
                         paid_at = NOW(),
                         notes = :notes
                     WHERE commission_id = :id";
    
    $update_stmt = $db->prepare($update_query);
    $update_stmt->execute([
        ':id' => $commission_id,
        ':notes' => $notes
    ]);
    
    $db->commit();
    
    echo json_encode([
        'success' => true,
        'message' => 'Comissão marcada como paga com sucesso',
        'commission_id' => $commission_id,
        'amount' => number_format($commission['commission_amount'], 2, ',', '.') . ' AOA'
    ]);
    
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    error_log("Erro em mark_commission_paid.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

