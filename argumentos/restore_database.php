<?php
require_once 'configuracoes/base_dados.php';

echo "Restoring Database Tables...\n";

$db = (new Database())->getConnection();

try {
    // 1. Announcements (Crucial, index.php depends on it)
    $db->exec("CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        type VARCHAR(20) DEFAULT 'info',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "[+] announcements restored.\n";

    // 2. Institutions
    $db->exec("CREATE TABLE IF NOT EXISTS institutions (
        institution_id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(255) NOT NULL,
        type ENUM('University', 'Institute', 'HighSchool', 'Other') DEFAULT 'University',
        code VARCHAR(50) UNIQUE NULL,
        logo_url VARCHAR(255) NULL,
        location VARCHAR(255) NULL,
        contact_email VARCHAR(255) NULL,
        website VARCHAR(255) NULL,
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )");
    echo "[+] institutions restored.\n";
    
    // Institution sub-tables
    $db->exec("CREATE TABLE IF NOT EXISTS institution_actions (
         id INT AUTO_INCREMENT PRIMARY KEY,
         institution_id INT NOT NULL,
         action_name VARCHAR(255),
         created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    // Re-creating inferred tables for completeness
    $db->exec("CREATE TABLE IF NOT EXISTS institution_challenges (
        challenge_id INT AUTO_INCREMENT PRIMARY KEY,
        institution_id INT NOT NULL,
        title VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    $db->exec("CREATE TABLE IF NOT EXISTS institution_opportunities (
        opportunity_id INT AUTO_INCREMENT PRIMARY KEY,
        institution_id INT NOT NULL,
        title VARCHAR(255),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
     $db->exec("CREATE TABLE IF NOT EXISTS institution_invitations (
        invitation_id INT AUTO_INCREMENT PRIMARY KEY,
        institution_id INT NOT NULL,
        email VARCHAR(255),
        token VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "[+] institution sub-tables restored.\n";

    // 3. Mentorship Features
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_notices (
        notice_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT NOT NULL,
        importance ENUM('normal', 'high') DEFAULT 'normal',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_item_targets (
        target_id INT AUTO_INCREMENT PRIMARY KEY, 
        item_type ENUM('notice', 'resource') NOT NULL, 
        item_id INT NOT NULL, 
        student_id INT NOT NULL, 
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP, 
        INDEX (item_type, item_id), 
        INDEX (student_id)
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_acknowledgments (
        ack_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        item_type VARCHAR(50),
        item_id INT,
        acknowledged_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "[+] mentorship tables restored.\n";

    // 4. Paths (Trilhas)
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_paths (
        path_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_path_steps (
        step_id INT AUTO_INCREMENT PRIMARY KEY,
        path_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        sort_order INT DEFAULT 0,
        FOREIGN KEY (path_id) REFERENCES mentorship_paths(path_id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_path_enrollments (
        enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
        path_id INT NOT NULL,
        student_id INT NOT NULL,
        status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
        progress_pct INT DEFAULT 0,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (path_id) REFERENCES mentorship_paths(path_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_path_progress (
        progress_id INT AUTO_INCREMENT PRIMARY KEY,
        enrollment_id INT NOT NULL,
        step_id INT NOT NULL,
        completed_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "[+] mentorship paths restored.\n";

    // 5. Certificates
    $db->exec("CREATE TABLE IF NOT EXISTS certificates (
        certificate_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT,
        project_id INT,
        institution_id INT,
        certificate_type ENUM('merit', 'innovation', 'completion') DEFAULT 'merit',
        certificate_code VARCHAR(50) UNIQUE,
        issued_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_certificates (
        cert_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        path_id INT NOT NULL,
        cert_code VARCHAR(50) UNIQUE NOT NULL,
        issue_date TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (path_id) REFERENCES mentorship_paths(path_id) ON DELETE CASCADE
    )");
    echo "[+] certificates restored.\n";

    // 6. Gamification & Social
    $db->exec("CREATE TABLE IF NOT EXISTS badges (
        badge_id INT AUTO_INCREMENT PRIMARY KEY,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        icon VARCHAR(255),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS user_badges (
        user_badge_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        badge_id INT NOT NULL,
        earned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (badge_id) REFERENCES badges(badge_id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS followers (
        id INT AUTO_INCREMENT PRIMARY KEY,
        follower_id INT NOT NULL,
        following_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (follower_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (following_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS project_endorsements (
        endorsement_id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        endorser_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
        FOREIGN KEY (endorser_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    $db->exec("CREATE TABLE IF NOT EXISTS activity_logs (
        log_id INT AUTO_INCREMENT PRIMARY KEY,
        user_id INT NOT NULL,
        activity_type VARCHAR(50),
        description TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");
    echo "[+] gamification & social restored.\n";

    echo "ALL DELETED TABLES RESTORED SUCCESSFULLY.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
