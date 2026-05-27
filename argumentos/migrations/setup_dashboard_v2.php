<?php
require_once '../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Create Settings Table
    $sql = "CREATE TABLE IF NOT EXISTS settings (
        setting_key VARCHAR(50) PRIMARY KEY,
        setting_value TEXT,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
    )";
    $db->exec($sql);
    echo "Settings table created.<br>";

    // Insert Default Settings
    $defaults = [
        'site_name' => 'KALIYE',
        'maintenance_mode' => '0',
        'admin_email' => 'admin@aksanti.xyz',
        'allow_registrations' => '1'
    ];

    $stmt = $db->prepare("INSERT IGNORE INTO settings (setting_key, setting_value) VALUES (:key, :val)");
    foreach ($defaults as $key => $val) {
        $stmt->execute([':key' => $key, ':val' => $val]);
    }
    echo "Default settings inserted.<br>";

} catch (PDOException $e) {
    echo "Error: " . $e->getMessage();
}
?>
