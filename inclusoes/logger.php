<?php
// includes/logger.php

if (!function_exists('logActivity')) {
    function logActivity($db, $user_id, $action, $details = '') {
        try {
            $ip = $_SERVER['REMOTE_ADDR'] ?? 'Unknown';
            $stmt = $db->prepare("INSERT INTO activity_logs (user_id, action, ip_address, details) VALUES (?, ?, ?, ?)");
            $stmt->execute([$user_id, $action, $ip, $details]);
        } catch (PDOException $e) {
            // Silently fail logging if error, don't break app
            error_log("Activity Log Error: " . $e->getMessage());
        }
    }
}
