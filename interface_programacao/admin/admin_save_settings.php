<?php
/**
 * interface_programacao/admin/admin_save_settings.php - Save Global System Settings
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isAdmin() || !hasPermission('settings')) {
    echo json_encode(['success' => false, 'message' => 'Acesso negado.']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    requireValidCSRFTokenJson();

    $settings = $_POST;
    
    // Checkboxes handling (since they only send if checked)
    $checkboxes = [
        'allow_registrations',
        'maintenance_mode',
        'automation_enabled',
        'automation_kyc_reminders',
        'automation_mentor_reminders',
        'automation_project_reminders',
        'automation_progress_reminders',
        'automation_investment_reminders',
        'automation_support_escalation',
        'automation_archive_notifications',
        'automation_expire_otp_codes',
        'automation_expire_mentorship_slots',
        'automation_dormant_user_alerts',
        'automation_cleanup_history'
    ];
    foreach ($checkboxes as $cb) {
        if (!isset($settings[$cb])) {
            $settings[$cb] = '0';
        }
    }

    try {
        $db->beginTransaction();

        $stmt = $db->prepare("INSERT INTO settings (setting_key, setting_value) 
                              VALUES (?, ?) 
                              ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");

        foreach ($settings as $key => $value) {
            // Guard against saving arbitrary POST data that isn't a setting
            // You might want a whitelist here for safety
            $allowed_keys = [
                'site_name', 'admin_email', 'allow_registrations', 
                'maintenance_mode', 'ai_model', 'gemini_api_key', 
                'platform_fee_percent', 'currency_code', 'platform_iban',
                'automation_enabled',
                'automation_kyc_reminders', 'automation_kyc_hours',
                'automation_mentor_reminders', 'automation_mentor_hours',
                'automation_project_reminders', 'automation_project_hours',
                'automation_progress_reminders', 'automation_progress_hours',
                'automation_investment_reminders', 'automation_investment_hours',
                'automation_support_escalation', 'automation_support_hours',
                'automation_archive_notifications', 'automation_archive_notifications_days',
                'automation_expire_otp_codes',
                'automation_expire_mentorship_slots',
                'automation_dormant_user_alerts', 'automation_dormant_user_days',
                'automation_cleanup_history', 'automation_history_days'
            ];

            if (in_array($key, $allowed_keys)) {
                if (substr($key, -6) === '_hours' || substr($key, -5) === '_days' || $key === 'automation_archive_notifications_days') {
                    $value = (string)max(1, (int)$value);
                }
                $stmt->execute([$key, $value]);
            }
        }

        $db->commit();
        header("Location: ../../administracao/system/settings.php?success=1");
        exit();

    } catch (Exception $e) {
        if ($db->inTransaction()) {
            $db->rollBack();
        }
        echo json_encode(['success' => false, 'message' => 'Erro ao salvar: ' . $e->getMessage()]);
    }
} else {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
}
?>

