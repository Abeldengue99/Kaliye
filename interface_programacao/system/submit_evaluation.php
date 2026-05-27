<?php
/**
 * interface_programacao/system/submit_evaluation.php
 * Endpoint para submissão de avaliações da plataforma.
 */
session_start();
header('Content-Type: application/json');
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Usuário não autenticado']);
    exit();
}

$user_id = $_SESSION['user_id'];
$rating = isset($_POST['rating']) ? (int)$_POST['rating'] : 0;
$comment = isset($_POST['comment']) ? trim($_POST['comment']) : '';

if ($rating < 1 || $rating > 5) {
    echo json_encode(['success' => false, 'message' => 'Classificação inválida']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    $stmt = $db->prepare("INSERT INTO platform_evaluations (user_id, rating, comment) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $rating, $comment]);
    
    echo json_encode(['success' => true, 'message' => 'Obrigado pelo seu feedback!']);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro ao guardar avaliação: ' . $e->getMessage()]);
}
?>
