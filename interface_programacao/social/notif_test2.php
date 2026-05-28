<?php
require '../../configuracoes/base_dados.php';
$db = (new Database())->getConnection();
$stmt = $db->query("SELECT notification_id, type, reference_id, link, title FROM notifications ORDER BY created_at DESC LIMIT 15;");
print_r($stmt->fetchAll(PDO::FETCH_ASSOC));
