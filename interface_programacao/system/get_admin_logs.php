<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->query("SELECT l.*, u.full_name, u.profile_pic
        FROM audit_logs l
        LEFT JOIN users u ON l.admin_id = u.user_id
        ORDER BY l.created_at DESC
        LIMIT 200");
    echo json_encode(['success' => true, 'logs' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'logs' => []]);
}
