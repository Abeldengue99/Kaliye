<?php
// migrate_mentorship_v2.php
require_once 'configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. REVIEWS & TESTIMONIALS
    $db->exec("CREATE TABLE IF NOT EXISTS user_reviews (
        review_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        student_id INT NOT NULL,
        rating TINYINT NOT NULL CHECK (rating >= 1 AND rating <= 5),
        comment TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 2. AVAILABILITY & BOOKING
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_availability (
        availability_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        day_of_week TINYINT NOT NULL, -- 0 (Sun) to 6 (Sat)
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_sessions (
        session_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        student_id INT NOT NULL,
        session_date DATE NOT NULL,
        session_time TIME NOT NULL,
        status ENUM('pending', 'confirmed', 'completed', 'cancelled') DEFAULT 'pending',
        meeting_link VARCHAR(255),
        price DECIMAL(10,2) DEFAULT 0.00,
        paid TINYINT(1) DEFAULT 0,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 3. MENTORSHIP PATHS (Trilhas)
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_paths (
        path_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        description TEXT,
        price DECIMAL(10,2) DEFAULT 0.00,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_path_steps (
        step_id INT AUTO_INCREMENT PRIMARY KEY,
        path_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        sort_order INT DEFAULT 0,
        FOREIGN KEY (path_id) REFERENCES mentorship_paths(path_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_path_enrollments (
        enrollment_id INT AUTO_INCREMENT PRIMARY KEY,
        path_id INT NOT NULL,
        student_id INT NOT NULL,
        status ENUM('active', 'completed', 'dropped') DEFAULT 'active',
        progress_pct INT DEFAULT 0,
        enrolled_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (path_id) REFERENCES mentorship_paths(path_id) ON DELETE CASCADE,
        FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;");

    // 4. GAMIFICATION STATS
    $db->exec("ALTER TABLE users 
        ADD COLUMN IF NOT EXISTS xp_points INT DEFAULT 0,
        ADD COLUMN IF NOT EXISTS user_level INT DEFAULT 1,
        ADD COLUMN IF NOT EXISTS impact_score INT DEFAULT 0;");

    echo "Mentorship Ecosystem tables created successfully.\n";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
