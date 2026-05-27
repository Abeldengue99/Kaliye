<?php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json; charset=utf-8');
requireAdmin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit();
}

$action = $_POST['action'] ?? '';
$id = isset($_POST['id']) ? (int)$_POST['id'] : 0;
$adminId = (int)($_SESSION['user_id'] ?? 0);

try {
    $db = (new Database())->getConnection();
    ChatSecurity::ensureSafetyTables($db);

    if ($action === 'resolve_report' && $id > 0) {
        $stmt = $db->prepare("UPDATE chat_reports SET status = 'reviewed' WHERE report_id = ?");
        $stmt->execute([$id]);
        ChatSecurity::logChatEvent($db, $adminId, null, 'admin_reviewed_chat_report', 'info', ['report_id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Denuncia marcada como analisada.']);
        exit();
    }

    if ($action === 'dismiss_report' && $id > 0) {
        $stmt = $db->prepare("UPDATE chat_reports SET status = 'dismissed' WHERE report_id = ?");
        $stmt->execute([$id]);
        ChatSecurity::logChatEvent($db, $adminId, null, 'admin_dismissed_chat_report', 'info', ['report_id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Denuncia arquivada.']);
        exit();
    }

    if ($action === 'unblock' && $id > 0) {
        $stmt = $db->prepare("DELETE FROM chat_blocks WHERE block_id = ?");
        $stmt->execute([$id]);
        ChatSecurity::logChatEvent($db, $adminId, null, 'admin_removed_chat_block', 'warning', ['block_id' => $id]);
        echo json_encode(['success' => true, 'message' => 'Bloqueio removido.']);
        exit();
    }

    echo json_encode(['success' => false, 'message' => 'Acao invalida.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Falha ao processar acao.']);
}
