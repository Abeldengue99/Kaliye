<?php
/**
 * interface_programacao/mentorship/apply_project_mentorship.php
 * Endpoint para registar a candidatura de um mentor a um projecto pública (projeto).
 */
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$mentor_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'student';
$mentor_status = $_SESSION['mentor_status'] ?? 'unsubmitted';

// Apenas mentores aprovados (incluindo peer mentors) ou admins podem candidatar-se
if ($user_type !== 'admin' && $mentor_status !== 'approved') {
    echo json_encode(['success' => false, 'message' => 'Apenas mentores oficiais aprovados podem candidatar-se a projetos.']);
    exit;
}

$project_id = $_POST['project_id'] ?? null;
if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'ID do projeto não fornecido.']);
    exit;
}

$database = new Database();
$db = $database->getConnection();

require_once '../../inclusoes/ProjectWorkflowSchema.php';
ensureProjectMentorshipApplicationsSchema($db);

try {
    // 1. Verificar se o projeto existe e obter o dono
    $p_stmt = $db->prepare("SELECT owner_id, title FROM projects WHERE project_id = ?");
    $p_stmt->execute([$project_id]);
    $project = $p_stmt->fetch();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projeto não encontrado.']);
        exit;
    }

    $owner_id = $project['owner_id'];
    if ($owner_id == $mentor_id) {
        echo json_encode(['success' => false, 'message' => 'Não podes mentorar o teu próprio projeto.']);
        exit;
    }

    // 2. Verificar se já existe candidatura
    $check = $db->prepare("SELECT COUNT(*) FROM project_mentorship_applications WHERE project_id = ? AND mentor_id = ?");
    $check->execute([$project_id, $mentor_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Já submeteste uma candidatura para mentorar este projeto.']);
        exit;
    }

    // 3. Registar Candidatura
    $motivation = $_POST['motivation'] ?? '';
    
    $ins = $db->prepare("INSERT INTO project_mentorship_applications (project_id, mentor_id, status, motivation) VALUES (?, ?, 'pending', ?)");
    $ins->execute([$project_id, $mentor_id, $motivation]);

    // 4. Notificar Administradores
    $mentor_name = $_SESSION['user_name'] ?? 'Um Mentor';
    $notif_title = "Nova Candidatura de Mentoria";
    $notif_content = "O mentor $mentor_name candidatou-se para mentorar o projecto '" . htmlspecialchars($project['title']) . "'.";
    $link = 'administracao/users/project_mentorship_applications.php';
    
    // Obter todos os admins
    $admins_stmt = $db->query("SELECT user_id FROM users WHERE user_type = 'admin'");
    $admins = $admins_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($admins) > 0) {
        $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'system', ?)");
        foreach ($admins as $admin_id) {
            $notif_ins->execute([$admin_id, $mentor_id, $notif_title, $notif_content, $link]);
        }
    }

    echo json_encode(['success' => true, 'message' => 'Candidatura submetida com sucesso! A equipa KALIYE irá rever a sua proposta.']);

} catch (PDOException $e) {
    if ($e->getCode() == '23505') { // Unique constraint violation (PostgreSQL)
        echo json_encode(['success' => false, 'message' => 'Já submeteste uma candidatura para este projeto.']);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
    }
}
?>
