<?php
// interface_programacao/admin/toggle_badge.php
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit();
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$status = isset($_POST['status']) ? (int)$_POST['status'] : 0;

if (!$user_id) {
    echo json_encode(['success' => false, 'message' => 'ID inválido.']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $stmt = $db->prepare("UPDATE users SET badge_verified = ? WHERE user_id = ?");
    $stmt->execute([$status, $user_id]);

    echo json_encode([
        'success' => true, 
        'message' => 'Selo ' . ($status ? 'atribuído' : 'removido') . ' com sucesso.'
    ]);
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}

