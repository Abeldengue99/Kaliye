<?php
require 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

// Buscar todas as tabelas
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);

echo "--- INICIANDO MAPEAMENTO DE 74 TABELAS ---\n";
foreach($tables as $table) {
    echo "\nTABLE: $table\n";
    $columns = $db->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
    foreach($columns as $col) {
        echo "  - {$col['Field']} ({$col['Type']})\n";
    }
}
