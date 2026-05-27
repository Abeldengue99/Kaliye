<?php
// processos/migrations/mentorship_v1.php
require_once __DIR__ . '/../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Mentorship Slots (Actual sessions/slots)
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_slots (
        slot_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        start_time DATETIME NOT NULL,
        end_time DATETIME NOT NULL,
        status ENUM('available', 'booked', 'confirmed', 'completed') DEFAULT 'available',
        participant_id INT NULL,
        meeting_link VARCHAR(255) NULL,
        meeting_room VARCHAR(100) NULL,
        platform ENUM('jitsi', 'google_meet', 'zoom', 'other') DEFAULT 'jitsi',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
        FOREIGN KEY (participant_id) REFERENCES users(user_id) ON DELETE SET NULL
    )");

    // 2. Mentor Availability (Recurring Slots)
    $db->exec("CREATE TABLE IF NOT EXISTS mentor_availability (
        availability_id INT AUTO_INCREMENT PRIMARY KEY,
        mentor_id INT NOT NULL,
        day_of_week TINYINT COMMENT '0=Dom, 1=Seg... 6=Sab',
        start_time TIME NOT NULL,
        end_time TIME NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    echo "Migration successful.";
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage();
}
?>

