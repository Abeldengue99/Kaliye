<?php
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('CLI only');
}

require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/AdminAutomation.php';

$db = (new Database())->getConnection();
$automation = new AdminAutomation($db, null);
$result = $automation->run(in_array('--dry-run', $argv, true));

echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . PHP_EOL;
?>
