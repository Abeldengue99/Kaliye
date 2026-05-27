<?php
/**
 * interface_programacao/admin/get_badge_counts.php
 * Endpoint for real-time admin badge updates.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isAdmin()) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

$database = new Database();
$db = $database->getConnection();

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
    $counts['mentors'] = $db->query("SELECT COUNT(*) FROM users WHERE mentor_status = 'pending'")->fetchColumn();
    $counts['investments'] = $db->query("SELECT COUNT(*) FROM project_investments WHERE status = 'pending'")->fetchColumn();
    $counts['support'] = $db->query("SELECT COUNT(*) FROM support_messages WHERE CAST(is_read AS INTEGER) = 0")->fetchColumn();
    $counts['progress'] = $db->query("SELECT COUNT(*) FROM project_progress_reports WHERE report_status = 'pending_admin'")->fetchColumn();
    
    try {
        $counts['moderation'] = $db->query("SELECT COUNT(*) FROM projects WHERE approval_status = 'pending'")->fetchColumn();
    } catch (Exception $e) {
        $counts['moderation'] = $db->query("SELECT COUNT(*) FROM projects WHERE is_public = false")->fetchColumn();
    }
    try {
        $counts['chat_reports'] = $db->query("SELECT COUNT(*) FROM chat_reports WHERE status = 'pending'")->fetchColumn();
    } catch (Exception $e) {}

    echo json_encode(['success' => true, 'counts' => $counts]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
