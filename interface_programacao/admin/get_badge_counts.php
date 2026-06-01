<?php
/**
 * interface_programacao/admin/get_badge_counts.php
 * Endpoint for real-time admin badge updates.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/RetentionMaintenance.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();
(new RetentionMaintenance($db))->ensureSchema();

$counts = [
    'kyc' => 0,
    'mentors' => 0,
    'investments' => 0,
    'support' => 0,
    'moderation' => 0,
    'progress' => 0,
    'chat_reports' => 0
];

try {
    $counts['kyc'] = $db->query("SELECT COUNT(*) FROM users WHERE verification_status = 'pending'")->fetchColumn();
    $counts['mentors'] = $db->query("SELECT COUNT(*) FROM users WHERE (mentor_status = 'pending' OR mentorship_status = 'pending') AND mentor_application_archived_at IS NULL")->fetchColumn();
    $counts['investments'] = $db->query("SELECT COUNT(*) FROM project_investments WHERE status = 'pending' AND archived_at IS NULL")->fetchColumn();
    
    // CORREÇÃO POSTGRESQL: Garantir compatibilidade sem dar datatype mismatch (Tratando como integer ou booleano conforme o banco)
    $counts['support'] = $db->query("SELECT COUNT(*) FROM support_messages WHERE CAST(is_read AS INTEGER) = 0")->fetchColumn();
    $counts['progress'] = $db->query("SELECT COUNT(*) FROM project_progress_reports WHERE report_status = 'pending_admin'")->fetchColumn();
    
    try {
        // CORREÇÃO DA MODERAÇÃO: Aplicação da regra estrita dos 3 estados sincronizados
        $counts['moderation'] = $db->query("
            SELECT COUNT(*) 
            FROM projects 
            WHERE approval_status = 'pending' 
              AND is_public = false 
              AND status = 'pending'
        ")->fetchColumn();
    } catch (Exception $e) {
        // Fallback de segurança caso a tabela ainda tenha dados corrompidos na transição
        $counts['moderation'] = $db->query("SELECT COUNT(*) FROM projects WHERE is_public = false AND approval_status = 'pending'")->fetchColumn();
    }
    
    try {
        $counts['chat_reports'] = $db->query("SELECT COUNT(*) FROM chat_reports WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'counts' => $counts]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
