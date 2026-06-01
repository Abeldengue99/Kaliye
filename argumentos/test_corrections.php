<?php
/**
 * TESTE DE VALIDAÇÃO - Notificações e Dashboard
 * 
 * Script para verificar se as correções foram implementadas corretamente
 * Acesso: http://192.168.0.195/kaliye/argumentos/test_corrections.php
 */

require_once '../configuracoes/base_dados.php';

$results = [
    'notifications_validator' => false,
    'dashboard_hero_preload' => false,
    'get_notifications_fix' => false,
    'notification_links_fixed' => false,
    'old_notification_links_count' => 0,
];

// ========================================
// TESTE 1: Verificar se validador foi carregado
// ========================================
$results['notifications_validator'] = defined('NOTIFICATIONS_VALIDATION_RUNNING');

// ========================================
// TESTE 2: Verificar se dashboard_hero tem preload
// ========================================
$dashboard_hero_path = '../inclusoes/components/dashboard/dashboard_hero.php';
if (file_exists($dashboard_hero_path)) {
    $dashboard_content = file_get_contents($dashboard_hero_path);
    $results['dashboard_hero_preload'] = (
        strpos($dashboard_content, "link.rel = 'preload'") !== false &&
        strpos($dashboard_content, "document.head.appendChild") !== false
    );
}

// ========================================
// TESTE 3: Verificar se get_notifications tem correção
// ========================================
$get_notifications_path = '../interface_programacao/social/get_notifications.php';
if (file_exists($get_notifications_path)) {
    $get_notif_content = file_get_contents($get_notifications_path);
    $results['get_notifications_fix'] = strpos($get_notif_content, "paginas/social/duvidas.php") !== false;
}

// ========================================
// TESTE 4: Contar notificações com links antigos no BD
// ========================================
try {
    $db = new Database();
    $conn = $db->getConnection();
    
    $query = $conn->query("SELECT COUNT(*) as cnt FROM notifications WHERE link LIKE '%paginas/social/duvidas.php%'");
    $result = $query->fetch(PDO::FETCH_ASSOC);
    $results['old_notification_links_count'] = $result['cnt'] ?? 0;
    
    // Se houver links antigos, significa que o validador não corrigiu ainda
    $results['notification_links_fixed'] = ($results['old_notification_links_count'] === 0);
    
} catch (Exception $e) {
    $results['error'] = $e->getMessage();
}

// ========================================
// TESTE 5: Verificar se base_dados.php carrega validador
// ========================================
$base_dados_path = '../configuracoes/base_dados.php';
if (file_exists($base_dados_path)) {
    $base_dados_content = file_get_contents($base_dados_path);
    $results['validator_loaded_at_startup'] = strpos($base_dados_content, 'notifications_validator.php') !== false;
}

?>
<!DOCTYPE html>
<html>
<head>
    <title>Teste de Correções - KALIYE</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        .container { max-width: 800px; }
        .test-item { margin: 15px 0; padding: 10px; border: 1px solid #ccc; border-radius: 4px; }
        .pass { background-color: #d4edda; border-color: #28a745; color: #155724; }
        .fail { background-color: #f8d7da; border-color: #f5c6cb; color: #721c24; }
        .warning { background-color: #fff3cd; border-color: #ffc107; color: #856404; }
        h1 { color: #333; }
        code { background: #f5f5f5; padding: 2px 6px; }
    </style>
</head>
<body>
    <div class="container">
        <h1>✅ Teste de Validação - Correções Implementadas</h1>
        <p><strong>Data:</strong> <?php echo date('Y-m-d H:i:s'); ?></p>
        
        <?php
        // Teste 1
        $class = $results['notifications_validator'] ? 'pass' : 'fail';
        echo "<div class='test-item $class'>";
        echo "<strong>1. Validador de Notificações Carregado:</strong> ";
        echo $results['notifications_validator'] ? "✅ SIM" : "❌ NÃO";
        echo "</div>";
        
        // Teste 2
        $class = $results['dashboard_hero_preload'] ? 'pass' : 'fail';
        echo "<div class='test-item $class'>";
        echo "<strong>2. Dashboard Hero Preload Implementado:</strong> ";
        echo $results['dashboard_hero_preload'] ? "✅ SIM" : "❌ NÃO";
        echo "</div>";
        
        // Teste 3
        $class = $results['get_notifications_fix'] ? 'pass' : 'fail';
        echo "<div class='test-item $class'>";
        echo "<strong>3. Correção de Links em get_notifications.php:</strong> ";
        echo $results['get_notifications_fix'] ? "✅ SIM" : "❌ NÃO";
        echo "</div>";
        
        // Teste 4
        if ($results['old_notification_links_count'] > 0) {
            $class = 'warning';
            $status = "⚠️ " . $results['old_notification_links_count'] . " links antigos encontrados";
        } else {
            $class = 'pass';
            $status = "✅ Nenhum link antigo no BD";
        }
        echo "<div class='test-item $class'>";
        echo "<strong>4. Estado da Base de Dados:</strong> $status";
        echo "</div>";
        
        // Teste 5
        $class = $results['validator_loaded_at_startup'] ? 'pass' : 'fail';
        echo "<div class='test-item $class'>";
        echo "<strong>5. Validador Carregado no Startup:</strong> ";
        echo $results['validator_loaded_at_startup'] ? "✅ SIM" : "❌ NÃO";
        echo "</div>";
        
        // Erro (se houver)
        if (isset($results['error'])) {
            echo "<div class='test-item fail'>";
            echo "<strong>⚠️ Erro:</strong> " . htmlspecialchars($results['error']);
            echo "</div>";
        }
        ?>
        
        <hr>
        
        <h2>📝 Resumo</h2>
        
        <?php
        $passed = array_sum(array_filter([
            $results['notifications_validator'],
            $results['dashboard_hero_preload'],
            $results['get_notifications_fix'],
            $results['validator_loaded_at_startup']
        ]));
        $total = 4;
        
        echo "<p><strong>Testes Passados:</strong> $passed/$total</p>";
        
        if ($passed === $total) {
            echo "<div class='test-item pass'><strong>🎉 Todas as correções foram implementadas com sucesso!</strong></div>";
        } else {
            echo "<div class='test-item fail'><strong>⚠️ Algumas correções podem estar incompletas.</strong></div>";
        }
        
        if ($results['old_notification_links_count'] > 0) {
            echo "<div class='test-item warning'>";
            echo "<strong>⚠️ Nota:</strong> Ainda existem " . $results['old_notification_links_count'] . " links antigos no BD. ";
            echo "O validador irá corrigi-los automaticamente. ";
            echo "<a href='?validate_notifications=1'>Forçar validação agora</a>";
            echo "</div>";
        }
        ?>
        
        <h2>🔧 Instruções de Teste Manual</h2>
        <ol>
            <li><strong>Teste de Notificação:</strong> Clique em qualquer notificação de dúvida no dashboard. Deve abrir sem erro 404.</li>
            <li><strong>Teste de Dashboard:</strong> Recarregue a página principal. Imagens do carrossel não devem piscar.</li>
            <li><strong>Validação Automática:</strong> O sistema irá corrigir links antigos automaticamente em background.</li>
        </ol>
        
    </div>
</body>
</html>
