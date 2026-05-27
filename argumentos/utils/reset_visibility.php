<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

echo "Initializing is_public column for projects...\n";
// Set all existing projects to is_public = 0 to require admin approval
$db->query("UPDATE projects SET is_public = false");
echo "Done. All projects now require manual admin approval to be visible to investors.\n";
?>
