<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();
$tables = $db->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);
print_r($tables);
?>
