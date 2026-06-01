<?php
/**
 * interface_programacao/projects/apply_student_investment_project.php
 * Endpoint para registar a candidatura de um estudante a um projeto de investidor.
 */
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$student_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'student';

// Apenas estudantes podem candidatar-se como estudante
if ($user_type !== 'student' && $user_type !== 'admin') {
    echo json_encode(['success' => false, 'message' => 'Apenas estudantes podem candidatar-se a projetos como estudante.']);
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
ensureStudentApplicationsSchema($db);

try {
    // 1. Verificar se o projeto existe e obter o dono (investor)
    $p_stmt = $db->prepare("SELECT owner_id, title, owner_type FROM projects WHERE project_id = ?");
    $p_stmt->execute([$project_id]);
    $project = $p_stmt->fetch();

    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projeto não encontrado.']);
        exit;
    }

    // Verificar se é projeto de investidor
    if ($project['owner_type'] !== 'investor' && $project['owner_type'] !== 'mentor') {
        echo json_encode(['success' => false, 'message' => 'Este projeto não está aberto para candidaturas de estudantes.']);
        exit;
    }

    $owner_id = $project['owner_id'];
    if ($owner_id == $student_id) {
        echo json_encode(['success' => false, 'message' => 'Não podes candidatar-te ao teu próprio projeto.']);
        exit;
    }

    // 2. Verificar se já existe candidatura
    $check = $db->prepare("SELECT COUNT(*) FROM project_student_applications WHERE project_id = ? AND student_id = ?");
    $check->execute([$project_id, $student_id]);
    if ($check->fetchColumn() > 0) {
        echo json_encode(['success' => false, 'message' => 'Já submeteste uma candidatura para este projeto.']);
        exit;
    }

    // 3. Registar Candidatura
    $motivation = $_POST['motivation'] ?? '';
    $relevant_skills = $_POST['relevant_skills'] ?? '';
    $learning_objectives = $_POST['learning_objectives'] ?? '';
    $time_availability = $_POST['time_availability'] ?? '';
    $academic_background = $_POST['academic_background'] ?? '';
    
    $ins = $db->prepare("INSERT INTO project_student_applications 
        (project_id, student_id, status, motivation, relevant_skills, learning_objectives, time_availability, academic_background) 
        VALUES (?, ?, 'submitted', ?, ?, ?, ?, ?)");
    $ins->execute([$project_id, $student_id, $motivation, $relevant_skills, $learning_objectives, $time_availability, $academic_background]);

    // 4. Notificar Administradores
    $student_name = $_SESSION['user_name'] ?? 'Um Estudante';
    $notif_title = "Nova Candidatura de Estudante";
    $notif_content = "O estudante $student_name candidatou-se para o projecto '" . htmlspecialchars($project['title']) . "'.";
    $link = 'administracao/users/project_student_applications.php';
    
    // Obter todos os admins
    $admins_stmt = $db->query("SELECT user_id FROM users WHERE user_type IN ('admin', 'superadmin')");
    $admins = $admins_stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (count($admins) > 0) {
        $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'system', ?)");
        foreach ($admins as $admin_id) {
            $notif_ins->execute([$admin_id, $student_id, $notif_title, $notif_content, $link]);
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
