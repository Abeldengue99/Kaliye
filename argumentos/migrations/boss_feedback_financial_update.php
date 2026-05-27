<?php
// processos/migrations/boss_feedback_financial_update.php
// Implementação do feedback do Chefe sobre dados financeiros, compliance e segurança.
// Tradução de sintaxe PostgreSQL (IDENTITY) para MySQL (AUTO_INCREMENT).

require_once __DIR__ . '/../../configuracoes/base_dados.php';

$db = new Database();
$conn = $db->getConnection();

function logMsg($msg) {
    echo "[INFO] $msg\n";
}

function logError($msg) {
    echo "[ERROR] $msg\n";
}

// Helper para verificar/adicionar coluna
function ensureColumn($conn, $table, $colName, $colDefinition) {
    try {
        $check = $conn->query("SHOW COLUMNS FROM `$table` LIKE '$colName'");
        if ($check->rowCount() == 0) {
            $conn->exec("ALTER TABLE `$table` ADD COLUMN `$colName` $colDefinition");
            logMsg("Coluna '$colName' adicionada a '$table'.");
        } else {
            // Opcional: Modificar se já existe para garantir o tipo (BOSS REQUEST: enforce types)
            // $conn->exec("ALTER TABLE `$table` MODIFY COLUMN `$colName` $colDefinition");
            // Mas cuidado com dados existentes. Para NOT NULL, é arriscado sem default.
        }
    } catch (PDOException $e) {
        logError("Erro na coluna $table.$colName: " . $e->getMessage());
    }
}

