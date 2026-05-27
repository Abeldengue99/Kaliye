<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();
$st = $db->query('DESCRIBE projects');
foreach($st->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . " - " . $row['Type'] . " (Default: " . ($row['Default'] ?? 'NULL') . ")\n";
}
?>
