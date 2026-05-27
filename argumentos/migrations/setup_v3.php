<?php
require_once '../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    // 1. Announcements Table
    $sql = "CREATE TABLE IF NOT EXISTS announcements (
        id INT AUTO_INCREMENT PRIMARY KEY,
        message TEXT NOT NULL,
        type VARCHAR(20) DEFAULT 'info',
        is_active TINYINT(1) DEFAULT 1,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Announcements table created.<br>";

    // 2. Audit Logs Table
    $sql = "CREATE TABLE IF NOT EXISTS audit_logs (
        id INT AUTO_INCREMENT PRIMARY KEY,
        admin_id INT,
        action VARCHAR(50),
        details TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Audit Logs table created.<br>";

    // 3. Add is_verified to Users
    // Check if column exists first to avoid error
    $col = $db->query("SHOW COLUMNS FROM users LIKE 'is_verified'")->fetch();
    if (!$col) {
        $db->exec("ALTER TABLE users ADD COLUMN is_verified TINYINT(1) DEFAULT 0");
        echo "is_verified column added to users.<br>";
    } else {
        echo "is_verified column already exists.<br>";
    }

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
