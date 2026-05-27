<?php
// servicos/projects/invest_project.php
// Processa investimentos em projetos com cálculo automático de comissões
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Payments feature flag
$payments_config = require __DIR__ . '/../../configuracoes/pagamentos.php';
if (!isset($payments_config['payments_enabled']) || $payments_config['payments_enabled'] === false) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Funcionalidade de investimentos desativada nesta versão.']);
    exit;
}

// Verifico se o usuário está autenticado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

if (($_SESSION['user_type'] ?? '') !== 'investor') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Apenas investidores podem iniciar uma proposta de investimento.']);
    exit;
}
requireValidCSRFTokenJson();

if (($_SESSION['verification_status'] ?? 'unsubmitted') !== 'verified') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Valide o KYC antes de investir em projectos.']);
    exit;
}

// Obtenho os dados do investimento
$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) {
    $input = $_POST;
}
$project_id = isset($input['project_id']) ? (int)$input['project_id'] : 0;
$amount = isset($input['amount']) ? (float)$input['amount'] : 0;
$investor_id = $_SESSION['user_id'];

// Valido os dados
if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de projeto inválido']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor de investimento inválido']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Inicio transação para garantir integridade
    $db->beginTransaction();
    
    // Busco informações do projeto
    $project_query = "SELECT p.*, u.full_name as mentor_name 
                      FROM projects p 
                      LEFT JOIN users u ON p.assigned_mentor_id = u.user_id 
                      WHERE p.project_id = :project_id";
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([':project_id' => $project_id]);
    $project = $project_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        throw new Exception("Projeto não encontrado");
    }
    
    if ((int)($project['owner_id'] ?? 0) === (int)$investor_id) {
        throw new Exception("Nao e possivel investir no proprio projecto.");
    }

    $is_public = in_array($project['is_public'] ?? false, [true, 1, '1', 't'], true);
    if (!$is_public || ($project['approval_status'] ?? 'pending') !== 'approved') {
        throw new Exception("Este projecto ainda nao esta disponivel para investimento.");
    }

    $min_investment = (float)($project['minimum_investment'] ?? 0);
    $max_investment = isset($project['maximum_investment']) ? (float)$project['maximum_investment'] : 0;
    if ($min_investment > 0 && $amount < $min_investment) {
        throw new Exception("O valor esta abaixo do investimento minimo deste projecto.");
    }
    if ($max_investment > 0 && $amount > $max_investment) {
        throw new Exception("O valor excede o investimento maximo permitido para este projecto.");
    }

    // Calculo as comissões
    $aksanti_commission_rate = 20.00; // 20% padrão da Aksanti
    $aksanti_commission_amount = ($amount * $aksanti_commission_rate) / 100;
    
    // Verifico se o mentor tem direito a comissão
    $mentor_commission_rate = 0.00;
    $mentor_commission_amount = 0.00;
    $mentor_id = null;
    
    if ($project['mentor_eligible_for_commission'] == 1 && $project['assigned_mentor_id']) {
        $mentor_commission_rate = $project['mentor_commission_percentage'] ?? 5.00;
        $mentor_commission_amount = ($amount * $mentor_commission_rate) / 100;
        $mentor_id = $project['assigned_mentor_id'];
    }
    
    // Calculo o valor líquido que vai para o projeto
    $net_amount = $amount - $aksanti_commission_amount - $mentor_commission_amount;
    
    // Insiro o investimento
    $investment_query = "INSERT INTO project_investments 
                        (project_id, investor_id, amount, status, 
                         aksanti_commission_rate, aksanti_commission_amount,
                         mentor_commission_rate, mentor_commission_amount,
                         net_amount_to_project)
                        VALUES 
                        (:project_id, :investor_id, :amount, 'pending',
                         :aksanti_rate, :aksanti_amount,
                         :mentor_rate, :mentor_amount,
                         :net_amount)";
    
    $investment_stmt = $db->prepare($investment_query);
    $investment_stmt->execute([
        ':project_id' => $project_id,
        ':investor_id' => $investor_id,
        ':amount' => $amount,
        ':aksanti_rate' => $aksanti_commission_rate,
        ':aksanti_amount' => $aksanti_commission_amount,
        ':mentor_rate' => $mentor_commission_rate,
        ':mentor_amount' => $mentor_commission_amount,
        ':net_amount' => $net_amount
    ]);
    
    $investment_id = $db->lastInsertId();
    $payment_reference = str_pad((string)$investment_id, 9, '0', STR_PAD_LEFT);
    
    // Registro as comissões no histórico
    // 1. Comissão da Aksanti
    $db->prepare("INSERT INTO commission_history 
                  (investment_id, project_id, mentor_id, commission_type, 
                   commission_rate, commission_amount, investment_amount, status)
                  VALUES (?, ?, NULL, 'aksanti', ?, ?, ?, 'pending')")
       ->execute([$investment_id, $project_id, $aksanti_commission_rate, 
                  $aksanti_commission_amount, $amount]);
    
    // 2. Comissão do Mentor (se aplicável)
    if ($mentor_commission_amount > 0 && $mentor_id) {
        $db->prepare("INSERT INTO commission_history 
                      (investment_id, project_id, mentor_id, commission_type, 
                       commission_rate, commission_amount, investment_amount, status)
                      VALUES (?, ?, ?, 'mentor', ?, ?, ?, 'pending')")
           ->execute([$investment_id, $project_id, $mentor_id, $mentor_commission_rate, 
                      $mentor_commission_amount, $amount]);
    }
    
    // Confirmo a transação
    $db->commit();
    
    // Retorno sucesso com detalhes
    echo json_encode([
        'success' => true,
        'message' => 'Investimento registrado com sucesso',
        'investment_id' => $investment_id,
        'reference' => $payment_reference,
        'formatted_amount' => number_format($amount, 2, ',', '.') . ' AOA',
        'breakdown' => [
            'total_invested' => number_format($amount, 2, ',', '.'),
            'aksanti_commission' => number_format($aksanti_commission_amount, 2, ',', '.') . ' AOA (' . $aksanti_commission_rate . '%)',
            'mentor_commission' => number_format($mentor_commission_amount, 2, ',', '.') . ' AOA (' . $mentor_commission_rate . '%)',
            'net_to_project' => number_format($net_amount, 2, ',', '.') . ' AOA'
        ]
    ]);
    
} catch (PDOException $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    error_log("Erro em invest_project.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao registar investimento. Tente novamente.']);
} catch (Exception $e) {
    // Reverto a transação em caso de erro
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}

