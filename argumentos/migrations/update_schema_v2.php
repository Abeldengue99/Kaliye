<?php
// processos/update_schema_v2.php
require_once __DIR__ . '/../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

echo "Updating database schema...\n";

try {
    // 1. Add verification and security columns to users table
    $columns = [
        "is_verified" => "TINYINT(1) DEFAULT 0",
        "phone" => "VARCHAR(20) DEFAULT NULL",
        "id_number" => "VARCHAR(50) DEFAULT NULL" // bilhete de identidade ou passaporte
    ];

    foreach ($columns as $col => $def) {
        try {
            $db->query("SELECT $col FROM users LIMIT 1");
            echo "Column '$col' already exists in 'users'.\n";
        } catch (PDOException $e) {
            echo "Adding '$col' to 'users'...\n";
            $db->exec("ALTER TABLE users ADD COLUMN $col $def");
        }
    }

    // 2. Create project_likes table
    $db->exec("CREATE TABLE IF NOT EXISTS project_likes (
        like_id INT AUTO_INCREMENT PRIMARY KEY,
        project_id INT NOT NULL,
        user_id INT NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE KEY unique_like (project_id, user_id)
    )");
    echo "Table 'project_likes' verified.\n";

    echo "Schema update completed successfully.\n";

} catch (PDOException $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>

