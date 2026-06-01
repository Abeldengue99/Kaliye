<?php
require_once 'configuracoes/base_dados.php';
try {
    $db = (new Database())->getConnection();
    $tables = $db->query('SELECT table_name FROM information_schema.tables WHERE table_schema = "public" ORDER BY table_name')->fetchAll(PDO::FETCH_COLUMN);
    
    echo "=== " . count($tables) . " TABELAS ===\n\n";
    
    $emptyOrphaned = [];
    $orphaned = [];
    
    foreach($tables as $t) {
        $count = $db->query('SELECT COUNT(*) FROM "' . $t . '"')->fetchColumn();
        $size = $db->query('SELECT pg_total_relation_size("' . $t . '")::bigint')->fetchColumn();
        
        echo str_pad($t, 35) . " | " . 
             str_pad($count . " registos", 15) . " | " . 
             str_pad(round($size/1024/1024, 1) . " MB", 8) . "\n";
    }
    
} catch (Exception $e) {
    echo "ERRO: " . $e->getMessage();
}
?>
