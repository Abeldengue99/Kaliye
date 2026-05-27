<?php
/**
 * interface_programacao/social/get_doubt_detail.php
 * SOLUÇÃO DEFINITIVA DE ESTABILIZAÇÃO
 */
ob_start(); 
error_reporting(0); // Suprimimos avisos para garantir que o JSON não seja corrompido.
session_start();

// Caminho absoluto para evitar falhas de inclusão em diferentes ambientes de servidor.
require_once dirname(__DIR__, 2) . '/configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_GET['doubt_id'])) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'ID inválido']);
    exit();
}

$doubt_id = (int)$_GET['doubt_id'];
$database = new Database();
$db = $database->getConnection();

try {
    $types = [
        'student' => 'Estudante',
        'univ_student' => 'Estudante Universitário',
        'high_student' => 'Estudante de Ensino Médio',
        'mentor' => 'Mentor',
        'investor' => 'Investidor',
        'admin' => 'Administrador'
    ];

    // 1. Dúvida Principal - Query Otimizada (Rule #4)
    $stmt = $db->prepare("SELECT d.*, u.full_name, u.profile_pic, u.user_type, u.mentorship_status 
                          FROM doubts d JOIN users u ON d.user_id = u.user_id 
                          WHERE d.doubt_id = ?");
    $stmt->execute([$doubt_id]);
    $doubt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$doubt) {
        if (ob_get_length()) ob_clean();
        echo json_encode(['success' => false, 'message' => 'Dúvida inexistente']);
        exit();
    }

    $doubt['user_type_label'] = $types[$doubt['user_type']] ?? $doubt['user_type'];

    // 2. Comentários - SQL robusto com COALESCE para evitar erros de coluna NULL
    $c_stmt = $db->prepare("SELECT c.comment_id, c.doubt_id, c.user_id, c.parent_id, c.content,
                                   c.created_at, COALESCE(c.is_helpful, false) AS is_helpful,
                                   COALESCE(c.helpful_count, 0) AS helpful_count,
                                   u.full_name, u.profile_pic, u.user_type, u.mentorship_status
                            FROM doubt_comments c 
                            JOIN users u ON c.user_id = u.user_id 
                            WHERE c.doubt_id = ? 
                            ORDER BY COALESCE(c.is_helpful, false) DESC, c.created_at ASC");
    $c_stmt->execute([$doubt_id]);
    $comments = $c_stmt->fetchAll(PDO::FETCH_ASSOC);

    foreach($comments as &$c) {
        $c['user_type_label'] = $types[$c['user_type']] ?? $c['user_type'];
        $c['user_voted'] = 0;
        $c['is_helpful'] = (bool)$c['is_helpful'];
        $c['helpful_count'] = (int)$c['helpful_count'];
    }

    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => true, 'doubt' => $doubt, 'comments' => $comments]);

} catch (Exception $e) {
    if (ob_get_length()) ob_clean();
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
}
?>
