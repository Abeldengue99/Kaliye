<?php
require 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();
try {
    $res = $db->query('SELECT * FROM project_investments ORDER BY investment_id DESC LIMIT 5')->fetchAll(PDO::FETCH_ASSOC);
    print_r($res);
} catch (Exception $e) {
    echo 'Error: ' . $e->getMessage();
}
