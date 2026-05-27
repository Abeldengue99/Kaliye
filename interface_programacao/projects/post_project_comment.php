<?php
// servicos/projects/post_project_comment.php
// Gerencia a publicação de comentários em projetos
header('Content-Type: application/json');
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

// Verificar se usuário está logado
if (!isset($_SESSION['user_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit;
}

// Obter dados da requisição
requireValidCSRFTokenJson();
$input = json_decode(file_get_contents('php://input'), true);

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : (isset($input['project_id']) ? (int)$input['project_id'] : 0);
$content = isset($_POST['content']) ? trim($_POST['content']) : (isset($input['content']) ? trim($input['content']) : '');
$user_id = $_SESSION['user_id'];

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de projeto inválido']);
    exit;
}

if (empty($content)) {
    echo json_encode(['success' => false, 'message' => 'Conteúdo do comentário é obrigatório']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verificar se o projeto existe
    $check_project = $db->prepare("SELECT project_id FROM projects WHERE project_id = :id");
    $check_project->execute([':id' => $project_id]);
    
    if ($check_project->rowCount() === 0) {
        echo json_encode(['success' => false, 'message' => 'Projeto não encontrado']);
        exit;
    }

    // Inserir comentário na tabela project_comments
    $insert_query = "INSERT INTO project_comments (project_id, user_id, content) VALUES (:project_id, :user_id, :content)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->execute([
        ':project_id' => $project_id, 
        ':user_id' => $user_id, 
        ':content' => $content
    ]);
    
    $comment_id = $db->lastInsertId();
    
    // Obter dados do comentário recém criado para adicionar na UI
    $user_query = "SELECT full_name, profile_pic, user_type FROM users WHERE user_id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([':user_id' => $user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // --- NOTIFICAÇÃO AO DONO ---
    $project_query = "SELECT owner_id, title FROM projects WHERE project_id = :id";
    $project_stmt = $db->prepare($project_query);
    $project_stmt->execute([':id' => $project_id]);
    $project = $project_stmt->fetch();

    if ($project && $project['owner_id'] != $user_id) {
        $is_investor = ($user_data['user_type'] === 'investor');
        $notif_title = "💬 Novo Comentário no seu Projecto!";
        $notif_content = ($is_investor ? "Um Investidor" : $user_data['full_name']) . " comentou: '" . substr($content, 0, 50) . "...'";
        $sender_notif_id = $is_investor ? null : $user_id;

        $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, ?, ?)");
        $notif_ins->execute([
            $project['owner_id'],
            $sender_notif_id,
            $notif_title,
            $notif_content,
            'comment',
            'index.php?id=' . $project_id
        ]);
    }
    
    echo json_encode([
        'success' => true, 
        'comment' => [
            'id' => $comment_id,
            'content' => $content,
            'user_id' => $user_id,
            'user_name' => $user_data['full_name'],
            'user_pic' => $user_data['profile_pic'] ?? 'default_profile.png',
            'created_at' => date('Y-m-d H:i:s') // Simulação de timestamp imediato
        ]
    ]);

} catch (PDOException $e) {
    error_log("Erro no post_project_comment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar comentário']);
}

