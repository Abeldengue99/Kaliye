<?php
// migrate_path_progress.php
require_once 'configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_path_progress (
        progress_id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL,
        step_id INT NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (enrollment_id) REFERENCES mentorship_path_enrollments(enrollment_id) ON DELETE CASCADE,
        FOREIGN KEY (step_id) REFERENCES mentorship_path_steps(step_id) ON DELETE CASCADE,
        UNIQUE KEY (enrollment_id, step_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Path progress tracking added.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
