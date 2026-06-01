<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

$tables = ['social_comments', 'project_comments'];
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table'");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($cols) {
            echo "Table $table exists with columns:\n";
            foreach ($cols as $col) {
                echo "  - {$col['column_name']} ({$col['data_type']})\n";
            }
        } else {
            echo "Table $table DOES NOT EXIST.\n";
        }
    } catch (Exception $e) {
        echo "Error checking $table: " . $e->getMessage() . "\n";
    }
}
?>
