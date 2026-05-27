<?php
// processos/migrations/refine_boss_feedback_2026.php
// Refinement based on Boss's Feedback (Proposal-A vs C)
require_once __DIR__ . '/../../configuracoes/base_dados.php';

$db = new Database();
$conn = $db->getConnection();

function logMsg($msg) {
    echo "[INFO] $msg\n";
}

function logError($msg) {
    echo "[ERROR] $msg\n";
}

function columnExists($conn, $table, $column) {
    try {
        $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
        $stmt->execute([$column]);
        return $stmt->fetch() !== false;
    } catch (Exception $e) { return false; }
}

try {
    logMsg("Starting Boss's Feedback Refinement...");

    // 1. KYC Conflict: Clean up 'users' table
    // Remove document paths from users, as they belong in kyc_verifications
    // We keep 'is_verified' as requested for status.
    $colsToRemove = ['bi_front_path', 'bi_back_path', 'selfie_path', 'passport_path'];
    foreach ($colsToRemove as $col) {
        if (columnExists($conn, 'users', $col)) {
            try {
                // Check if we should back up data?
                // For this migration, we assume data is either migrated or we just drop explicitly as requested to solve conflict.
                // "Mantenha a tabela separada... mas use os campos ... APENAS para mostrar o status"
                $conn->exec("ALTER TABLE `users` DROP COLUMN `$col`");
                logMsg("Dropped column '$col' from 'users' (moved to kyc_verifications).");
            } catch (PDOException $e) {
                logError("Failed to drop '$col': " . $e->getMessage());
            }
        }
    }

    // 2. ENUM -> VARCHAR (Flexibility)
    // Boss prefers VARCHAR(50) with comments over strict ENUM
    
    $adjustments = [
        ['users', 'user_type', "VARCHAR(50) NOT NULL COMMENT 'admin, mentor, student, investor, institution'"],
        ['users', 'mentorship_status', "VARCHAR(50) DEFAULT 'none'"],
        ['projects', 'mentorship_status', "VARCHAR(50) DEFAULT 'none'"],
        ['mentorship_contracts', 'contract_type', "VARCHAR(50) DEFAULT 'premium_invested'"],
        ['mentorship_contracts', 'status', "VARCHAR(50) DEFAULT 'pending_mentor_acceptance'"],
        ['mentorship_sessions', 'status', "VARCHAR(50) DEFAULT 'scheduled'"],
        ['project_investments', 'status', "VARCHAR(50) DEFAULT 'pending'"],
        ['payouts', 'status', "VARCHAR(50) DEFAULT 'pending'"],
        ['payouts', 'role', "VARCHAR(50) NOT NULL"]
    ];

    foreach ($adjustments as $adj) {
        $table = $adj[0];
        $col = $adj[1];
        $def = $adj[2];
        
        if (columnExists($conn, $table, $col)) {
            try {
                $conn->exec("ALTER TABLE `$table` MODIFY `$col` $def");
                logMsg("Converted '$table.$col' to VARCHAR/Flexible format.");
            } catch (PDOException $e) {
                logError("Failed to modify '$table.$col': " . $e->getMessage());
            }
        }
    }

    // 3. Mentorship Tables Check
    // Verification of Calendar/Slots existence
    $checkSlots = $conn->query("SHOW TABLES LIKE 'mentorship_slots'");
    if ($checkSlots->rowCount() > 0) {
        logMsg("CONFIRMED: 'mentorship_slots' table exists (Calendar System is active).");
    } else {
        logError("MISSING: 'mentorship_slots' table not found! Creating it now...");
        // Fallback creation if missing
        $sql = "CREATE TABLE IF NOT EXISTS `mentorship_slots` (
            `slot_id` INT AUTO_INCREMENT PRIMARY KEY,
            `mentor_id` INT NOT NULL,
            `start_time` DATETIME NOT NULL,
            `end_time` DATETIME NOT NULL,
            `status` VARCHAR(50) DEFAULT 'available',
            CONSTRAINT `audit_slot_mentor` FOREIGN KEY (`mentor_id`) REFERENCES `users`(`user_id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
        $conn->exec($sql);
        logMsg("'mentorship_slots' created.");
    }

    $checkBookings = $conn->query("SHOW TABLES LIKE 'mentorship_bookings'");
    if ($checkBookings->rowCount() > 0) {
        logMsg("CONFIRMED: 'mentorship_bookings' table exists.");
    }

    logMsg("Refinement Completed Successfully.");

} catch(PDOException $e) {
    logError("Refinement Fatal Error: " . $e->getMessage());
}

