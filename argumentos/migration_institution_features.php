<?php
// processos/migration_institution_features.php
require_once '../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

echo "Iniciando migração de funcionalidades institucionais...\n";

try {
    // 1. Table for Internal Hackathons/Challenges
    $sql_challenges = "CREATE TABLE IF NOT EXISTS institution_challenges (
        challenge_id INT AUTO_INCREMENT PRIMARY KEY,
        institution_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        status ENUM('draft', 'active', 'closed') DEFAULT 'draft',
        created_by INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (institution_id) REFERENCES institutions(institution_id) ON DELETE CASCADE
    )";
    $db->exec($sql_challenges);
    echo "âœ” Tabela institution_challenges criada/verificada.\n";

    // 2. Table for School Endorsements (Selo de Mérito)
    $sql_endorsements = "CREATE TABLE IF NOT EXISTS project_endorsements (
        endorsement_id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        institution_id INT NOT NULL,
        endorsed_by INT NOT NULL, -- The school admin user_id
        type ENUM('academic_excellence', 'innovation_award', 'social_impact', 'dean_list') NOT NULL,
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
        FOREIGN KEY (institution_id) REFERENCES institutions(institution_id) ON DELETE CASCADE
    )";
    $db->exec($sql_endorsements);
    echo "âœ” Tabela project_endorsements criada/verificada.\n";

    // 3. Table for Recruitment Opportunities
    $sql_opportunities = "CREATE TABLE IF NOT EXISTS institution_opportunities (
        opportunity_id INT AUTO_INCREMENT PRIMARY KEY,
        institution_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT NOT NULL,
        requirements TEXT,
        external_link VARCHAR(255),
        status ENUM('open', 'closed') DEFAULT 'open',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql_opportunities);
    echo "âœ” Tabela institution_opportunities criada/verificada.\n";

    echo "Migração concluída com sucesso!\n";

} catch (PDOException $e) {
    echo "Erro na migração: " . $e->getMessage() . "\n";
}
?>

