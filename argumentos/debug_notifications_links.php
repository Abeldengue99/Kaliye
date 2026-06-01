<?php
/**
 * Debug: Ver links das notificações na BD
 */

require_once '../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

try {
    // Procurar notificações com "duvidas" no link
    $query = "SELECT id, user_id, link, type, title, content FROM notifications WHERE link LIKE '%duv%' ORDER BY created_at DESC LIMIT 10";
    $stmt = $db->prepare($query);
    $stmt->execute();
    $notifications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "<h1>Notificações com 'duvidas' no link:</h1>";
    echo "<pre>";
    print_r($notifications);
    echo "</pre>";
    
    // Contar totais
    $count_query = "SELECT COUNT(*) as total, 
                    SUM(CASE WHEN link LIKE '%paginas/social/duvidas%' THEN 1 ELSE 0 END) as old_format,
                    SUM(CASE WHEN link LIKE '%paginas/explorar/doubts%' THEN 1 ELSE 0 END) as new_format
                    FROM notifications";
    $count_stmt = $db->prepare($count_query);
    $count_stmt->execute();
    $counts = $count_stmt->fetch(PDO::FETCH_ASSOC);
    
    echo "<h2>Resumo:</h2>";
    echo "<p>Total de notificações: " . $counts['total'] . "</p>";
    echo "<p>Links antigos (paginas/social/duvidas): " . ($counts['old_format'] ?? 0) . "</p>";
    echo "<p>Links novos (paginas/explorar/doubts): " . ($counts['new_format'] ?? 0) . "</p>";
    
} catch (Exception $e) {
    echo "Erro: " . $e->getMessage();
}
?>
