<?php
// servicos/social/delete_doubt.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}
requireValidCSRFTokenJson();

$doubt_id = $_POST['doubt_id'] ?? null;

if (!$doubt_id) {
    echo json_encode(['success' => false, 'message' => 'Parâmetros inválidos']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // Check permission
    $query = "SELECT user_id FROM doubts WHERE doubt_id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute([$doubt_id]);
    $doubt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doubt) {
        echo json_encode(['success' => false, 'message' => 'Dúvida não encontrada']);
        exit();
    }

    if ($doubt['user_id'] != $_SESSION['user_id'] && $_SESSION['user_type'] !== 'admin') {
        echo json_encode(['success' => false, 'message' => 'Acesso negado']);
        exit();
    }

    // Delete comments first if not set to cascade
    $del_comments = "DELETE FROM doubt_comments WHERE doubt_id = ?";
    $cmd = $db->prepare($del_comments);
    $cmd->execute([$doubt_id]);

    $del_query = "DELETE FROM doubts WHERE doubt_id = ?";
    $del = $db->prepare($del_query);
    
    if ($del->execute([$doubt_id])) {
        echo json_encode(['success' => true]);
    } else {
        echo json_encode(['success' => false, 'message' => 'Erro ao eliminar']);
    }

} catch (PDOException $e) {
    error_log('delete_doubt error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao eliminar duvida.']);
}

