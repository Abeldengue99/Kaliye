<?php
// interface_programacao/admin/approve_progress_report.php
// Administração aprova/publica o progresso para o investidor

header('Content-Type: application/json');
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('moderation')) {
    echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
    exit();
}

$report_id = $_POST['report_id'] ?? 0;
$action = $_POST['action'] ?? ''; // approve, reject
$admin_feedback = $_POST['admin_feedback'] ?? '';
$admin_id = $_SESSION['user_id'];

if ($report_id <= 0 || !in_array($action, ['approve', 'feedback', 'reject'], true)) {
    echo json_encode(['success' => false, 'message' => 'Campos em falta.']);
    exit();
}

$db = (new Database())->getConnection();

try {
    $db->beginTransaction();

    // Buscar informações do relatório
    $stmt = $db->prepare("SELECT r.*, p.owner_id FROM project_progress_reports r JOIN projects p ON r.project_id = p.project_id WHERE r.report_id = ?");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Relatório não encontrado.']);
        exit();
    }

    $new_status = ($action === 'approve') ? 'published' : 'admin_feedback';
    
    // 1. Atualizar o relatório
    $upt = $db->prepare("UPDATE project_progress_reports SET report_status = ?, admin_feedback = ?, reviewed_at = NOW() WHERE report_id = ?");
    $upt->execute([$new_status, $admin_feedback, $report_id]);

    // ==========================================
    // 🔓 MILESTONE ESCROW: LIBERTAÇÃO DE FUNDOS
    // ==========================================
    if ($action === 'approve') {
        // Buscar saldo do escrow e dados do proprietário
        $escrow_stmt = $db->prepare("SELECT escrow_balance, current_milestone_index, owner_id FROM projects WHERE project_id = ?");
        $escrow_stmt->execute([$report['project_id']]);
        $project_data = $escrow_stmt->fetch();

        $escrow_balance    = (float)($project_data['escrow_balance'] ?? 0);
        $milestone_index   = (int)($project_data['current_milestone_index'] ?? 0);
        $founder_id        = $project_data['owner_id'];

        if ($escrow_balance > 0) {
            // Tranche = 25% por marco aprovado (máximo 4 tranches = 100%)
            $tranche_pct    = 0.25;
            $tranche_amount = round($escrow_balance * $tranche_pct, 2);

            // Debitar do escrow e creditar na carteira do fundador
            $db->prepare("UPDATE projects SET escrow_balance = GREATEST(0, escrow_balance - ?) , current_milestone_index = current_milestone_index + 1 WHERE project_id = ?")
               ->execute([$tranche_amount, $report['project_id']]);

            // Verificar se a coluna wallet_balance existe nos users
            try {
                $db->prepare("UPDATE users SET wallet_balance = COALESCE(wallet_balance, 0) + ? WHERE user_id = ?")
                   ->execute([$tranche_amount, $founder_id]);
            } catch (PDOException $we) {
                // Coluna não existe ainda — apenas continuar sem quebrar o fluxo
                error_log('wallet_balance não encontrado: ' . $we->getMessage());
            }

            // Notificar Fundador sobre a tranche libertada
            $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type) VALUES (?, ?, ?, ?, 'investment')")
               ->execute([
                   $founder_id, $admin_id,
                   '💰 Tranche de Capital Libertada!',
                   number_format($tranche_amount, 2, ',', '.') . ' AOA foram transferidos do escrow para a sua carteira após a aprovação do Marco ' . ($milestone_index + 1) . '.'
               ]);
        }

        // Encontrar investidores reais
        $investors = $db->prepare("SELECT investor_id FROM project_investments WHERE project_id = ?");
        $investors->execute([$report['project_id']]);
        $investor_ids = $investors->fetchAll(PDO::FETCH_COLUMN);

        // Notificar cada investidor
        foreach($investor_ids as $inv_id) {
            $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, ?, ?)");
            $notif->execute([
                $inv_id,
                $admin_id,
                "✅ Novo Progresso Validado pela KALIYE",
                "O projecto que investiste foi actualizado. Clica para ver o Roadmap.",
                'system',
                'index.php?id=' . $report['project_id']
            ]);
        }
    } else {
        // Se for feedback, notificar Fundador e Mentor (Ambos supervisionam o erro)
        $msg_notif = "A KALIYE Admin solicitou alterações: $admin_feedback";
        
        $notif_stakeholders = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type) VALUES (?, ?, ?, ?, ?)");
        
        // Notificar Fundador
        $notif_stakeholders->execute([$report['owner_id'], $admin_id, "⚠️ Ajuste Admin Necessário", $msg_notif, 'system']);
        
        // Notificar Mentor
        $mentor_id = $db->query("SELECT assigned_mentor_id FROM projects WHERE project_id = ".$report['project_id'])->fetchColumn();
        if ($mentor_id) {
            $notif_stakeholders->execute([$mentor_id, $admin_id, "🔍 Revisão de Monitoria Solicitada", $msg_notif, 'system']);
        }
    }

    // 3. Notificar o fundador do resultado da análise
    $notif_founder = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type) VALUES (?, ?, ?, ?, ?)");
    $notif_founder->execute([
        $report['owner_id'],
        $admin_id,
        ($action === 'approve') ? "🎉 Progresso Publicado!" : "⚠️ Feedback sobre o seu Relatório",
        ($action === 'approve') ? "O seu progresso foi validado e agora é visível para investidores." : "A KALIYE solicitou alterações no seu relatório: $admin_feedback",
        'system'
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Ação processada com sucesso.']);

} catch (PDOException $e) {
    if($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
