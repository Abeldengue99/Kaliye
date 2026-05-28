<?php
// servicos/projects/invest_project.php
// Phase 1: Processa candidaturas de investimento (sem pagamento digital)
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

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

// Novos campos da candidatura
$investor_motivation = trim($input['investor_motivation'] ?? '');
$investor_experience = trim($input['investor_experience'] ?? '');
$investor_linkedin = trim($input['investor_linkedin'] ?? '');
$investment_type = trim($input['investment_type'] ?? 'equity');
$terms = trim($input['terms'] ?? '');

// Valido os dados
if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de projeto inválido']);
    exit;
}

if ($amount <= 0) {
    echo json_encode(['success' => false, 'message' => 'Valor de investimento inválido']);
    exit;
}

if (empty($investor_motivation)) {
    echo json_encode(['success' => false, 'message' => 'Deve explicar a sua motivação para investir neste projecto.']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Primeiro, garantir que as colunas opcionais existem (FORA DA TRANSAÇÃO para evitar abortos no PostgreSQL)
    try {
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT 'AOA'");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investment_type VARCHAR(50) DEFAULT 'equity'");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS terms TEXT DEFAULT NULL");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS aksanti_commission_rate DECIMAL(5,2) DEFAULT 0");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS aksanti_commission_amount DECIMAL(15,2) DEFAULT 0");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS mentor_commission_rate DECIMAL(5,2) DEFAULT 0");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS mentor_commission_amount DECIMAL(15,2) DEFAULT 0");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS net_amount_to_project DECIMAL(15,2) DEFAULT 0");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investor_motivation TEXT DEFAULT NULL");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investor_experience VARCHAR(500) DEFAULT NULL");
        $db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investor_linkedin VARCHAR(500) DEFAULT NULL");
    } catch (PDOException $alterErr) {
        error_log("ALTER TABLE invest: " . $alterErr->getMessage());
    }
    
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
        throw new Exception("Não é possível investir no próprio projecto.");
    }

    $is_public = filter_var($project['is_public'] ?? false, FILTER_VALIDATE_BOOLEAN);
    $approval = $project['approval_status'] ?? 'pending';
    if (!$is_public || $approval !== 'approved') {
        error_log("Invest blocked: project_id=$project_id, is_public=" . var_export($project['is_public'], true) . ", approval_status=$approval");
        throw new Exception("Este projecto ainda não está disponível para investimento. (Status: $approval)");
    }

    // Verificar se o investidor já tem uma candidatura pendente neste projeto
    $existing = $db->prepare("SELECT investment_id FROM project_investments WHERE project_id = :pid AND investor_id = :iid AND status = 'pending'");
    $existing->execute([':pid' => $project_id, ':iid' => $investor_id]);
    if ($existing->fetch()) {
        throw new Exception("Já tem uma proposta de investimento pendente neste projecto. Aguarde a análise da equipa KALIYE.");
    }

    // Calculo as comissões (preservadas para quando a Phase 2 ativar pagamentos)
    $aksanti_commission_rate = 20.00;
    $aksanti_commission_amount = ($amount * $aksanti_commission_rate) / 100;
    
    $mentor_commission_rate = 0.00;
    $mentor_commission_amount = 0.00;
    $mentor_id = null;
    
    if (($project['mentor_eligible_for_commission'] ?? 0) == 1 && ($project['assigned_mentor_id'] ?? null)) {
        $mentor_commission_rate = $project['mentor_commission_percentage'] ?? 5.00;
        $mentor_commission_amount = ($amount * $mentor_commission_rate) / 100;
        $mentor_id = $project['assigned_mentor_id'];
    }
    
    $net_amount = $amount - $aksanti_commission_amount - $mentor_commission_amount;
    
    // Insiro a candidatura de investimento com status 'pending' (aguarda aprovação admin)
    $investment_query = "INSERT INTO project_investments 
                        (project_id, investor_id, amount, status, currency, investment_type, terms,
                         aksanti_commission_rate, aksanti_commission_amount,
                         mentor_commission_rate, mentor_commission_amount,
                         net_amount_to_project, investor_motivation)
                        VALUES 
                        (:project_id, :investor_id, :amount, 'pending', :currency, :investment_type, :terms,
                         :aksanti_rate, :aksanti_amount,
                         :mentor_rate, :mentor_amount,
                         :net_amount, :motivation)";
    
    $investment_stmt = $db->prepare($investment_query);
    $investment_stmt->execute([
        ':project_id' => $project_id,
        ':investor_id' => $investor_id,
        ':amount' => $amount,
        ':currency' => $input['currency'] ?? 'AOA',
        ':investment_type' => $investment_type,
        ':terms' => $terms,
        ':aksanti_rate' => $aksanti_commission_rate,
        ':aksanti_amount' => $aksanti_commission_amount,
        ':mentor_rate' => $mentor_commission_rate,
        ':mentor_amount' => $mentor_commission_amount,
        ':net_amount' => $net_amount,
        ':motivation' => $investor_motivation
    ]);
    
    $investment_id = $db->lastInsertId();

    // Guardar campos adicionais de experiência/linkedin (já inserimos a motivação no INSERT principal)
    try {
        $db->prepare("UPDATE project_investments SET investor_experience = :exp, investor_linkedin = :lin WHERE investment_id = :id")
           ->execute([':exp' => $investor_experience, ':lin' => $investor_linkedin, ':id' => $investment_id]);
    } catch (PDOException $colErr) {
        // Silencioso - os dados principais já foram salvos
        error_log("Update extra fields: " . $colErr->getMessage());
    }

    // Registar a comissão no histórico (para Phase 2)
    try {
        $db->prepare("INSERT INTO commission_history 
                      (investment_id, project_id, mentor_id, commission_type, 
                       commission_rate, commission_amount, investment_amount, status)
                      VALUES (?, ?, NULL, 'aksanti', ?, ?, ?, 'pending')")
           ->execute([$investment_id, $project_id, $aksanti_commission_rate, 
                      $aksanti_commission_amount, $amount]);
        
        if ($mentor_commission_amount > 0 && $mentor_id) {
            $db->prepare("INSERT INTO commission_history 
                          (investment_id, project_id, mentor_id, commission_type, 
                           commission_rate, commission_amount, investment_amount, status)
                          VALUES (?, ?, ?, 'mentor', ?, ?, ?, 'pending')")
               ->execute([$investment_id, $project_id, $mentor_id, $mentor_commission_rate, 
                          $mentor_commission_amount, $amount]);
        }
    } catch (PDOException $commErr) {
        // Silencioso - comissões são secundárias na Phase 1
        error_log("Commission history insert skipped: " . $commErr->getMessage());
    }
    
    // A notificação ao dono do projecto foi removida (a pedido do cliente), 
    // pois é a Administração (KALIYE) que gere todas as candidaturas na Fase 1.
    try {
        // Opcional: Notificar todos os admins (se houver um painel de alertas globais)
        // Por agora, o admin vê no dashboard Financeiro.
    } catch (PDOException $notifErr) {
        error_log("Notification error: " . $notifErr->getMessage());
    }
    
    // Retorno sucesso
    echo json_encode([
        'success' => true,
        'message' => 'A sua proposta de investimento foi submetida com sucesso! A equipa KALIYE irá analisar e entrar em contacto consigo.',
        'investment_id' => $investment_id
    ]);
    
} catch (PDOException $e) {
    error_log("Erro em invest_project.php [PDO]: " . $e->getMessage() . " | SQL State: " . $e->getCode() . " | File: " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['success' => false, 'message' => 'Erro de base de dados: ' . $e->getMessage()]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
