<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

$full_schema = "-- KALIYE Database Schema Dump\n";
$full_schema .= "-- Generated on: " . date('Y-m-d H:i:s') . "\n\n";
$full_schema .= "SET FOREIGN_KEY_CHECKS = 0;\n\n";

foreach ($tables as $table) {
    $create_stmt = $db->query("SHOW CREATE TABLE `$table`")->fetch(PDO::FETCH_ASSOC);
    $full_schema .= "-- Structure for table `$table` --\n";
    $full_schema .= "DROP TABLE IF EXISTS `$table`;\n";
    $full_schema .= $create_stmt['Create Table'] . ";\n\n";
}

$full_schema .= "SET FOREIGN_KEY_CHECKS = 1;\n";

file_put_contents('documentacao/full_database_structure.sql', $full_schema);
echo "Schema exported successfully to docs/full_database_structure.sql\n";
