<?php
// interface_programacao/mentorship/mentor_action_progress.php
// O Mentor analisa o progresso e decide se envia para a Aksanti ou pede correcções.

header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id']) || !in_array($_SESSION['user_type'], ['mentor', 'admin'])) {
    echo json_encode(['success' => false, 'message' => 'Permissão negada.']);
    exit();
}

$mentor_id = $_SESSION['user_id'];
$report_id = $_POST['report_id'] ?? 0;
$action = $_POST['action'] ?? ''; // validate, feedback
$feedback_text = $_POST['mentor_feedback'] ?? '';
// $feedback_media = ... (Implementar upload se necessário)

if ($report_id <= 0 || empty($action)) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos.']);
    exit();
}

$db = (new Database())->getConnection();

try {
    // 1. Verificar se este mentor é realmente o mentor do projecto associado ao relatório
    $stmt = $db->prepare("
        SELECT r.*, p.assigned_mentor_id, p.owner_id, p.title as project_name 
        FROM project_progress_reports r 
        JOIN projects p ON r.project_id = p.project_id 
        WHERE r.report_id = ?
    ");
    $stmt->execute([$report_id]);
    $report = $stmt->fetch();

    if (!$report) {
        echo json_encode(['success' => false, 'message' => 'Relatório não encontrado.']);
        exit();
    }

    // Apenas o mentor atribuído ou um admin pode validar
    if ($report['assigned_mentor_id'] != $mentor_id && $_SESSION['user_type'] != 'admin') {
        echo json_encode(['success' => false, 'message' => 'Não tens jurisdição sobre este projecto.']);
        exit();
    }

    if ($action === 'validate') {
        // Enviar para a Administração
        $upt = $db->prepare("UPDATE project_progress_reports SET report_status = 'pending_admin', mentor_approved_at = NOW() WHERE report_id = ?");
        $upt->execute([$report_id]);

        // Notificar Admins
        $admins = $db->query("SELECT user_id FROM users WHERE user_type = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach($admins as $admin_id) {
            $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type) VALUES (?, ?, ?, ?, ?)");
            $notif->execute([
                $admin_id, 
                $mentor_id, 
                "🔍 Novo Progresso para Revisão Admin", 
                "O mentor validou o progresso do projecto: " . $report['project_name'], 
                'system'
            ]);
        }
        $msg = "Progresso validado e enviado para a Aksanti Admin.";

    } else if ($action === 'feedback') {
        // Pedir correcções ao Fundador
        $upt = $db->prepare("UPDATE project_progress_reports SET report_status = 'mentor_feedback', mentor_feedback = ? WHERE report_id = ?");
        $upt->execute([$feedback_text, $report_id]);

        // Notificar o Fundador (Mentoreado)
        $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, ?, ?)");
        $notif->execute([
            $report['owner_id'],
            $mentor_id,
            "⚠️ Feedback do teu Mentor",
            "O teu mentor solicitou ajustes no teu relatório de progresso: " . substr($feedback_text, 0, 40) . "...",
            'system',
            'paginas/explorar/my_projects.php'
        ]);
        $msg = "Feedback enviado ao fundador para ajustes.";
    }

    echo json_encode(['success' => true, 'message' => $msg]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
