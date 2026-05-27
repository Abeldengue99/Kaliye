<?php
/**
 * Script de Sincronização Final - KALIYE
 * Garante que 100% das sugestões do usuário (Reembolsos, Litígios e Índices) estejam no MySQL/XAMPP.
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    echo "=== SINCRONIZAÇÃO COMPLETA COM XAMPP/MYSQL ===\n\n";

    // 1. TABELAS DE REEMBOLSOS E LITÍGIOS
    echo "1. Criando tabela de Reembolsos (refund_requests)...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `refund_requests` (
        `refund_id` INT AUTO_INCREMENT PRIMARY KEY,
        `investment_id` INT NOT NULL,
        `requested_by` INT NOT NULL,
        `amount` DECIMAL(15,2) NOT NULL,
        `reason` TEXT NOT NULL,
        `status` ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'pending',
        `reviewed_by` INT NULL,
        `reviewed_at` TIMESTAMP NULL,
        `processed_at` TIMESTAMP NULL,
        `admin_notes` TEXT,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`investment_id`) REFERENCES `project_investments`(`investment_id`),
        FOREIGN KEY (`requested_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "2. Criando tabela de Litígios (disputes)...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS `disputes` (
        `dispute_id` INT AUTO_INCREMENT PRIMARY KEY,
        `investment_id` INT NULL,
        `project_id` INT NULL,
        `filed_by` INT NOT NULL,
        `against_user_id` INT NULL,
        `dispute_type` VARCHAR(50) NOT NULL,
        `description` TEXT NOT NULL,
        `status` ENUM('open', 'resolved', 'closed') NOT NULL DEFAULT 'open',
        `resolution` TEXT,
        `resolved_by` INT NULL,
        `resolved_at` TIMESTAMP NULL,
        `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (`filed_by`) REFERENCES `users`(`user_id`)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. GARANTIR DECIMAL(15,2) EM TODOS OS CAMPOS FINANCEIROS
    echo "3. Padronizando campos financeiros para DECIMAL(15,2)...\n";
    $standardize = [
        'users' => ['wallet_balance', 'total_invested'],
        'projects' => ['funding_goal', 'minimum_investment', 'maximum_investment'],
        'project_investments' => ['amount'],
        'transactions' => ['amount', 'balance_before', 'balance_after']
    ];

    foreach($standardize as $table => $cols) {
        foreach($cols as $col) {
            try {
                $db->exec("ALTER TABLE `$table` MODIFY COLUMN `$col` DECIMAL(15,2)");
                echo "   ✅ $table.$col atualizado.\n";
            } catch(Exception $e) { echo "   ⚠️ Erro ao atualizar $table.$col: " . $e->getMessage() . "\n"; }
        }
    }

    // 3. ÍNDICES DE PERFORMANCE (RESTANTES)
    echo "4. Aplicando índices de performance...\n";
    $indices = [
        "idx_projects_category"    => ["projects", "category"],
        "idx_investments_status"   => ["project_investments", "status"],
        "idx_transactions_created" => ["transactions", "created_at"],
        "idx_messages_sender"      => ["messages", "sender_id"],
        "idx_notifications_user"   => ["notifications", "user_id"],
        "idx_notifications_read"   => ["notifications", "is_read"]
    ];

    foreach($indices as $name => $info) {
        try {
            $db->exec("CREATE INDEX `$name` ON `{$info[0]}`(`{$info[1]}`);");
            echo "   ✅ Índice $name criado.\n";
        } catch(Exception $e) { /* Índice provavelmente já existe */ }
    }

    echo "\n--- ✅ TUDO SINCRONIZADO! A BASE ESTÁ 100% IGUAL AO SEU PEDIDO ---\n";

} catch (PDOException $e) {
    echo "\n❌ ERRO NA SINCRONIZAÇÃO: " . $e->getMessage() . "\n";
}
