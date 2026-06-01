<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

$tables = ['social_comments', 'project_comments'];
$output = "";
foreach ($tables as $table) {
    try {
        $stmt = $db->query("SELECT column_name, data_type FROM information_schema.columns WHERE table_name = '$table'");
        $cols = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if ($cols) {
            $output .= "Table $table exists with columns:\n";
            foreach ($cols as $col) {
                $output .= "  - {$col['column_name']} ({$col['data_type']})\n";
            }
        } else {
            $output .= "Table $table DOES NOT EXIST.\n";
        }
    } catch (Exception $e) {
        $output .= "Error checking $table: " . $e->getMessage() . "\n";
    }
}
echo nl2br($output);
?>
