<?php
// interface_programacao/admin/admin_process_mentor.php
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

// Verificar se é admin
if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if (!hasPermission('mentor_approval')) {
    echo json_encode(['success' => false, 'message' => 'Sem permissão para esta ação.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$action = isset($_POST['action']) ? $_POST['action'] : '';

if ($user_id <= 0 || !in_array($action, ['approve', 'reject'])) {
    echo json_encode(['success' => false, 'message' => 'Dados inválidos.']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Verificar se o usuário existe e está pendente
    $check = $db->prepare("SELECT full_name, email, user_type, is_peer_mentor, mentorship_status FROM users WHERE user_id = ?");
    $check->execute([$user_id]);
    $user = $check->fetch(PDO::FETCH_ASSOC);
    
    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado.']);
        exit();
    }
    
    if ($user['mentorship_status'] !== 'pending') {
        echo json_encode(['success' => false, 'message' => 'Esta candidatura já foi processada.']);
        exit();
    }
    
    if ($action === 'approve') {
        // Aprovar mentor
        $updateSql = "UPDATE users SET mentorship_status = 'approved'";
        if ($user['user_type'] !== 'univ_student' && $user['user_type'] !== 'high_student') {
            $updateSql .= ", user_type = 'mentor'";
        }
        $updateSql .= " WHERE user_id = ?";
        $update = $db->prepare($updateSql);
        $update->execute([$user_id]);
        
        // Criar notificação para o usuário
        $notif = $db->prepare("INSERT INTO notifications (user_id, title, content, type, link) VALUES (?, ?, ?, ?, ?)");
        $notif->execute([
            $user_id,
            'Candidatura Aprovada!',
            'Parabéns! A sua candidatura a Mentor de Referência KALIYE foi aprovada. Agora pode começar a ajudar estudantes e empreendedores.',
            'system',
            'paginas/mentoria/mentorship.php'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Mentor aprovado com sucesso! ' . htmlspecialchars($user['full_name']) . ' foi notificado.'
        ]);
        
    } else {
        // Rejeitar mentor
        $update = $db->prepare("UPDATE users SET mentorship_status = 'rejected' WHERE user_id = ?");
        $update->execute([$user_id]);
        
        // Criar notificação para o usuário
        $notif = $db->prepare("INSERT INTO notifications (user_id, title, content, type, link) VALUES (?, ?, ?, ?, ?)");
        $notif->execute([
            $user_id,
            'Candidatura Não Aprovada',
            'Infelizmente, a sua candidatura a Mentor não foi aprovada neste momento. Pode tentar novamente mais tarde após ganhar mais experiência.',
            'system',
            'paginas/social/profile.php'
        ]);
        
        echo json_encode([
            'success' => true, 
            'message' => 'Candidatura rejeitada. ' . htmlspecialchars($user['full_name']) . ' foi notificado.'
        ]);
    }
    
} catch (PDOException $e) {
    error_log("Erro em admin_process_mentor.php: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar candidatura: ' . $e->getMessage()]);
}
?>

