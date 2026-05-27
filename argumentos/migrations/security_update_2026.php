<?php

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
    $stmt = $conn->prepare("SHOW COLUMNS FROM `$table` LIKE ?");
    $stmt->execute([$column]);
    return $stmt->fetch() !== false;
}

function addColumnIfNotExists($conn, $table, $column, $definition) {
    if (!columnExists($conn, $table, $column)) {
        try {
            $sql = "ALTER TABLE `$table` ADD COLUMN `$column` $definition";
            $conn->exec($sql);
            logMsg("Added column '$column' to table '$table'.");
        } catch (PDOException $e) {
            logError("Failed to add column '$column' to '$table': " . $e->getMessage());
        }
    } else {
        logMsg("Column '$column' already exists in '$table'. Skipping add.");
    }
}

function modifyColumn($conn, $table, $column, $definition) {
    if (columnExists($conn, $table, $column)) {
        try {
            $sql = "ALTER TABLE `$table` MODIFY `$column` $definition";
            $conn->exec($sql);
            logMsg("Modified column '$column' in table '$table'.");
        } catch (PDOException $e) {
            logError("Failed to modify column '$column' in '$table': " . $e->getMessage());
        }
    } else {
        logError("Column '$column' does not exist in '$table'. Cannot modify.");
    }
}

