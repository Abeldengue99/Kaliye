<?php
require 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();
$s = $db->query("SELECT comment_id, project_id, content FROM project_comments_v2 WHERE content LIKE '%MENTORIA:%'");
print_r($s->fetchAll(PDO::FETCH_ASSOC));
?>
