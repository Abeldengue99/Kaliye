<?php
require_once 'configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();
$stmt = $db->query("DESCRIBE users");
foreach($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo $row['Field'] . "\n";
}
?>