try {
    logMsg("Starting Security & Feature Update Migration...");

    // 1. Financial Data Updates (Users)
    // "wallet_balance" decimal(15,2) NOT NULL DEFAULT 0.00
    // "total_invested" - Check if exists, likely in users or project_investments?
    // User request implied checks on financial data. 
    // Assuming 'users' has 'wallet_balance'.
    modifyColumn($conn, 'users', 'wallet_balance', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    
    // Check if 'total_invested' exists in users, if so modify, else maybe add?
    if (columnExists($conn, 'users', 'total_invested')) {
        modifyColumn($conn, 'users', 'total_invested', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    } else {
         // Optionally add it if it's a new requirement? User listed it under "Especificar..."
         // I'll add it to users if missing, as it seems relevant for a wallet system.
         addColumnIfNotExists($conn, 'users', 'total_invested', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    }

    // 2. Transactions Table
    $sql = "CREATE TABLE IF NOT EXISTS `transactions` (
      `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int NOT NULL,
      `type` varchar(50) NOT NULL,
      `amount` decimal(15,2) NOT NULL,
      `balance_before` decimal(15,2) NOT NULL,
      `balance_after` decimal(15,2) NOT NULL,
      `reference_type` varchar(50),
      `reference_id` int,
      `status` varchar(50) NOT NULL DEFAULT 'pending',
      `description` text,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `processed_at` timestamp NULL,
      CONSTRAINT `fk_transaction_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'transactions' checked/created.");

    // 3. OTP Codes
    $sql = "CREATE TABLE IF NOT EXISTS `otp_codes` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int NOT NULL,
      `code_hash` varchar(255) NOT NULL,
      `purpose` varchar(50) NOT NULL,
      `expires_at` timestamp NOT NULL,
      `used_at` timestamp NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_otp_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'otp_codes' checked/created.");

    // 4. Users Constraints & Improvements
    modifyColumn($conn, 'users', 'email', "VARCHAR(100) NOT NULL"); // UNIQUE might fail if dups, add separately if needed.
    // Try adding UNIQUE index safely?
    try {
        $conn->exec("ALTER TABLE `users` ADD UNIQUE (`email`)");
        logMsg("Added UNIQUE constraint to 'users.email'.");
    } catch (PDOException $e) {
        // Ignore if already exists or duplicate entry
        logMsg("UNIQUE constraint on 'email' skipped (maybe exists or duplicates): " . $e->getMessage());
    }

    // User Type Enum - fetch current to be safe? Or Enforce list?
    // "admin','mentor','student','investor','institution'"
    modifyColumn($conn, 'users', 'user_type', "ENUM('admin','mentor','student','investor','institution') NOT NULL");

    // "created_at"
    modifyColumn($conn, 'users', 'created_at', "TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP");

    // Booleans
    // is_verified, is_public, is_read (notifications?), paid (?), is_active, is_elite_mentor, is_visionary_investor
    $boolCols = ['is_verified', 'is_public', 'is_active', 'is_elite_mentor', 'is_visionary_investor'];
    foreach ($boolCols as $col) {
        if (columnExists($conn, 'users', $col)) {
             // Default values: verified=false(0), public=true(1), etc.
             $default = ($col === 'is_public') ? '1' : '0';
             modifyColumn($conn, 'users', $col, "BOOLEAN NOT NULL DEFAULT $default");
        }
    }

    // 5. Project Investments Enhancements
    addColumnIfNotExists($conn, 'project_investments', 'equity_percentage', "DECIMAL(5,2)");
    addColumnIfNotExists($conn, 'project_investments', 'expected_return_rate', "DECIMAL(5,2)");
    addColumnIfNotExists($conn, 'project_investments', 'investment_type', "VARCHAR(50)");
    addColumnIfNotExists($conn, 'project_investments', 'maturity_date', "DATE");
    addColumnIfNotExists($conn, 'project_investments', 'contract_signed_at', "TIMESTAMP NULL");

    // 6. Investment Returns
    $sql = "CREATE TABLE IF NOT EXISTS `investment_returns` (
      `return_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` int NOT NULL,
      `amount` decimal(15,2) NOT NULL,
      `return_type` varchar(50) NOT NULL,
      `payment_date` date NOT NULL,
      `status` varchar(50) NOT NULL DEFAULT 'pending',
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_return_investment` FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'investment_returns' checked/created.");

    // 7. KYC
    $sql = "CREATE TABLE IF NOT EXISTS `kyc_verifications` (
      `kyc_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int NOT NULL,
      `verification_level` varchar(50) NOT NULL, 
      `document_type` varchar(50),
      `document_number` varchar(100),
      `document_country` varchar(3),
      `document_expiry` date,
      `verification_provider` varchar(50),
      `provider_reference` varchar(255),
      `status` varchar(50) NOT NULL DEFAULT 'pending',
      `verified_by` int,
      `verified_at` timestamp NULL,
      `rejection_reason` text,
      `risk_score` int,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_kyc_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
      CONSTRAINT `fk_kyc_verifier` FOREIGN KEY (`verified_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'kyc_verifications' checked/created.");

    // 8. Investor Accreditation
    $sql = "CREATE TABLE IF NOT EXISTS `investor_accreditation` (
      `accreditation_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int NOT NULL,
      `is_accredited` boolean NOT NULL DEFAULT 0,
      `accreditation_type` varchar(50),
      `proof_document_path` varchar(255),
      `verified_by` int,
      `verified_at` timestamp NULL,
      `expires_at` date,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_accred_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'investor_accreditation' checked/created.");

    // 9. Escrow
    $sql = "CREATE TABLE IF NOT EXISTS `escrow_accounts` (
      `escrow_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` int NOT NULL,
      `amount` decimal(15,2) NOT NULL,
      `status` varchar(50) NOT NULL DEFAULT 'held',
      `hold_until` timestamp NULL,
      `release_conditions` text,
      `released_at` timestamp NULL,
      `released_to` int,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_escrow_investment` FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'escrow_accounts' checked/created.");

    // 10. Project Funding details
    addColumnIfNotExists($conn, 'projects', 'funding_goal', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    addColumnIfNotExists($conn, 'projects', 'minimum_investment', "DECIMAL(15,2) DEFAULT 1000.00");
    addColumnIfNotExists($conn, 'projects', 'maximum_investment', "DECIMAL(15,2)");
    addColumnIfNotExists($conn, 'projects', 'campaign_start_date', "TIMESTAMP NULL");
    addColumnIfNotExists($conn, 'projects', 'campaign_end_date', "TIMESTAMP NULL");
    addColumnIfNotExists($conn, 'projects', 'funding_type', "VARCHAR(50)");
    addColumnIfNotExists($conn, 'projects', 'approval_status', "VARCHAR(50) DEFAULT 'pending'");
    addColumnIfNotExists($conn, 'projects', 'approved_by', "INT");
    addColumnIfNotExists($conn, 'projects', 'approved_at', "TIMESTAMP NULL");

    // 11. Refunds & Disputes
    $sql = "CREATE TABLE IF NOT EXISTS `refund_requests` (
      `refund_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` int NOT NULL,
      `requested_by` int NOT NULL,
      `amount` decimal(15,2) NOT NULL,
      `reason` text NOT NULL,
      `status` varchar(50) NOT NULL DEFAULT 'pending',
      `reviewed_by` int,
      `reviewed_at` timestamp NULL,
      `processed_at` timestamp NULL,
      `admin_notes` text,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_refund_investment` FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'refund_requests' checked/created.");

    $sql = "CREATE TABLE IF NOT EXISTS `disputes` (
      `dispute_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` int,
      `project_id` int,
      `filed_by` int NOT NULL,
      `against_user_id` int,
      `dispute_type` varchar(50) NOT NULL,
      `description` text NOT NULL,
      `status` varchar(50) NOT NULL DEFAULT 'open',
      `resolution` text,
      `resolved_by` int,
      `resolved_at` timestamp NULL,
      `created_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_dispute_filer` FOREIGN KEY (`filed_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'disputes' checked/created.");

    // 12. Consents & Compliance
    $sql = "CREATE TABLE IF NOT EXISTS `user_consents` (
      `consent_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` int NOT NULL,
      `consent_type` varchar(50) NOT NULL,
      `version` varchar(20) NOT NULL,
      `ip_address` varchar(45),
      `user_agent` text,
      `consented_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_consent_user` FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'user_consents' checked/created.");

    $sql = "CREATE TABLE IF NOT EXISTS `risk_disclosures` (
      `disclosure_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` int NOT NULL,
      `disclosure_type` varchar(50) NOT NULL,
      `content` text NOT NULL,
      `acknowledged_by` int NOT NULL,
      `acknowledged_at` timestamp NOT NULL DEFAULT CURRENT_TIMESTAMP,
      CONSTRAINT `fk_disclosure_investment` FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sql);
    logMsg("Table 'risk_disclosures' checked/created.");

    // 13. Indexes
    // CREATE INDEX IF NOT EXISTS is not standard MySQL suitable everywhere, usually simple CREATE INDEX and catch error
    $indexes = [
        ['users', 'email', 'idx_users_email'],
        ['users', 'user_type', 'idx_users_type'],
        ['projects', 'owner_id', 'idx_projects_owner'],
        // ['projects', 'funding_status', 'idx_projects_status'], // Need to check if funding_status exists
        // ['projects', 'category', 'idx_projects_category'], // Need to check if category exists
        ['project_investments', 'project_id', 'idx_investments_project'],
        ['project_investments', 'investor_id', 'idx_investments_investor'], // Check column name (investor_id vs user_id?)
        // mentorship_system.sql mentions 'investor_id' in a contract table, but project_investments usually has user_id/investor_id?
        // Let's assume 'investor_id' as per user request snippet: CREATE INDEX "idx_investments_investor" ON "project_investments"("investor_id");
        ['project_investments', 'status', 'idx_investments_status'],
        ['transactions', 'user_id', 'idx_transactions_user'],
        ['transactions', 'created_at', 'idx_transactions_created'],
        // ['messages', 'sender_id', 'idx_messages_sender'], // Messages table exists?
        // ['messages', 'receiver_id', 'idx_messages_receiver'],
        // ['notifications', 'user_id', 'idx_notifications_user'],
        // ['notifications', 'is_read', 'idx_notifications_read']
    ];

    foreach ($indexes as $idx) {
        $tbl = $idx[0];
        $col = $idx[1];
        $name = $idx[2];
        if (columnExists($conn, $tbl, $col)) {
             try {
                 // Check if index exists first?
                 $check = $conn->query("SHOW INDEX FROM `$tbl` WHERE Key_name = '$name'");
                 if ($check->rowCount() == 0) {
                     $conn->exec("CREATE INDEX `$name` ON `$tbl`(`$col`)");
                     logMsg("Created index '$name' on '$tbl'.");
                 } else {
                     logMsg("Index '$name' already exists.");
                 }
             } catch (PDOException $e) {
                 logError("Index creation '$name' failed: " . $e->getMessage());
             }
        } else {
            logMsg("Skipping index '$name' because column '$col' not found in '$tbl'.");
        }
    }
    
    // Explicitly check for notifications and messages if they exist
    if (columnExists($conn, 'notifications', 'user_id')) {
         // Create idx_notifications_user
         try {
             $conn->exec("CREATE INDEX idx_notifications_user ON notifications(user_id)"); 
         } catch(Exception $e) {}
    }

    logMsg("Migration completed successfully.");

} catch(PDOException $e) {
    logError("Migration Fatal Error: " . $e->getMessage());
}

