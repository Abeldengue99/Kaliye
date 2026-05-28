<?php
/**
 * interface_programacao/admin/admin_process_investment_proposal.php
 * Endpoint para o administrador aprovar ou rejeitar propostas de investimento.
 * Phase 1: Sem pagamento digital. Aprovação = contacto presencial.
 */
session_start();
header('Content-Type: application/json');

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit();
}

$input = json_decode(file_get_contents('php://input'), true);
if (!is_array($input)) $input = $_POST;

$investment_id = isset($input['investment_id']) ? (int)$input['investment_id'] : 0;
$action = $input['action'] ?? ''; // 'approve' or 'reject'
$admin_notes = trim($input['admin_notes'] ?? '');

if ($investment_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'error' => 'Parâmetros inválidos.']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

try {
    $db->beginTransaction();

    // Buscar a proposta
    $stmt = $db->prepare("
        SELECT pi.*, p.title AS project_title, u.full_name AS investor_name, p.owner_id
        FROM project_investments pi
        JOIN projects p ON pi.project_id = p.project_id
        JOIN users u ON pi.investor_id = u.user_id
        WHERE pi.investment_id = :id
    ");
    $stmt->execute([':id' => $investment_id]);
    $proposal = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$proposal) {
        throw new Exception('Proposta de investimento não encontrada.');
    }

    // Buscar email do investidor
    $inv_stmt = $db->prepare("SELECT email FROM users WHERE user_id = :id");
    $inv_stmt->execute([':id' => $proposal['investor_id']]);
    $investor_email = $inv_stmt->fetchColumn();

    require_once '../../inclusoes/SimpleMailer.php';
    $mailer = new SimpleMailer();

    if ($action === 'approve') {
        $new_status = 'approved';
        $notif_msg = 'A sua proposta de investimento no projecto "' . $proposal['project_title'] . '" foi APROVADA pela equipa KALIYE! Entraremos em contacto consigo para formalizar presencialmente.';
        
        if ($investor_email) {
            $email_subject = "Boas notícias! A sua proposta de investimento foi aceite.";
            $email_body = "<h3>A sua Proposta foi Aprovada</h3>
                           <p>Olá <strong>{$proposal['investor_name']}</strong>,</p>
                           <p>A equipa KALIYE aprovou a sua intenção de investimento no projeto <strong>{$proposal['project_title']}</strong>.</p>
                           <p><strong>Próximos Passos:</strong> A nossa equipa de gestão irá entrar em contacto consigo dentro de 48 horas para agendar uma reunião de formalização.</p>";
            $mailer->sendEmail($investor_email, $proposal['investor_name'], $email_subject, $email_body);
        }
    } else {
        $new_status = 'rejected';
        $notif_msg = 'A sua proposta de investimento no projecto "' . $proposal['project_title'] . '" não foi aprovada nesta fase.';
        if (!empty($admin_notes)) {
            $notif_msg .= ' Motivo: ' . $admin_notes;
        }

        if ($investor_email) {
            $email_subject = "Atualização sobre a sua proposta de investimento";
            $email_body = "<h3>Resultado da sua Proposta</h3>
                           <p>Olá <strong>{$proposal['investor_name']}</strong>,</p>
                           <p>Obrigado por demonstrar interesse no projeto <strong>{$proposal['project_title']}</strong>.</p>
                           <p>Lamentamos informar que a sua proposta não foi aceite nesta fase.</p>";
            if (!empty($admin_notes)) {
                $email_body .= "<p><strong>Nota da equipa:</strong> " . htmlspecialchars($admin_notes) . "</p>";
            }
            $email_body .= "<p>Continue a explorar o ecossistema KALIYE para encontrar mais oportunidades de investimento.</p>";
            $mailer->sendEmail($investor_email, $proposal['investor_name'], $email_subject, $email_body);
        }
    }

    // Atualizar o estado
    $db->prepare("UPDATE project_investments SET status = :status WHERE investment_id = :id")
       ->execute([':status' => $new_status, ':id' => $investment_id]);

    // Notificar o investidor
    try {
        $db->prepare("INSERT INTO notifications (user_id, type, content, reference_id) VALUES (?, 'investment_decision', ?, ?)")
           ->execute([$proposal['investor_id'], $notif_msg, $investment_id]);
    } catch (PDOException $e) {
        error_log("Notification error: " . $e->getMessage());
    }

    // Registar na auditoria
    try {
        $admin_name = $_SESSION['full_name'] ?? 'Admin';
        $audit_action = $action === 'approve' ? 'approve_investment' : 'reject_investment';
        $audit_details = $admin_name . ' ' . ($action === 'approve' ? 'aprovou' : 'rejeitou') . ' a proposta de investimento #' . $investment_id . ' de ' . $proposal['investor_name'] . ' no projecto "' . $proposal['project_title'] . '"';
        $db->prepare("INSERT INTO audit_logs (admin_id, action, details) VALUES (?, ?, ?)")
           ->execute([$_SESSION['user_id'], $audit_action, $audit_details]);
    } catch (PDOException $e) {
        error_log("Audit log error: " . $e->getMessage());
    }

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => $action === 'approve' 
            ? 'Proposta aprovada com sucesso! O investidor foi notificado.' 
            : 'Proposta rejeitada. O investidor foi notificado.'
    ]);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
