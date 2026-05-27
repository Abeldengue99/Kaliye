<?php
// migrate_elite_phase2.php
require_once 'configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. CERTIFICATES
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_certificates (
        cert_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        path_id INT NOT NULL,
        cert_code VARCHAR(50) UNIQUE NOT NULL,
        issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (path_id) REFERENCES mentorship_paths(path_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. GROUP SESSIONS (FOCUS MODE)
    // Update existing sessions to support group type
    $db->exec("ALTER TABLE mentorship_sessions 
        ADD COLUMN IF NOT EXISTS is_group TINYINT(1) DEFAULT 0,
        ADD COLUMN IF NOT EXISTS max_capacity INT DEFAULT 1,
        ADD COLUMN IF NOT EXISTS meeting_type ENUM('one_on_one', 'group') DEFAULT 'one_on_one';");

    // Join table for group session participants
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_session_participants (
        participation_id INT AUTO_INCREMENT PRIMARY KEY,
        session_id INT NOT NULL,
        student_id INT NOT NULL,
        joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (session_id) REFERENCES mentorship_sessions(session_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
        UNIQUE KEY (session_id, student_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    echo "Elite Phase 2 tables and columns created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
