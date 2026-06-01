<?php
/**
 * interface_programacao/projects/apply_specialist_project.php
 * Endpoint para registar a candidatura de um especialista a um projeto.
 */
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$specialist_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'student';

// Apenas especialistas/mentores podem candidatar-se como especialistas
if ($user_type !== 'mentor' && $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Apenas especialistas aprovados podem candidatar-se como especialista a projetos.']);
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
ensureSpecialistApplicationsSchema($db);

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
    if ($owner_id == $specialist_id) {
        echo json_encode(['success' => false, 'message' => 'Não podes candidatar-te como especialista no teu próprio projeto.']);
        exit;
    }

    // 2. Verificar se já existe candidatura
    $check = $db->prepare("SELECT COUNT(*) FROM project_specialist_applications WHERE project_id = ? AND specialist_id = ?");
    $check->execute([$project_id, $specialist_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Já submeteste uma candidatura como especialista para este projeto.']);
        exit;
    }

    // 3. Registar Candidatura
    $motivation = $_POST['motivation'] ?? '';
    $relevant_experience = $_POST['relevant_experience'] ?? '';
    $proposed_support = $_POST['proposed_support'] ?? '';
    $specialization_areas = $_POST['specialization_areas'] ?? '';
    $availability = $_POST['availability'] ?? '';
    
    $ins = $db->prepare("INSERT INTO project_specialist_applications 
        (project_id, specialist_id, status, motivation, relevant_experience, proposed_support, specialization_areas, availability) 
        VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?)");
    $ins->execute([$project_id, $specialist_id, $motivation, $relevant_experience, $proposed_support, $specialization_areas, $availability]);

    // 4. Notificar Administradores
    $specialist_name = $_SESSION['user_name'] ?? 'Um Especialista';
    $notif_title = "Nova Candidatura de Especialista";
    $notif_content = "O especialista $specialist_name candidatou-se para o projecto '" . htmlspecialchars($project['title']) . "'.";
    $link = 'administracao/users/project_specialist_applications.php';
    
    // Obter todos os admins
    $admins_stmt = $db->query("SELECT user_id FROM users WHERE user_type IN ('admin', 'superadmin')");
    $admins = $admins_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($admins) > 0) {
        $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'system', ?)");
        foreach ($admins as $admin_id) {
            $notif_ins->execute([$admin_id, $specialist_id, $notif_title, $notif_content, $link]);
        }
    }

    echo json_encode([
        'success' => true, 
        'message' => 'Candidatura registada com sucesso. A administração analisará a tua candidatura em breve.',
        'data' => ['application_id' => $db->lastInsertId()]
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao registar candidatura: ' . $e->getMessage()]);
    exit;
}
?>
