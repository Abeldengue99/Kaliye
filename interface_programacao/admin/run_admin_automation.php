<?php
@session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/AdminAutomation.php';

header('Content-Type: application/json');

if (!isAdmin() || !hasPermission('settings')) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit();
}

requireValidCSRFTokenJson();

try {
    $db = (new Database())->getConnection();
    $automation = new AdminAutomation($db, (int)($_SESSION['user_id'] ?? 0));
    $dryRun = in_array(strtolower((string)($_POST['dry_run'] ?? '0')), ['1', 'true', 'yes', 'on'], true);
    $result = $automation->run($dryRun);
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'Erro na automacao: ' . $e->getMessage()]);
}
?>
