<?php
/**
 * ADMIN: Corrigir Links de Notificações de Dúvidas
 * Arquivo temporário para corrigir o redirecionamento errado de notificações
 */

session_start();

// Verificar se é admin
if (empty($_SESSION['user_type']) || $_SESSION['user_type'] !== 'admin') {
    die('❌ Acesso negado - Apenas administradores');
}

require_once '../configuracoes/base_dados.php';

?>
<!DOCTYPE html>
<html lang="pt-PT">
<head>
    <meta charset="UTF-8">
    <title>Corrigir Notificações de Dúvida</title>
    <style>
        body { background: #0f172a; color: #e2e8f0; font-family: monospace; padding: 2rem; }
        .container { max-width: 800px; margin: 0 auto; background: rgba(15,23,42,0.8); border: 1px solid #f7941d; border-radius: 12px; padding: 2rem; }
        h1 { color: #f7941d; }
        .success { background: rgba(16, 185, 129, 0.15); border-left: 4px solid #10b981; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        .info { background: rgba(59, 130, 246, 0.15); border-left: 4px solid #3b82f6; padding: 1rem; margin: 1rem 0; border-radius: 4px; }
        code { background: rgba(0,0,0,0.3); padding: 0.25rem 0.5rem; border-radius: 3px; }
        .stat { display: grid; grid-template-columns: repeat(2, 1fr); gap: 1rem; margin: 1.5rem 0; }
        .card { background: rgba(99, 102, 241, 0.1); border: 1px solid #6366f1; padding: 1rem; border-radius: 8px; }
        .card h3 { color: #a5b4fc; margin-bottom: 0.5rem; font-size: 0.9rem; }
        .value { font-size: 1.8rem; font-weight: bold; color: #f7941d; }
    </style>
</head>
<body>
    <div class="container">
        <h1>🔧 Corrigir Links de Notificações de Dúvida</h1>
        
<?php

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Contar notificações com link incorreto
    $check_old = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $old_count = $check_old->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    echo "<div class='info'>";
    echo "🔍 Varredura encontrou <code>$old_count</code> notificações com link incorreto";
    echo "</div>";

    if ($old_count > 0) {
        // 2. Atualizar links
        $affected = $db->exec("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
        
        echo "<div class='success'>";
        echo "✅ <strong>Correção Aplicada:</strong><br>";
        echo "• Notificações atualizadas: <code>$affected</code><br>";
        echo "• Link antigo: <code>paginas/social/duvidas.php</code><br>";
        echo "• Link novo: <code>paginas/explorar/doubts.php</code>";
        echo "</div>";

        // 3. Verificar se todas foram corrigidas
        $verify = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
        $remaining = $verify->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        echo "<div class='success'>";
        echo "🎉 <strong>Verificação OK:</strong><br>";
        echo "• Links restantes com erro: <code>$remaining</code>";
        echo "</div>";
    } else {
        echo "<div class='success'>";
        echo "ℹ️ Sem correções necessárias - Todas as notificações já estão corretas!";
        echo "</div>";
    }

    // 4. Estatísticas
    echo "<h2 style='color: #f7941d; margin-top: 2rem;'>📊 Estatísticas Gerais</h2>";
    
    $stats = $db->query("
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN type = 'comment' THEN 1 ELSE 0 END) as doubt_comments,
            SUM(CASE WHEN type = 'best_comment' THEN 1 ELSE 0 END) as best_comments,
            SUM(CASE WHEN link LIKE '%doubts.php%' THEN 1 ELSE 0 END) as correct_doubt_links
        FROM notifications
    ")->fetch(PDO::FETCH_ASSOC);

    echo "<div class='stat'>";
    echo "<div class='card'><h3>Total de Notificações</h3><div class='value'>" . $stats['total'] . "</div></div>";
    echo "<div class='card'><h3>Comentários em Dúvidas</h3><div class='value'>" . ($stats['doubt_comments'] ?? 0) . "</div></div>";
    echo "<div class='card'><h3>Comentários Selecionados</h3><div class='value'>" . ($stats['best_comments'] ?? 0) . "</div></div>";
    echo "<div class='card'><h3>Links Corretos (doubts.php)</h3><div class='value'>" . ($stats['correct_doubt_links'] ?? 0) . "</div></div>";
    echo "</div>";

    echo "<div class='success' style='margin-top: 2rem;'>";
    echo "✅ <strong>Operação Concluída!</strong><br>";
    echo "Os usuários agora podem clicar em notificações de dúvida e serão redirecionados corretamente para <code>paginas/explorar/doubts.php</code>";
    echo "</div>";

} catch (Exception $e) {
    echo "<div style='background: rgba(239, 68, 68, 0.15); border-left: 4px solid #ef4444; padding: 1rem; border-radius: 4px;'>";
    echo "❌ <strong>Erro:</strong> " . htmlspecialchars($e->getMessage());
    echo "</div>";
}

?>
    </div>
</body>
</html>
