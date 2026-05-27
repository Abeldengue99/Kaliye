<?php
// processos/migrations/migrate_mentorship_targets.php
require_once __DIR__ . '/../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

try {
    echo "--- Migrating Notices Targets ---\n";
    $notices = $db->query("SELECT notice_id, mentor_id FROM mentor_notices")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($notices as $n) {
        $stmtMentees = $db->prepare("SELECT DISTINCT user_id FROM chat_group_members 
                                     WHERE group_id IN (SELECT group_id FROM chat_groups WHERE creator_id = :mid)
                                     AND user_id != :mid");
        $stmtMentees->execute([':mid' => $n['mentor_id']]);
        $mentees = $stmtMentees->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($mentees as $m) {
            $db->prepare("INSERT IGNORE INTO mentorship_item_targets (item_type, item_id, student_id) VALUES ('notice', ?, ?)")
               ->execute([$n['notice_id'], $m['user_id']]);
        }
        echo "Notice {$n['notice_id']} targeted to " . count($mentees) . " students.\n";
    }

    echo "\n--- Migrating Resources Targets ---\n";
    $resources = $db->query("SELECT resource_id, mentor_id FROM mentor_resources")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($resources as $r) {
        $stmtMentees = $db->prepare("SELECT DISTINCT user_id FROM chat_group_members 
                                     WHERE group_id IN (SELECT group_id FROM chat_groups WHERE creator_id = :mid)
                                     AND user_id != :mid");
        $stmtMentees->execute([':mid' => $r['mentor_id']]);
        $mentees = $stmtMentees->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($mentees as $m) {
            $db->prepare("INSERT IGNORE INTO mentorship_item_targets (item_type, item_id, student_id) VALUES ('resource', ?, ?)")
               ->execute([$r['resource_id'], $m['user_id']]);
        }
        echo "Resource {$r['resource_id']} targeted to " . count($mentees) . " students.\n";
    }

    echo "\nâœ… Migration completed!\n";
} catch (PDOException $e) {
    echo "âŒ Error: " . $e->getMessage() . "\n";
}
?>

