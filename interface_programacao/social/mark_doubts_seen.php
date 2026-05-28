<?php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Não autenticado.']);
    exit();
}
requireValidCSRFTokenJson();

try {
    $database = new Database();
    $db = $database->getConnection();
    $user_id = (int)$_SESSION['user_id'];

    $db->exec("CREATE TABLE IF NOT EXISTS user_doubt_views (
        user_id INTEGER PRIMARY KEY,
        last_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
    )");

    $update = $db->prepare("UPDATE user_doubt_views SET last_seen_at = CURRENT_TIMESTAMP WHERE user_id = ?");
    $update->execute([$user_id]);

    if ($update->rowCount() === 0) {
        $insert = $db->prepare("INSERT INTO user_doubt_views (user_id, last_seen_at) VALUES (?, CURRENT_TIMESTAMP)");
        $insert->execute([$user_id]);
    }

    if (isset($_SESSION['header_counts'])) {
        $_SESSION['header_counts']['doubts'] = 0;
    }

    echo json_encode(['success' => true]);
} catch (Throwable $e) {
    error_log('mark_doubts_seen error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao atualizar leitura.']);
}
