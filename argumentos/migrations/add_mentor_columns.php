<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

try {
    echo "Adding mentor application columns...\n";
    
    $queries = [
        "ALTER TABLE users ADD COLUMN specialty VARCHAR(100) NULL AFTER academic_info",
        "ALTER TABLE users ADD COLUMN experience_years INT NULL AFTER specialty",
        "ALTER TABLE users ADD COLUMN linkedin_url VARCHAR(255) NULL AFTER experience_years",
        "ALTER TABLE users ADD COLUMN cv_path VARCHAR(255) NULL AFTER linkedin_url",
        "ALTER TABLE users ADD COLUMN mentorship_status ENUM('unsubmitted', 'pending', 'approved', 'rejected') DEFAULT 'unsubmitted' AFTER cv_path"
    ];

    foreach ($queries as $q) {
        try {
            $db->exec($q);
            echo "Executed: $q\n";
        } catch (Exception $e) {
            echo "Skipped (likely exists): " . $e->getMessage() . "\n";
        }
    }

    echo "Optimization complete.\n";

} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
