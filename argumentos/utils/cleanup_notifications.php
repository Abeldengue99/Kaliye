<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

echo "Checking project statuses...\n";
$st = $db->query("SELECT title, ai_status FROM projects");
$projects = $st->fetchAll(PDO::FETCH_ASSOC);
foreach ($projects as $p) {
    echo "- {$p['title']}: {$p['ai_status']}\n";
}

echo "\nChecking unread notifications for investors...\n";
$st = $db->query("SELECT COUNT(*) FROM investor_notifications WHERE CAST(is_read AS INTEGER) = 0");
$count = $st->fetchColumn();
echo "Total unread: $count\n";

if ($count > 0) {
    echo "Marking all legacy notifications as read to start the new 'Professional Curatory' flow...\n";
$db->query("UPDATE investor_notifications SET is_read = '1'");
    echo "Done.\n";
}
?>
