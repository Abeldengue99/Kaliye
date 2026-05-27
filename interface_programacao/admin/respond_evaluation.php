<?php
/**
 * interface_programacao/admin/respond_evaluation.php
 * Endpoint para administradores responderem a feedbacks de utilizadores.
 */
@session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['user_type'] !== 'admin' && $_SESSION['user_type'] !== 'superadmin')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado']);
    exit();
}

$evaluation_id = isset($_POST['evaluation_id']) ? (int)$_POST['evaluation_id'] : 0;
$response = isset($_POST['response']) ? trim($_POST['response']) : '';

if (!$evaluation_id || !$response) {
    echo json_encode(['success' => false, 'message' => 'Dados incompletos']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    // 1. Atualizar a avaliação com a resposta
    $stmt = $db->prepare("UPDATE platform_evaluations SET admin_response = ?, responded_at = NOW() WHERE id = ?");
    $stmt->execute([$response, $evaluation_id]);
    
    // 2. Buscar o ID do utilizador para enviar a notificação
    $user_stmt = $db->prepare("SELECT user_id, rating FROM platform_evaluations WHERE id = ?");
    $user_stmt->execute([$evaluation_id]);
    $eval = $user_stmt->fetch();
    
    if ($eval) {
        $user_id = $eval['user_id'];
        $title = "Feedback Respondido pela Equipa";
        $content = "A equipa KALIYE respondeu à tua sugestão/avaliação. Clica para ver mais.";
        
        // 3. Inserir Notificação
        $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'system', '#')");
        $notif->execute([$user_id, $_SESSION['user_id'], $title, $content]);
    }
    
    echo json_encode(['success' => true, 'message' => 'Resposta enviada e utilizador notificado!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
