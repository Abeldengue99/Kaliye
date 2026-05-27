<?php
/**
 * Block a user from direct chat.
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

$blockerId = (int)$_SESSION['user_id'];
$blockedId = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
$reason = ChatSecurity::normalizeText($_POST['reason'] ?? 'manual');

try {
    $db = (new Database())->getConnection();
    if (ChatSecurity::blockUser($db, $blockerId, $blockedId, $reason)) {
        echo json_encode(['success' => true, 'message' => 'Utilizador bloqueado.']);
        exit();
    }
    echo json_encode(['success' => false, 'error' => 'Nao foi possivel bloquear este utilizador.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'error' => 'Falha no bloqueio.']);
}
