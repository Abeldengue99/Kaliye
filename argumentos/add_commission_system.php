<?php
// processos/add_commission_system.php
// Adiciona colunas para sistema de comissões da Aksanti
require_once dirname(__DIR__) . '/configuracoes/base_dados.php';
$db = (new Database())->getConnection();

echo "=== ADICIONANDO SISTEMA DE COMISSÃ•ES ===\n\n";

try {
    // 1. Adicionar coluna de comissão da Aksanti na tabela project_investments
    echo "1. Adicionando coluna aksanti_commission_rate...\n";
    $db->exec("ALTER TABLE project_investments 
               ADD COLUMN IF NOT EXISTS aksanti_commission_rate DECIMAL(5,2) DEFAULT 20.00 
               COMMENT 'Percentagem de comissão da Aksanti (padrão 20%)'");
    echo "   âœ… Coluna aksanti_commission_rate adicionada.\n";
    
    echo "2. Adicionando coluna aksanti_commission_amount...\n";
    $db->exec("ALTER TABLE project_investments 
               ADD COLUMN IF NOT EXISTS aksanti_commission_amount DECIMAL(15,2) DEFAULT 0.00 
               COMMENT 'Valor calculado da comissão da Aksanti'");
    echo "   âœ… Coluna aksanti_commission_amount adicionada.\n";
    
    echo "3. Adicionando coluna mentor_commission_rate...\n";
    $db->exec("ALTER TABLE project_investments 
               ADD COLUMN IF NOT EXISTS mentor_commission_rate DECIMAL(5,2) DEFAULT 0.00 
               COMMENT 'Percentagem de comissão do mentor (se aplicável)'");
    echo "   âœ… Coluna mentor_commission_rate adicionada.\n";
    
    echo "4. Adicionando coluna mentor_commission_amount...\n";
    $db->exec("ALTER TABLE project_investments 
               ADD COLUMN IF NOT EXISTS mentor_commission_amount DECIMAL(15,2) DEFAULT 0.00 
               COMMENT 'Valor calculado da comissão do mentor'");
    echo "   âœ… Coluna mentor_commission_amount adicionada.\n";
    
    echo "5. Adicionando coluna net_amount_to_project...\n";
    $db->exec("ALTER TABLE project_investments 
               ADD COLUMN IF NOT EXISTS net_amount_to_project DECIMAL(15,2) DEFAULT 0.00 
               COMMENT 'Valor líquido que vai para o projeto após comissões'");
    echo "   âœ… Coluna net_amount_to_project adicionada.\n";
    
    // 2. Adicionar flag para indicar se mentor recebe comissão
    echo "\n6. Adicionando coluna mentor_eligible_for_commission na tabela projects...\n";
    $db->exec("ALTER TABLE projects 
               ADD COLUMN IF NOT EXISTS mentor_eligible_for_commission TINYINT(1) DEFAULT 0 
               COMMENT 'Se 1, mentor recebe comissão na conclusão do projeto'");
    echo "   âœ… Coluna mentor_eligible_for_commission adicionada.\n";
    
    echo "7. Adicionando coluna mentor_commission_percentage na tabela projects...\n";
    $db->exec("ALTER TABLE projects 
               ADD COLUMN IF NOT EXISTS mentor_commission_percentage DECIMAL(5,2) DEFAULT 5.00 
               COMMENT 'Percentagem de comissão do mentor sobre investimentos'");
    echo "   âœ… Coluna mentor_commission_percentage adicionada.\n";
    
    // 3. Criar tabela de histórico de comissões
    echo "\n8. Criando tabela commission_history...\n";
    $db->exec("CREATE TABLE IF NOT EXISTS commission_history (
        commission_id INT AUTO_INCREMENT PRIMARY KEY,
        investment_id INT NOT NULL,
        project_id INT NOT NULL,
        mentor_id INT NULL,
        commission_type ENUM('aksanti', 'mentor') NOT NULL,
        commission_rate DECIMAL(5,2) NOT NULL,
        commission_amount DECIMAL(15,2) NOT NULL,
        investment_amount DECIMAL(15,2) NOT NULL,
        status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
        paid_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        notes TEXT NULL,
        FOREIGN KEY (investment_id) REFERENCES project_investments(investment_id) ON DELETE CASCADE,
        FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE SET NULL,
        INDEX idx_commission_type (commission_type),
        INDEX idx_commission_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    echo "   âœ… Tabela commission_history criada.\n";
    
    echo "\n=== SISTEMA DE COMISSÃ•ES CONFIGURADO COM SUCESSO ===\n";
    
} catch (PDOException $e) {
    echo "âŒ Erro: " . $e->getMessage() . "\n";
    exit(1);
}

