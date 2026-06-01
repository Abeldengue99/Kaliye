<?php
/**
 * fix_doubts_notification_links.php
 * Corrige os links incorretos em todas as notificações de dúvidas na base de dados
 * Atualiza: paginas/social/duvidas.php → paginas/explorar/doubts.php
 */

require_once __DIR__ . '/../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();

    // 1. Contar notificações com link incorreto
    $check_old = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $old_count = $check_old->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;

    error_log("=== CORRIGIR LINKS DE NOTIFICAÇÕES DE DÚVIDA ===");
    error_log("Notificações com link incorreto encontradas: " . $old_count);

    if ($old_count > 0) {
        // 2. Atualizar links
        $update = $db->exec("UPDATE notifications SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') WHERE link LIKE '%paginas/social/duvidas.php%'");
        
        error_log("Links atualizados com sucesso: " . $update . " notificações");

        // 3. Verificar se todas foram corrigidas
        $verify = $db->query("SELECT COUNT(*) as count FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
        $remaining = $verify->fetch(PDO::FETCH_ASSOC)['count'] ?? 0;
        
        error_log("Links restantes com erro: " . $remaining);
        
        echo "✅ SUCESSO: " . $update . " notificações de dúvida foram corrigidas!\n";
        echo "Links agora apontam corretamente para: paginas/explorar/doubts.php\n";
    } else {
        echo "ℹ️ Sem correções necessárias - Todas as notificações já apontam para URL correto\n";
    }

} catch (Exception $e) {
    error_log("ERRO ao corrigir notificações: " . $e->getMessage());
    echo "❌ ERRO: " . $e->getMessage() . "\n";
}
?>
