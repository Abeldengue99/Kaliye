<?php
// interface_programacao/projects/submit_progress_report.php
// Submissão de relatórios de progresso (Fundador ou Mentor)

header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$project_id = $_POST['project_id'] ?? 0;
$title = $_POST['report_title'] ?? '';
$content = $_POST['report_content'] ?? '';
$percentage = $_POST['progress_percentage'] ?? 0;
// Media handling logic would go here if file is uploaded

if ($project_id <= 0 || empty($title) || empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Campos obrigatórios em falta.']);
    exit();
}

$db = (new Database())->getConnection();

try {
    // Verificar se o utilizador é o dono ou o mentor atribuído
    $check = $db->prepare("SELECT owner_id, assigned_mentor_id FROM projects WHERE project_id = ?");
    $check->execute([$project_id]);
    $project = $check->fetch();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projecto não encontrado.']);
        exit();
    }

    if ($project['owner_id'] != $user_id && $project['assigned_mentor_id'] != $user_id) {
        echo json_encode(['success' => false, 'message' => 'Não tens permissão para reportar progresso neste projecto.']);
        exit();
    }

    // Inserir Relatório Pendente de validação pelo Mentor
    $stmt = $db->prepare("
        INSERT INTO project_progress_reports 
        (project_id, author_id, title, content, progress_percentage, report_status) 
        VALUES (?, ?, ?, ?, ?, 'pending_mentor')
    ");
    $stmt->execute([$project_id, $user_id, $title, $content, $percentage]);

    // Notificar o Mentor (se houver um atribuído)
    if ($project['assigned_mentor_id']) {
        $notif_title = "🛠️ Revisão de Progresso Necessária";
        $notif_desc = "O teu mentoreado submeteu um avanço no projecto ID: $project_id. Analisa e valida para seguir para a Aksanti.";
        $notif_link = "paginas/mentoria/mentorship.php"; // Página de gestão do mentor

        $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, ?, ?)");
        $notif->execute([$project['assigned_mentor_id'], $user_id, $notif_title, $notif_desc, 'system', $notif_link]);
    } else {
        // Se não houver mentor, cai direto na Admin (opcional, por agora notificamos a Admin)
        $notif_title = "🚀 Novo Progresso (Sem Mentor)";
        $notif_desc = "O projecto ID: $project_id submeteu progresso mas não tem mentor. A Aksanti deve revisar.";
        
        $admins = $db->query("SELECT user_id FROM users WHERE user_type = 'admin'")->fetchAll(PDO::FETCH_COLUMN);
        foreach($admins as $admin_id) {
            $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type) VALUES (?, ?, ?, ?, ?)");
            $notif->execute([$admin_id, $user_id, $notif_title, $notif_desc, 'system']);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Relatório enviado para validação do Mentor.']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno: ' . $e->getMessage()]);
}
