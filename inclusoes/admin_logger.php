<?php
// includes/admin_logger.php

function logAdminAction($db, $admin_id, $action, $details) {
    try {
        $stmt = $db->prepare("INSERT INTO audit_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
        return $stmt->execute([$admin_id, $action, $details]);
    } catch (Exception $e) {
        // Fail silently but maybe error_log it
        error_log("Failed to log admin action: " . $e->getMessage());
        return false;
    }
}
?>
