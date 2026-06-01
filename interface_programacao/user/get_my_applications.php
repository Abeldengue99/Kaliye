<?php
/**
 * interface_programacao/user/get_my_applications.php
 * Endpoint para recuperar as candidaturas do utilizador
 */
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';

session_start();
if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$user_type = $_SESSION['user_type'] ?? 'student';
$application_type = $_GET['type'] ?? 'all';

$database = new Database();
$db = $database->getConnection();

require_once '../../inclusoes/ProjectWorkflowSchema.php';
ensureProjectMentorshipApplicationsSchema($db);
ensureSpecialistApplicationsSchema($db);
ensureStudentApplicationsSchema($db);
ensureInvestmentApplicationsSchema($db);

try {
    $applications = [];

    // Get mentorship applications (if mentor)
    if (($user_type === 'mentor' || $user_type === 'admin') && ($application_type === 'all' || $application_type === 'mentor')) {
        $mentor_query = "
            SELECT pma.*, p.title as project_title, p.owner_id, u.full_name as owner_name, u.user_type as owner_type
            FROM project_mentorship_applications pma
            JOIN projects p ON p.project_id = pma.project_id
            JOIN users u ON u.user_id = p.owner_id
            WHERE pma.mentor_id = ?
            ORDER BY pma.created_at DESC
        ";
        $mentor_stmt = $db->prepare($mentor_query);
        $mentor_stmt->execute([$user_id]);
        $mentor_apps = $mentor_stmt->fetchAll();
        
        foreach ($mentor_apps as $app) {
            $applications[] = [
                'type' => 'mentorship',
                'application_id' => $app['application_id'],
                'project_id' => $app['project_id'],
                'project_title' => $app['project_title'],
                'owner_name' => $app['owner_name'],
                'owner_type' => $app['owner_type'],
                'status' => $app['status'],
                'created_at' => $app['created_at'],
                'motivation' => $app['motivation']
            ];
        }
    }

    // Get specialist applications (if mentor/specialist)
    if (($user_type === 'mentor' || $user_type === 'admin') && ($application_type === 'all' || $application_type === 'specialist')) {
        $specialist_query = "
            SELECT psa.*, p.title as project_title, p.owner_id, u.full_name as owner_name, u.user_type as owner_type
            FROM project_specialist_applications psa
            JOIN projects p ON p.project_id = psa.project_id
            JOIN users u ON u.user_id = p.owner_id
            WHERE psa.specialist_id = ?
            ORDER BY psa.created_at DESC
        ";
        $specialist_stmt = $db->prepare($specialist_query);
        $specialist_stmt->execute([$user_id]);
        $specialist_apps = $specialist_stmt->fetchAll();
        
        foreach ($specialist_apps as $app) {
            $applications[] = [
                'type' => 'specialist',
                'application_id' => $app['application_id'],
                'project_id' => $app['project_id'],
                'project_title' => $app['project_title'],
                'owner_name' => $app['owner_name'],
                'owner_type' => $app['owner_type'],
                'status' => $app['status'],
                'created_at' => $app['created_at'],
                'motivation' => $app['motivation']
            ];
        }
    }

    // Get student applications (if student)
    if (($user_type === 'student' || $user_type === 'admin') && ($application_type === 'all' || $application_type === 'student')) {
        $student_query = "
            SELECT psa.*, p.title as project_title, p.owner_id, u.full_name as owner_name, u.user_type as owner_type
            FROM project_student_applications psa
            JOIN projects p ON p.project_id = psa.project_id
            JOIN users u ON u.user_id = p.owner_id
            WHERE psa.student_id = ?
            ORDER BY psa.created_at DESC
        ";
        $student_stmt = $db->prepare($student_query);
        $student_stmt->execute([$user_id]);
        $student_apps = $student_stmt->fetchAll();
        
        foreach ($student_apps as $app) {
            $applications[] = [
                'type' => 'student',
                'application_id' => $app['application_id'],
                'project_id' => $app['project_id'],
                'project_title' => $app['project_title'],
                'owner_name' => $app['owner_name'],
                'owner_type' => $app['owner_type'],
                'status' => $app['status'],
                'created_at' => $app['created_at'],
                'motivation' => $app['motivation']
            ];
        }
    }

    // Get investment applications (if investor)
    if (($user_type === 'investor' || $user_type === 'admin') && ($application_type === 'all' || $application_type === 'investment')) {
        $investment_query = "
            SELECT pi.*, p.title as project_title, p.owner_id, u.full_name as owner_name, u.user_type as owner_type
            FROM project_investments pi
            JOIN projects p ON p.project_id = pi.project_id
            JOIN users u ON u.user_id = p.owner_id
            WHERE pi.investor_id = ? AND pi.archived_at IS NULL
            ORDER BY pi.created_at DESC
        ";
        $investment_stmt = $db->prepare($investment_query);
        $investment_stmt->execute([$user_id]);
        $investment_apps = $investment_stmt->fetchAll();
        
        foreach ($investment_apps as $app) {
            $applications[] = [
                'type' => 'investment',
                'application_id' => $app['investment_id'],
                'project_id' => $app['project_id'],
                'project_title' => $app['project_title'],
                'owner_name' => $app['owner_name'],
                'owner_type' => $app['owner_type'],
                'status' => $app['status'],
                'created_at' => $app['created_at'],
                'amount' => $app['amount'],
                'currency' => $app['currency'] ?? 'AOA'
            ];
        }
    }

    // Sort by date (newest first)
    usort($applications, function($a, $b) {
        return strtotime($b['created_at']) - strtotime($a['created_at']);
    });

    echo json_encode([
        'success' => true,
        'applications' => $applications,
        'total_count' => count($applications)
    ]);

} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro: ' . $e->getMessage()]);
    exit;
}
?>
