<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

try {
    echo "Creating 'payouts' table for financial distribution...\n";
    
    $query = "
        CREATE TABLE IF NOT EXISTS payouts (
            payout_id INT AUTO_INCREMENT PRIMARY KEY,
            investment_id INT NOT NULL,
            project_id INT NOT NULL,
            recipient_id INT NOT NULL,
            role ENUM('student', 'mentor', 'company') NOT NULL,
            amount DECIMAL(15, 2) NOT NULL,
            currency VARCHAR(10) DEFAULT 'AOA',
            description VARCHAR(255) NULL,
            status ENUM('pending', 'paid', 'cancelled') DEFAULT 'pending',
            scheduled_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            paid_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (investment_id) REFERENCES project_investments(investment_id),
            FOREIGN KEY (project_id) REFERENCES projects(project_id),
            FOREIGN KEY (recipient_id) REFERENCES users(user_id)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
    ";

    $db->exec($query);
    echo "Table 'payouts' created successfully.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
