<?php
/**
 * interface_programacao/admin/admin_process_investment.php
 * Processa aprovação, rejeição e pagamento de investimentos.
 * Integração com Milestone Escrow: fundos aprovados vão para escrow_balance do projeto.
 */
header('Content-Type: application/json');
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('finance_docs')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$investment_id  = (int)($_POST['investment_id'] ?? 0);
$new_status     = $_POST['status'] ?? '';
$admin_id       = $_SESSION['user_id'];
$reason_code    = $_POST['reason_code'] ?? '';

if ($investment_id <= 0 || !in_array($new_status, ['approved', 'paid', 'rejected'], true)) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos.']);
    exit();
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    // Buscar o investimento e dados relacionados
    $stmt = $db->prepare("
        SELECT pi.*, p.owner_id, p.title as project_title, p.escrow_balance,
               i.full_name as investor_name, i.email as investor_email
        FROM project_investments pi
        JOIN projects p ON pi.project_id = p.project_id
        JOIN users i ON pi.investor_id = i.user_id
        WHERE pi.investment_id = ?
    ");
    $stmt->execute([$investment_id]);
    $inv = $stmt->fetch();

    if (!$inv) {
        throw new Exception("Investimento não encontrado.");
    }

    $current_status = (string)$inv['status'];
    $allowed_transitions = [
        'pending' => ['approved', 'rejected'],
        'approved' => ['paid', 'rejected'],
    ];

    if (!isset($allowed_transitions[$current_status]) || !in_array($new_status, $allowed_transitions[$current_status], true)) {
        throw new Exception("Transicao invalida: investimento em estado '{$current_status}' não pode ir para '{$new_status}'.");
    }
    // 1. Atualizar o status do investimento
    $db->prepare("UPDATE project_investments SET status = ?, updated_at = NOW() WHERE investment_id = ?")
       ->execute([$new_status, $investment_id]);

    // 2. LÓGICA DE ESCROW: Quando marcado como 'paid', o valor líquido vai para o escrow do projeto
    if ($new_status === 'paid') {
        $net_amount = (float)$inv['net_amount_to_project'];
        
        $db->prepare("UPDATE projects SET escrow_balance = COALESCE(escrow_balance, 0) + ? WHERE project_id = ?")
           ->execute([$net_amount, $inv['project_id']]);

        // Notificar o fundador
        $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'investment', ?)")
           ->execute([
               $inv['owner_id'],
               $admin_id,
               '🔒 Capital em Custódia Ativa!',
               number_format($net_amount, 2, ',', '.') . ' AOA do investidor ' . $inv['investor_name'] . ' foram assegurados em custódia. Os fundos serão libertados conforme os seus marcos de progresso são validados.',
               'paginas/plataforma/my_projects.php'
           ]);

        // Notificar o investidor
        $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'investment', ?)")
           ->execute([
               $inv['investor_id'],
               $admin_id,
               '✅ Investimento Confirmado!',
               'O seu investimento no projeto "' . $inv['project_title'] . '" foi confirmado. Os fundos estão em custódia segura e serão libertados mediante marcos de progresso.',
               'paginas/plataforma/investor_dashboard.php'
           ]);
    }

    // 3. Notificação de rejeição
    if ($new_status === 'rejected') {
        $reason_map = ['fraud' => 'Comprovativo inválido ou fraudulento', 'other' => 'Outros motivos'];
        $reason_text = $reason_map[$reason_code] ?? 'Não especificado';

        $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type) VALUES (?, ?, ?, ?, 'system')")
           ->execute([
               $inv['investor_id'],
               $admin_id,
               '❌ Investimento Rejeitado',
               'O seu investimento no projeto "' . $inv['project_title'] . '" foi rejeitado. Motivo: ' . $reason_text
           ]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Ação processada com sucesso.']);

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
?>
