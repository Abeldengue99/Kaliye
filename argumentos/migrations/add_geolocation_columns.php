<?php
require_once __DIR__ . '/../../configuracoes/base_dados.php';

try {
    $db = (new Database())->getConnection();
    echo "Updating login_logs table for Geolocation tracking...\n";

    // Add columns for localization
    $columns = [
        "ADD COLUMN country VARCHAR(100) NULL AFTER device_brand",
        "ADD COLUMN city VARCHAR(100) NULL AFTER country",
        "ADD COLUMN region VARCHAR(100) NULL AFTER city",
        "ADD COLUMN isp VARCHAR(100) NULL AFTER region",
        "ADD COLUMN latitude DECIMAL(10, 8) NULL AFTER isp",
        "ADD COLUMN longitude DECIMAL(11, 8) NULL AFTER latitude"
    ];

    foreach ($columns as $col) {
        try {
            $db->exec("ALTER TABLE login_logs $col");
            echo "Column added: $col\n";
        } catch (PDOException $e) {
            // Check if error is "Duplicate column name" (Code 42S21)
            if ($e->getCode() == '42S21') {
                echo "Column already exists, skipping.\n";
            } else {
                echo "Error adding column: " . $e->getMessage() . "\n";
            }
        }
    }

    echo "Database updated successfully.\n";

} catch (Exception $e) {
    echo "Critical Error: " . $e->getMessage() . "\n";
}
