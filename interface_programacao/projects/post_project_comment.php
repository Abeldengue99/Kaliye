<?php
// interface_programacao/projects/post_project_comment.php
// Gerencia a publicação de comentários em projetos e respostas
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

// Obter dados da requisição com verificação de CSRF manual para o form data JS
function verifyV2CSRF() {
    $token = $_POST['csrf_token'] ?? getRequestCSRFToken();
    if (!verifyCSRFToken($token)) {
        http_response_code(403);
        echo json_encode(['success' => false, 'message' => 'Token de segurança (CSRF) inválido. Recarregue a página.']);
        exit;
    }
}
verifyV2CSRF();

$input = json_decode(file_get_contents('php://input'), true);

$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : (isset($input['project_id']) ? (int)$input['project_id'] : 0);
$parent_id = isset($_POST['parent_id']) ? (int)$_POST['parent_id'] : (isset($input['parent_id']) ? (int)$input['parent_id'] : 0);
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
$db = $database->getConnection();

function ensureProjectCommentsV2Table(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS project_comments_v2 (
            comment_id SERIAL PRIMARY KEY,
            project_id INT NOT NULL,
            user_id INT NOT NULL,
            parent_id INT DEFAULT NULL,
            content TEXT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        );
    ");
}

try {
    ensureProjectCommentsV2Table($db);

    // Verificar se o projeto existe e obter o dono
    $check_project = $db->prepare("SELECT owner_id, title FROM projects WHERE project_id = :id");
    $check_project->execute([':id' => $project_id]);
    $project = $check_project->fetch(PDO::FETCH_ASSOC);
    
    if (!$project) {
        echo json_encode(['success' => false, 'message' => 'Projeto não encontrado']);
        exit;
    }

    // Inserir na nova tabela
    $parent_val = $parent_id > 0 ? $parent_id : null;
    $insert_query = "INSERT INTO project_comments_v2 (project_id, user_id, parent_id, content) VALUES (:project_id, :user_id, :parent_id, :content)";
    $insert_stmt = $db->prepare($insert_query);
    $insert_stmt->execute([
        ':project_id' => $project_id, 
        ':user_id' => $user_id, 
        ':parent_id' => $parent_val,
        ':content' => $content
    ]);
    
    $comment_id = $db->lastInsertId();
    
    // Obter dados do usuário
    $user_query = "SELECT full_name, profile_pic, user_type FROM users WHERE user_id = :user_id";
    $user_stmt = $db->prepare($user_query);
    $user_stmt->execute([':user_id' => $user_id]);
    $user_data = $user_stmt->fetch(PDO::FETCH_ASSOC);

    // --- LÓGICA DE NOTIFICAÇÕES ---
    $is_investor = ($user_data['user_type'] === 'investor');
    $sender_notif_id = $is_investor ? null : $user_id;
    $content_preview = substr($content, 0, 50) . "...";

    // Se for uma resposta (parent_id > 0)
    if ($parent_val !== null) {
        $parent_query = $db->prepare("SELECT user_id FROM project_comments_v2 WHERE comment_id = ?");
        $parent_query->execute([$parent_val]);
        $parent_comment = $parent_query->fetch();

        // Notifica o autor do comentário pai se não for ele próprio a responder
        if ($parent_comment && $parent_comment['user_id'] != $user_id) {
            $notif_title = "💬 Responderam ao seu comentário!";
            $notif_content = ($is_investor ? "Um Investidor" : $user_data['full_name']) . " respondeu: '" . $content_preview . "'";
            $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, reference_id, link) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $notif_ins->execute([
                $parent_comment['user_id'],
                $sender_notif_id,
                $notif_title,
                $notif_content,
                'project_comment',
                $project_id,
                'index.php?project_id=' . $project_id
            ]);
        }
    } else {
        // Se for um comentário principal, notifica o dono do projeto (se não for ele próprio)
        if ($project['owner_id'] != $user_id) {
            $notif_title = "💬 Novo Comentário no seu Projecto!";
            $notif_content = ($is_investor ? "Um Investidor" : $user_data['full_name']) . " comentou: '" . $content_preview . "'";
            $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, reference_id, link) VALUES (?, ?, ?, ?, ?, ?, ?)");
            $notif_ins->execute([
                $project['owner_id'],
                $sender_notif_id,
                $notif_title,
                $notif_content,
                'project_comment',
                $project_id,
                'index.php?project_id=' . $project_id
            ]);
        }
    }
    
    echo json_encode([
        'success' => true, 
        'comment' => [
            'id' => $comment_id,
            'parent_id' => $parent_val,
            'content' => $content,
            'user_id' => $user_id,
            'user_name' => $user_data['full_name'],
            'user_pic' => $user_data['profile_pic'] ?? 'default_profile.png',
            'created_at' => date('Y-m-d H:i:s')
        ]
    ]);

} catch (PDOException $e) {
    error_log("PDOException no post_project_comment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao publicar comentário.']);
} catch (Exception $e) {
    error_log("Exception no post_project_comment.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao publicar comentário.']);
}
?>
