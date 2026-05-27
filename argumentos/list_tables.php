<?php
require 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo "LISTA DE TABELAS ATUAIS NO SEU MYSQL:\n";
foreach($tables as $t) echo "- $t\n";
