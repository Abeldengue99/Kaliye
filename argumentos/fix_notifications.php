<?php
/**
 * fix_notifications.php
 * Script simples para corrigir notificações de dúvida
 */

require_once 'configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    echo "=== Corrigir Links de Notificações de Dúvida ===\n\n";

    // Contar notificações com link incorreto
    $check = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $row = $check->fetch(PDO::FETCH_ASSOC);
    $old_count = $row['cnt'] ?? 0;

    echo "1. Notificações com link incorreto encontradas: " . $old_count . "\n";

    if ($old_count > 0) {
        // Atualizar
        $updated = $db->exec("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
        echo "2. Notificações atualizadas: " . $updated . "\n";

        // Verificar
        $verify = $db->query("SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
        $row = $verify->fetch(PDO::FETCH_ASSOC);
        $remaining = $row['cnt'] ?? 0;

        echo "3. Links restantes com erro: " . $remaining . "\n";
        echo "\n✅ SUCESSO! Notificações corrigidas.\n";
    } else {
        echo "\nℹ️ Sem correções necessárias.\n";
    }

} catch (Exception $e) {
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
