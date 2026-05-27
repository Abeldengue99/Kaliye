<?php
/**
 * Report suspicious or abusive chat behavior.
 */
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Sessao expirada.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodo invalido.']);
    exit();
}

$reporterId = (int)$_SESSION['user_id'];
$reportedId = isset($_POST['reported_user_id']) ? (int)$_POST['reported_user_id'] : 0;
$messageId = isset($_POST['message_id']) && $_POST['message_id'] !== '' ? (int)$_POST['message_id'] : null;
$scope = ChatSecurity::normalizeText($_POST['scope'] ?? 'direct');
$category = ChatSecurity::normalizeText($_POST['category'] ?? 'other');
$details = ChatSecurity::normalizeText($_POST['details'] ?? '');

try {
    $db = (new Database())->getConnection();
    if (ChatSecurity::reportUser($db, $reporterId, $reportedId, $messageId, $scope, $category, $details)) {
        echo json_encode(['success' => true, 'message' => 'Denuncia registada para analise.']);
        exit();
    }
    echo json_encode(['success' => false, 'error' => 'Nao foi possivel registar a denuncia.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Falha ao registar denuncia.']);
}
