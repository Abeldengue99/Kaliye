<?php
require 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();
$s = $db->query("SELECT comment_id, project_id, content FROM project_comments_v2");
echo json_encode($s->fetchAll(PDO::FETCH_ASSOC));
?>