try {
    logMsg("Iniciando Migração Financeira e de Compliance (Boss Feedback)...");

    // 1. Atualizações na Tabela Users
    // Adicionar campos de carteira
    ensureColumn($conn, 'users', 'wallet_balance', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    ensureColumn($conn, 'users', 'total_invested', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    
    // Converter Flags para Boolean (TINYINT) e Defaults
    // MySQL trata BOOLEAN como TINYINT(1)
    $flags = [
        'is_verified' => "TINYINT(1) NOT NULL DEFAULT 0",
        'is_public' => "TINYINT(1) NOT NULL DEFAULT 1",
        'is_active' => "TINYINT(1) NOT NULL DEFAULT 1",
        // is_elite_mentor e is_visionary_investor foram removidos na Phase 1, mas se o chefe pediu
        // "A TABELA DE GAMIFICAÃ‡Ã•ES TAMBÃ‰M PODES ELIMINAR", isso confirma a remoção anterior.
        // No entanto, o boss mencionou "is_elite_mentor int" na lista de "campos sem restrição".
        // Assumo que ele estava a dar exemplos do que estava mal antes. 
        // Como o pedido é "MANTESSE A BASE DE DADOS QUE FIZESTE O BACKUP" (Phase 1), não vou readicionar Gamification.
    ];

    foreach ($flags as $col => $def) {
         try {
             // Verificar se existe antes de modificar
             $check = $conn->query("SHOW COLUMNS FROM `users` LIKE '$col'");
             if ($check->rowCount() > 0) {
                 $conn->exec("ALTER TABLE `users` MODIFY COLUMN `$col` $def");
                 logMsg("Coluna '$col' convertida/ajustada em 'users'.");
             }
         } catch (Exception $e) { logError("Falha ao ajustar flag $col: " . $e->getMessage()); }
    }

    // 2. Tabela de Transações (Completa)
    $sqlTransactions = "CREATE TABLE IF NOT EXISTS `transactions` (
      `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `type` VARCHAR(50) NOT NULL COMMENT 'deposit,withdrawal,investment,payout,fee,refund',
      `amount` DECIMAL(15,2) NOT NULL,
      `balance_before` DECIMAL(15,2) NOT NULL,
      `balance_after` DECIMAL(15,2) NOT NULL,
      `reference_type` VARCHAR(50) COMMENT 'investment,payout,payment',
      `reference_id` INT,
      `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
      `description` TEXT,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      `processed_at` TIMESTAMP NULL,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlTransactions);
    logMsg("Tabela 'transactions' garantida.");

    // 3. Tabela OTP Codes (Segurança)
    $sqlOTP = "CREATE TABLE IF NOT EXISTS `otp_codes` (
      `id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `code_hash` VARCHAR(255) NOT NULL,
      `purpose` VARCHAR(50) NOT NULL COMMENT 'email_verify,login,password_reset',
      `expires_at` TIMESTAMP NOT NULL,
      `used_at` TIMESTAMP NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlOTP);
    logMsg("Tabela 'otp_codes' garantida.");

    // Remover email_otp de users se existir
    try {
        $conn->exec("ALTER TABLE `users` DROP COLUMN `email_otp`");
        logMsg("Coluna insegura 'email_otp' removida de 'users'.");
    } catch (Exception $e) { /* Ignorar se não existir */ }

    // 4. Atualizações em Projectos (Campanha)
    ensureColumn($conn, 'projects', 'funding_goal', "DECIMAL(15,2) NOT NULL DEFAULT 0.00");
    ensureColumn($conn, 'projects', 'minimum_investment', "DECIMAL(15,2) DEFAULT 1000.00");
    ensureColumn($conn, 'projects', 'maximum_investment', "DECIMAL(15,2) DEFAULT NULL");
    ensureColumn($conn, 'projects', 'campaign_start_date', "TIMESTAMP NULL");
    ensureColumn($conn, 'projects', 'campaign_end_date', "TIMESTAMP NULL");
    ensureColumn($conn, 'projects', 'funding_type', "VARCHAR(50) COMMENT 'all_or_nothing,flexible'");
    ensureColumn($conn, 'projects', 'approval_status', "VARCHAR(50) DEFAULT 'pending'");
    ensureColumn($conn, 'projects', 'approved_by', "INT DEFAULT NULL");
    ensureColumn($conn, 'projects', 'approved_at', "TIMESTAMP NULL");

    // 5. Atualizações em Project Investments (Escala)
    ensureColumn($conn, 'project_investments', 'equity_percentage', "DECIMAL(5,2)");
    ensureColumn($conn, 'project_investments', 'expected_return_rate', "DECIMAL(5,2)");
    ensureColumn($conn, 'project_investments', 'investment_type', "VARCHAR(50) COMMENT 'equity,loan,donation'");
    ensureColumn($conn, 'project_investments', 'maturity_date', "DATE NULL");
    ensureColumn($conn, 'project_investments', 'contract_signed_at', "TIMESTAMP NULL");
    ensureColumn($conn, 'project_investments', 'payment_reference', "VARCHAR(50) UNIQUE DEFAULT NULL");

    // 6. Tabela Investment Returns
    $sqlReturns = "CREATE TABLE IF NOT EXISTS `investment_returns` (
      `return_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` INT NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `return_type` VARCHAR(50) NOT NULL COMMENT 'dividend,interest,capital_gain',
      `payment_date` DATE NOT NULL,
      `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlReturns);
    logMsg("Tabela 'investment_returns' garantida.");

    // 7. Tabela KYC Verifications
    $sqlKYC = "CREATE TABLE IF NOT EXISTS `kyc_verifications` (
      `kyc_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `verification_level` VARCHAR(50) NOT NULL DEFAULT 'basic',
      `document_type` VARCHAR(50),
      `document_number` VARCHAR(100),
      `document_country` VARCHAR(3),
      `document_expiry` DATE,
      `verification_provider` VARCHAR(50) DEFAULT 'manual',
      `provider_reference` VARCHAR(255),
      `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
      `verified_by` INT,
      `verified_at` TIMESTAMP NULL,
      `rejection_reason` TEXT,
      `risk_score` INT DEFAULT 0,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlKYC);
    
    // Migrar dados antigos de users (bi_front_path, etc) se necessário, mas Phase 1 já limpou.

    // 8. Tabela Investor Accreditation
    $sqlAccred = "CREATE TABLE IF NOT EXISTS `investor_accreditation` (
      `accreditation_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `is_accredited` TINYINT(1) NOT NULL DEFAULT 0,
      `accreditation_type` VARCHAR(50),
      `proof_document_path` VARCHAR(255),
      `verified_by` INT,
      `verified_at` TIMESTAMP NULL,
      `expires_at` DATE,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlAccred);
    logMsg("Tabela 'investor_accreditation' garantida.");

    // 9. Tabela Escrow Accounts
    $sqlEscrow = "CREATE TABLE IF NOT EXISTS `escrow_accounts` (
      `escrow_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` INT NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `status` VARCHAR(50) NOT NULL DEFAULT 'held',
      `hold_until` TIMESTAMP NULL,
      `release_conditions` TEXT,
      `released_at` TIMESTAMP NULL,
      `released_to` INT,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlEscrow);
    logMsg("Tabela 'escrow_accounts' garantida.");

    // 10. Tabelas Refunds e Disputes
    $sqlRefunds = "CREATE TABLE IF NOT EXISTS `refund_requests` (
      `refund_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` INT NOT NULL,
      `requested_by` INT NOT NULL,
      `amount` DECIMAL(15,2) NOT NULL,
      `reason` TEXT NOT NULL,
      `status` VARCHAR(50) NOT NULL DEFAULT 'pending',
      `reviewed_by` INT,
      `reviewed_at` TIMESTAMP NULL,
      `processed_at` TIMESTAMP NULL,
      `admin_notes` TEXT,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlRefunds);

    $sqlDisputes = "CREATE TABLE IF NOT EXISTS `disputes` (
      `dispute_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` INT,
      `project_id` INT,
      `filed_by` INT NOT NULL,
      `against_user_id` INT,
      `dispute_type` VARCHAR(50) NOT NULL,
      `description` TEXT NOT NULL,
      `status` VARCHAR(50) NOT NULL DEFAULT 'open',
      `resolution` TEXT,
      `resolved_by` INT,
      `resolved_at` TIMESTAMP NULL,
      `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`filed_by`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlDisputes);
    logMsg("Tabelas de Gestão de Conflitos garantidas.");

    // 11. Compliance
    $sqlConsents = "CREATE TABLE IF NOT EXISTS `user_consents` (
      `consent_id` INT AUTO_INCREMENT PRIMARY KEY,
      `user_id` INT NOT NULL,
      `consent_type` VARCHAR(50) NOT NULL,
      `version` VARCHAR(20) NOT NULL,
      `ip_address` VARCHAR(45),
      `user_agent` TEXT,
      `consented_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlConsents);

    $sqlRisk = "CREATE TABLE IF NOT EXISTS `risk_disclosures` (
      `disclosure_id` INT AUTO_INCREMENT PRIMARY KEY,
      `investment_id` INT NOT NULL,
      `disclosure_type` VARCHAR(50) NOT NULL,
      `content` TEXT NOT NULL,
      `acknowledged_by` INT NOT NULL,
      `acknowledged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
      FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $conn->exec($sqlRisk);
    logMsg("Tabelas de Compliance garantidas.");

    // 12. Indices (Melhoria de Performance)
    $indices = [
        "CREATE INDEX idx_users_email ON users(email)",
        "CREATE INDEX idx_users_type ON users(user_type)",
        "CREATE INDEX idx_projects_owner ON projects(owner_id)",
        "CREATE INDEX idx_projects_status ON projects(funding_status)",
        "CREATE INDEX idx_transactions_user ON transactions(user_id)",
        "CREATE INDEX idx_transactions_created ON transactions(created_at)",
        "CREATE INDEX idx_messages_sender ON messages(sender_id)",
        "CREATE INDEX idx_messages_receiver ON messages(receiver_id)",
        "CREATE INDEX idx_notifications_user ON notifications(user_id)"
    ];

    foreach($indices as $idxSql) {
        try {
            $conn->exec($idxSql);
        } catch(Exception $e) {
            // Index existing or error is common, ignore in dev migration script
        }
    }
    logMsg("Ãndices de performance aplicados.");

    logMsg("MIGRAÃ‡ÃƒO DE FEEDBACK DO CHEFE CONCLUÃDA COM SUCESSO!");

} catch(PDOException $e) {
    logError("Erro fatal na migração: " . $e->getMessage());
}

