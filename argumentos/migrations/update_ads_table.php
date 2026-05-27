<?php
require_once '../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

try {
    // Add image_url column
    $sql = "ALTER TABLE ads ADD COLUMN image_url VARCHAR(255) DEFAULT NULL";
    $db->exec($sql);
    echo "Column image_url added successfully.<br>";
} catch (PDOException $e) {
    echo "Error adding image_url (might already exist): " . $e->getMessage() . "<br>";
}

try {
    // Add link_url column
    $sql = "ALTER TABLE ads ADD COLUMN link_url VARCHAR(255) DEFAULT NULL";
    $db->exec($sql);
    echo "Column link_url added successfully.<br>";
} catch (PDOException $e) {
    echo "Error adding link_url (might already exist): " . $e->getMessage() . "<br>";
}
?>
