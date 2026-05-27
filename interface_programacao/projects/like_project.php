<?php
// servicos/projects/like_project.php
// Gerencia likes/unlikes em projetos
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
// Suporte tanto para JSON quanto para Form Data
$project_id = isset($input['project_id']) ? (int)$input['project_id'] : (isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0);
$user_id = $_SESSION['user_id'];

if ($project_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'ID de projeto inválido']);
    exit;
}

try {
    error_log("LIKE ACTION: project=" . $project_id . " user=" . $user_id);
    $database = new Database();
    $db = $database->getConnection();
    if (!$db) throw new Exception("Falha na conexão DB");
    // Verificar se já deu like
    $check_query = "SELECT like_id FROM project_likes WHERE project_id = :project_id AND user_id = :user_id";
    $check_stmt = $db->prepare($check_query);
    $check_stmt->execute([':project_id' => $project_id, ':user_id' => $user_id]);
    
    if ($check_stmt->rowCount() > 0) {
        // Já deu like -> Remover (Unlike)
        $delete_query = "DELETE FROM project_likes WHERE project_id = :project_id AND user_id = :user_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([':project_id' => $project_id, ':user_id' => $user_id]);
        $action = 'unliked';
    } else {
        // Não deu like -> Adicionar (Like)
        $insert_query = "INSERT INTO project_likes (project_id, user_id) VALUES (:project_id, :user_id)";
        $insert_stmt = $db->prepare($insert_query);
        $insert_stmt->execute([':project_id' => $project_id, ':user_id' => $user_id]);
        $action = 'liked';
        // --- NOTIFICAÇÃO ---
        // Obter dono do projeto e tipo do usuário que deu like
        $owner_query = "SELECT owner_id, title FROM projects WHERE project_id = :id";
        $owner_stmt = $db->prepare($owner_query);
        $owner_stmt->execute([':id' => $project_id]);
        $project_data = $owner_stmt->fetch();
        
        if ($project_data && $project_data['owner_id'] != $user_id) {
            // Obter dados de quem deu like
            $sender_query = "SELECT full_name, profile_pic, user_type FROM users WHERE user_id = :uid";
            $sender_stmt = $db->prepare($sender_query);
            $sender_stmt->execute([':uid' => $user_id]);
            $sender = $sender_stmt->fetch();
            
            $is_investor = ($sender['user_type'] === 'investor');
            $actor_name = $is_investor ? "Um Investidor" : $sender['full_name'];
            $notif_title = "❤ " . $actor_name . " Adorou o seu Projecto!";
            $notif_content = $actor_name . " reagiu com 'Adoro' ao seu projecto: '" . $project_data['title'] . "'";
            
            // Agora mantemos SEMPRE o sender_id para o frontend inferir o type, mas o frontend aplica KYC de anonimato automaticamente se user_role = 'investor'
            $notif_ins = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, ?, ?)");
            $notif_ins->execute([
                $project_data['owner_id'],
                $user_id,
                $notif_title,
                $notif_content,
                'project_like',
                'index.php?project_modal=' . $project_id
            ]);
        }
    }
    
    // Obter nova contagem
    $count_query = "SELECT COUNT(*) as total FROM project_likes WHERE project_id = :project_id";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute([':project_id' => $project_id]);
    $total_likes = $count_stmt->fetchColumn();
    
    echo json_encode([
        'success' => true, 
        'action' => $action, 
        'new_count' => $total_likes
    ]);

} catch (PDOException $e) {
    error_log("Erro no like_project.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro no banco de dados']);
}

