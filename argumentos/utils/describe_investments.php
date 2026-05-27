<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->query("DESCRIBE project_investments");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
?>
