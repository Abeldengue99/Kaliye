<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';
$database = new Database();
$db = $database->getConnection();

echo "<h1>Debug Database Connection</h1>";
echo "<pre>";

try {
    $current_db = $db->query("SELECT current_database()")->fetchColumn();
    echo "Current Database: " . $current_db . "\n";
    
    $table = 'project_progress_reports';
    $stmt = $db->prepare("SELECT EXISTS (SELECT FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?)");
    $stmt->execute([$table]);
    $exists = $stmt->fetchColumn();
    echo "Table '$table' exists in 'public' schema: " . ($exists ? 'YES' : 'NO') . "\n";
    
    if (!$exists) {
        echo "Searching for table in all schemas...\n";
        $stmt = $db->prepare("SELECT table_schema FROM information_schema.tables WHERE table_name = ?");
        $stmt->execute([$table]);
        $schemas = $stmt->fetchAll(PDO::FETCH_COLUMN);
        if ($schemas) {
            echo "Table found in schemas: " . implode(', ', $schemas) . "\n";
        } else {
            echo "Table NOT found in any schema.\n";
        }
    }

    echo "\nListing all tables in public schema:\n";
    $tables = $db->query("SELECT table_name FROM information_schema.tables WHERE table_schema = 'public' AND table_type = 'BASE TABLE'")->fetchAll(PDO::FETCH_COLUMN);
    foreach ($tables as $t) {
        echo "- $t\n";
    }

} catch (PDOException $e) {
    echo "ERROR: " . $e->getMessage();
}
echo "</pre>";
?>
