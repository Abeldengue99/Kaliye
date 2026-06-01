<?php
/**
 * Quarterly retention job.
 *
 * Suggested cron: run this script every 3 months. It creates archive snapshots
 * and marks eligible operational records as archived. It does not delete data.
 */
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/RetentionMaintenance.php';

$dryRun = in_array('--dry-run', $argv ?? [], true);

try {
    $db = (new Database())->getConnection();
    $maintenance = new RetentionMaintenance($db);
    $result = $maintenance->run($dryRun);

    echo ($dryRun ? '[DRY RUN] ' : '') . "Retention maintenance completed.\n";
    foreach ($result as $bucket => $count) {
        echo "- {$bucket}: {$count}\n";
    }
} catch (Throwable $e) {
    fwrite(STDERR, "Retention maintenance failed: " . $e->getMessage() . "\n");
    exit(1);
}
