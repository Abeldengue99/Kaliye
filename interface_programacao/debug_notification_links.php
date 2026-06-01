<?php
/**
 * Debug: Ver links das notificações na BD
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

header('Content-Type: text/html; charset=utf-8');

$database = new Database();
$db = $database->getConnection();

try {
    echo "<h1>🔍 Debug - Notificações com 'duvidas' no link</h1>";
    
    // Procurar notificações com "duvidas" no link
    $query = "SELECT id, user_id, link, type, title FROM notifications WHERE link LIKE '%duv%' ORDER BY created_at DESC LIMIT 15";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($notifications) > 0) {
        echo "<h2>Notificações encontradas:</h2>";
        echo "<table border='1' cellpadding='10'>";
        echo "<tr><th>ID</th><th>Link</th><th>Tipo</th><th>Título</th></tr>";
        foreach ($notifications as $n) {
            echo "<tr>";
            echo "<td>" . htmlspecialchars($n['id']) . "</td>";
            echo "<td><code>" . htmlspecialchars($n['link']) . "</code></td>";
            echo "<td>" . htmlspecialchars($n['type']) . "</td>";
            echo "<td>" . htmlspecialchars(substr($n['title'], 0, 50)) . "...</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p>✅ Nenhuma notificação com 'duvidas' no link encontrada!</p>";
    }
    
    // Contar totais
    $count_query = "SELECT COUNT(*) as total, 
                    SUM(CASE WHEN link LIKE '%paginas/social/duvidas%' THEN 1 ELSE 0 END) as old_format,
                    SUM(CASE WHEN link LIKE '%paginas/explorar/doubts%' THEN 1 ELSE 0 END) as new_format,
                    SUM(CASE WHEN link LIKE '%paginas/%' THEN 1 ELSE 0 END) as paginas_format
                    FROM notifications";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $counts = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>📊 Resumo Geral:</h2>";
    echo "<ul>";
    echo "<li><strong>Total de notificações:</strong> " . ($counts['total'] ?? 0) . "</li>";
    echo "<li><strong>Links antigos (paginas/social/duvidas):</strong> " . ($counts['old_format'] ?? 0) . "</li>";
    echo "<li><strong>Links novos (paginas/explorar/doubts):</strong> " . ($counts['new_format'] ?? 0) . "</li>";
    echo "<li><strong>Links com formato 'paginas/':</strong> " . ($counts['paginas_format'] ?? 0) . "</li>";
    echo "</ul>";
    
    // Amostra de links diferentes
    echo "<h2>📝 Tipos diferentes de links encontrados:</h2>";
    $distinct_query = "SELECT DISTINCT link FROM notifications WHERE link LIKE '%paginas%' ORDER BY link LIMIT 20";
    $distinct_stmt = $db->prepare($distinct_query);
    $distinct_stmt->execute();
    $distinct_links = $distinct_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($distinct_links) > 0) {
        echo "<ul>";
        foreach ($distinct_links as $dl) {
            echo "<li><code>" . htmlspecialchars($dl['link']) . "</code></li>";
        }
        echo "</ul>";
    }
    
} catch (Exception $e) {
    echo "<p><strong style='color:red'>❌ Erro:</strong> " . htmlspecialchars($e->getMessage()) . "</p>";
}
?>
