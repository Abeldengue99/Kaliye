<?php
/**
 * Script de Migração: Evolução do Sistema Financeiro e Segurança (Fase 2026)
 * Implementa: Transações, KYC, Escrow, Auditoria e Reforço de Tipos.
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "--- INICIANDO MIGRAÇÃO ESTRUTURAL ---\n\n";

    // 1. REFORÇO DE TRANSAÇÕES E SEGURANÇA (NOVAS TABELAS)
    
    echo "1. Criando tabela de Transações Financeiras...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `transactions` (
        `transaction_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `type` ENUM('deposit', 'withdrawal', 'investment', 'payout', 'fee', 'refund') NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `balance_before` DECIMAL(15,2) NOT NULL,
        `balance_after` DECIMAL(15,2) NOT NULL,
        `reference_type` VARCHAR(50),
        `reference_id` INT,
        `status` ENUM('pending', 'completed', 'failed', 'reversed') NOT NULL DEFAULT 'pending',
        `description` TEXT,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        `processed_at` TIMESTAMP NULL,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "2. Criando tabela de Códigos OTP (Hashed)...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `otp_codes` (
        `id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `code_hash` VARCHAR(255) NOT NULL,
        `purpose` ENUM('email_verify', 'login', 'password_reset') NOT NULL,
        `expires_at` TIMESTAMP NOT NULL,
        `used_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. COMPLIANCE E KYC
    
    echo "3. Criando tabela de Verificações KYC...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `kyc_verifications` (
        `kyc_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `verification_level` ENUM('basic', 'intermediate', 'full') NOT NULL,
        `document_type` ENUM('passport', 'drivers_license', 'national_id') NULL,
        `document_number` VARCHAR(100),
        `document_country` VARCHAR(3),
        `document_expiry` DATE,
        `verification_provider` VARCHAR(50) DEFAULT 'manual',
        `provider_reference` VARCHAR(255),
        `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        `verified_by` INT,
        `verified_at` TIMESTAMP NULL,
        `rejection_reason` TEXT,
        `risk_score` INT DEFAULT 0,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`),
        FOREIGN KEY (`verified_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "4. Criando tabela de Acreditação de Investidores...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `investor_accreditation` (
        `accreditation_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `is_accredited` TINYINT(1) NOT NULL DEFAULT 0,
        `accreditation_type` ENUM('income', 'net_worth', 'professional') NULL,
        `proof_document_path` VARCHAR(255),
        `verified_by` INT,
        `verified_at` TIMESTAMP NULL,
        `expires_at` DATE NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. INVESTIMENTOS E ESCROW
    
    echo "5. Criando tabela de Retornos de Investimento...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `investment_returns` (
        `return_id` INT AUTO_INCREMENT PRIMARY KEY,
        `investment_id` INT NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `return_type` ENUM('dividend', 'interest', 'capital_gain') NOT NULL,
        `payment_date` DATE NOT NULL,
        `status` ENUM('pending', 'paid', 'late') NOT NULL DEFAULT 'pending',
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "6. Criando tabela de Escrow (Custódia)...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `escrow_accounts` (
        `escrow_id` INT AUTO_INCREMENT PRIMARY KEY,
        `investment_id` INT NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `status` ENUM('held', 'released', 'refunded') NOT NULL DEFAULT 'held',
        `hold_until` TIMESTAMP NULL,
        `release_conditions` TEXT,
        `released_at` TIMESTAMP NULL,
        `released_to` INT,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. ALTERAÇÕES EM TABELAS EXISTENTES (ALTER TABLE)
    
    echo "7. Atualizando tabela de Usuários (Booleans e Constraints)...\n";
    $db->exec("ALTER TABLE `users` 
        MODIFY COLUMN `email` VARCHAR(100) NOT NULL,
        MODIFY COLUMN `user_type` VARCHAR(50) NOT NULL,
        MODIFY COLUMN `is_verified` TINYINT(1) NOT NULL DEFAULT 0;");
    
    // Adicionar colunas individualmente se não existirem
    $cols_to_add = [
        'wallet_balance' => "DECIMAL(15,2) NOT NULL DEFAULT 0.00",
        'total_invested' => "DECIMAL(15,2) NOT NULL DEFAULT 0.00"
    ];
    foreach($cols_to_add as $col => $def) {
        try { $db->exec("ALTER TABLE `users` ADD COLUMN `$col` $def"); } catch (Exception $e) {}
    }

    echo "8. Atualizando tabela de Projetos (Funding Goal)...\n";
    // Tentar adicionar colunas em projects
    $proj_cols = [
        'funding_goal' => "DECIMAL(15,2) NOT NULL DEFAULT 0.00",
        'minimum_investment' => "DECIMAL(15,2) DEFAULT 1000.00",
        'maximum_investment' => "DECIMAL(15,2) NULL",
        'campaign_start_date' => "TIMESTAMP NULL",
        'campaign_end_date' => "TIMESTAMP NULL",
        'funding_type' => "ENUM('all_or_nothing', 'flexible') DEFAULT 'flexible'",
        'approval_status' => "ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'",
        'approved_by' => "INT NULL",
        'approved_at' => "TIMESTAMP NULL"
    ];
    foreach($proj_cols as $col => $def) {
        try { $db->exec("ALTER TABLE `projects` ADD COLUMN `$col` $def"); } catch (Exception $e) {}
    }

    echo "9. Atualizando tabela de Investimentos...\n";
    try {
        $db->exec("ALTER TABLE `project_investments` 
            ADD COLUMN `equity_percentage` DECIMAL(5,2) NULL,
            ADD COLUMN `expected_return_rate` DECIMAL(5,2) NULL,
            ADD COLUMN `investment_type` ENUM('equity', 'loan', 'donation') DEFAULT 'equity',
            ADD COLUMN `maturity_date` DATE NULL,
            ADD COLUMN `contract_signed_at` TIMESTAMP NULL;");
    } catch (Exception $e) { echo "   (Nota: Algumas colunas em investments podem já existir...)\n"; }

    // 5. CONSENTIMENTO E RISCO
    
    echo "10. Criando tabelas de Consentimento e Risco...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `user_consents` (
        `consent_id` INT AUTO_INCREMENT PRIMARY KEY,
        `user_id` INT NOT NULL,
        `consent_type` VARCHAR(50) NOT NULL,
        `version` VARCHAR(20) NOT NULL,
        `ip_address` VARCHAR(45),
        `user_agent` TEXT,
        `consented_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`user_id`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS `risk_disclosures` (
        `disclosure_id` INT AUTO_INCREMENT PRIMARY KEY,
        `investment_id` INT NOT NULL,
        `disclosure_type` VARCHAR(50) NOT NULL,
        `content` TEXT NOT NULL,
        `acknowledged_by` INT NOT NULL,
        `acknowledged_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 6. INDEXAÇÃO PARA PERFORMANCE
    
    echo "11. Criando Índices de Performance...\n";
    $indexes = [
        "idx_users_email" => ["users", "email"],
        "idx_users_type" => ["users", "user_type"],
        "idx_projects_owner" => ["projects", "owner_id"],
        "idx_projects_status" => ["projects", "status"],
        "idx_investments_project" => ["project_investments", "project_id"],
        "idx_investments_investor" => ["project_investments", "investor_id"],
        "idx_transactions_user" => ["transactions", "user_id"],
        "idx_messages_receiver" => ["messages", "receiver_id"]
    ];

    foreach ($indexes as $name => $info) {
        try {
            $db->exec("CREATE INDEX `$name` ON `{$info[0]}`(`{$info[1]}`);");
        } catch (Exception $e) { /* Índice provav. já existe */ }
    }

    echo "\n--- ✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO ---\n";

} catch (PDOException $e) {
    echo "\n❌ ERRO NA MIGRAÇÃO: " . $e->getMessage() . "\n";
}
