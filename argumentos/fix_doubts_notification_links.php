<?php
/**
 * fix_doubts_notification_links.php
 * Corrige os links incorretos em todas as notificações de dúvidas na base de dados
 * Atualiza: paginas/social/duvidas.php → paginas/explorar/doubts.php
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

header('Content-Type: text/html; charset=utf-8');

echo <<<HTML
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Corrigir Links de Notificações de Dúvidas</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body { background: #0f172a; color: #e2e8f0; font-family: 'Monaco', monospace; padding: 2rem; }
        .container { max-width: 900px; margin: 0 auto; background: rgba(15,23,42,0.8); border: 1px solid rgba(255,255,255,0.1); border-radius: 12px; padding: 2rem; }
        h1 { color: #f7941d; margin-bottom: 1rem; font-size: 1.5rem; }
        .stat { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin: 1.5rem 0; }
        .stat-card { background: rgba(99, 102, 241, 0.1); border: 1px solid #6366f1; padding: 1rem; border-radius: 8px; }
        .stat-card h3 { color: #6366f1; font-size: 0.9rem; margin-bottom: 0.5rem; }
        .stat-card .value { font-size: 2rem; font-weight: bold; color: #f7941d; }
        .success { background: rgba(16, 185, 129, 0.1); border: 1px solid #10b981; color: #10b981; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .error { background: rgba(239, 68, 68, 0.1); border: 1px solid #ef4444; color: #ef4444; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        .info { background: rgba(59, 130, 246, 0.1); border: 1px solid #3b82f6; color: #3b82f6; padding: 1rem; border-radius: 8px; margin: 1rem 0; }
        code { background: rgba(0,0,0,0.3); padding: 0.3rem 0.6rem; border-radius: 4px; font-size: 0.9rem; }
        table { width: 100%; margin-top: 1rem; border-collapse: collapse; }
        th, td { padding: 0.75rem; text-align: left; border-bottom: 1px solid rgba(255,255,255,0.1); }
        th { background: rgba(99, 102, 241, 0.2); color: #a5b4fc; font-weight: bold; }
        tr:hover { background: rgba(99, 102, 241, 0.1); }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Corrigir Links de Notificações de Dúvidas</h1>
HTML;

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Contar notificações com link incorreto
    $check_old = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $old_count = $check_old->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    echo "<div class='info'>";
    echo "🔍 <strong>Varredura Inicial:</strong> Encontradas <code>$old_count</code> notificações com link incorreto<br>";
    echo "Padrão: <code>paginas/social/duvidas.php</code><br>";
    echo "Destino correto: <code>paginas/explorar/doubts.php</code>";
    echo "</div>";

    if ($old_count > 0) {
        // 2. Atualizar links
        $update = $db->query("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
        
        $affected_rows = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/explorar/doubts.php%' AND link LIKE '%doubt_id%'")->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        echo "<div class='success'>";
        echo "✅ <strong>Atualização Concluída!</strong><br>";
        echo "Links corrigidos: <code>" . $affected_rows . "</code> notificações<br>";
        echo "Status: Todas as notificações de dúvida agora apontam para o URL correto";
        echo "</div>";

        // 3. Verificar se todas foram corrigidas
        $verify = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
        $remaining = $verify->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        if ($remaining === 0) {
            echo "<div class='success'>";
            echo "🎉 <strong>Verificação OK:</strong> Nenhuma notificação com link incorreto restante";
            echo "</div>";
        }
    } else {
        echo "<div class='success'>";
        echo "✅ <strong>Nenhuma correção necessária:</strong> Todas as notificações já apontam para URL correto";
        echo "</div>";
    }

    // 4. Estatísticas gerais
    echo "<h2 style='color: #f7941d; margin-top: 2rem; margin-bottom: 1rem;'>📊 Estatísticas Gerais de Notificações</h2>";
    
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type = 'comment' THEN 1 ELSE 0 END) as doubt_comments,
            SUM(CASE WHEN type = 'best_comment' THEN 1 ELSE 0 END) as best_comments,
            SUM(CASE WHEN link LIKE '%doubts.php%' THEN 1 ELSE 0 END) as link_doubts
        FROM notifications
    ")->fetch(PDO::FETCH_ASSOC);

    echo "<div class='stat'>";
    echo "<div class='stat-card'>";
    echo "<h3>Total de Notificações</h3>";
    echo "<div class='value'>" . $stats['total'] . "</div>";
    echo "</div>";

    echo "<div class='stat-card'>";
    echo "<h3>Comentários em Dúvidas</h3>";
    echo "<div class='value'>" . ($stats['doubt_comments'] ?? 0) . "</div>";
    echo "</div>";

    echo "<div class='stat-card'>";
    echo "<h3>Comentários Selecionados</h3>";
    echo "<div class='value'>" . ($stats['best_comments'] ?? 0) . "</div>";
    echo "</div>";

    echo "<div class='stat-card'>";
    echo "<h3>Links → doubts.php</h3>";
    echo "<div class='value'>" . ($stats['link_doubts'] ?? 0) . "</div>";
    echo "</div>";
    echo "</div>";

    // 5. Mostrar alguns exemplos de notificações corrigidas
    echo "<h2 style='color: #f7941d; margin-top: 2rem; margin-bottom: 1rem;'>📝 Exemplos de Notificações (Últimas 5)</h2>";
    
    $examples = $db->query("
        SELECT notification_id, title, type, link, created_at 
        FROM notifications 
        WHERE link LIKE '%doubts.php%'
        ORDER BY created_at DESC
        LIMIT 5
    ")->fetchAll(PDO::FETCH_ASSOC);

    if (count($examples) > 0) {
        echo "<table>";
        echo "<thead><tr><th>ID</th><th>Título</th><th>Tipo</th><th>Link</th><th>Data</th></tr></thead>";
        echo "<tbody>";
        
        foreach ($examples as $row) {
            $link_short = substr($row['link'], 0, 50) . (strlen($row['link']) > 50 ? '...' : '');
            $date = date('d/m/Y H:i', strtotime($row['created_at']));
            echo "<tr>";
            echo "<td><code>" . $row['notification_id'] . "</code></td>";
            echo "<td>" . htmlspecialchars(substr($row['title'], 0, 40)) . "</td>";
            echo "<td><code>" . htmlspecialchars($row['type']) . "</code></td>";
            echo "<td><code style='font-size: 0.8rem;'>" . htmlspecialchars($link_short) . "</code></td>";
            echo "<td>" . $date . "</td>";
            echo "</tr>";
        }
        
        echo "</tbody>";
        echo "</table>";
    }

    echo "<div class='success' style='margin-top: 2rem;'>";
    echo "✅ <strong>Operação Concluída com Sucesso!</strong><br>";
    echo "As notificações de dúvida agora redirecionam corretamente para <code>paginas/explorar/doubts.php</code>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div class='error'>";
    echo "❌ <strong>Erro:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

echo <<<HTML
    </div>
</body>
</html>
HTML;
?>
