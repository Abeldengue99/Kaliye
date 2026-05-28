<?php
/**
 * interface_programacao/admin/admin_security_logs.php
 * Endpoint para obter logs de NDA e visualizações (Segurança de PI)
 */
session_start();
header('Content-Type: application/json');

require_once '../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

// Validar acesso de Administrador
if (!isset($_SESSION['user_id']) || $_SESSION['user_type'] !== 'admin') {
    echo json_encode(['success' => false, 'error' => 'Acesso negado. Privilégios de Administrador requeridos.']);
    exit();
}

try {
    // Buscar Logs de NDAs
    $stmtNDA = $db->prepare("
        SELECT n.nda_id, n.accepted_at, n.ip_address,
               u.full_name, u.user_type,
               p.title AS project_title, p.content_hash
        FROM project_nda_logs n
        JOIN users u ON n.user_id = u.user_id
        JOIN projects p ON n.project_id = p.project_id
        ORDER BY n.accepted_at DESC
        LIMIT 100
    ");
    $stmtNDA->execute();
    $nda_logs = $stmtNDA->fetchAll(PDO::FETCH_ASSOC);

    // Buscar Logs de Visualizações
    $stmtViews = $db->prepare("
        SELECT v.view_id, v.viewed_at, v.ip_address,
               u.full_name, u.user_type,
               p.title AS project_title
        FROM project_views_log v
        JOIN users u ON v.viewer_id = u.user_id
        JOIN projects p ON v.project_id = p.project_id
        ORDER BY v.viewed_at DESC
        LIMIT 100
    ");
    $stmtViews->execute();
    $view_logs = $stmtViews->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        'success' => true,
        'nda_logs' => $nda_logs,
        'view_logs' => $view_logs
    ]);

} catch (PDOException $e) {
    error_log("Admin Security Logs Error: " . $e->getMessage());
    echo json_encode(['success' => false, 'error' => 'Erro interno do servidor ao obter logs de segurança.']);
}
