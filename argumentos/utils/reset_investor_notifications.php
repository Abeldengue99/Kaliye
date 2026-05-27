<?php
// Script para resetar notificações de investidor
require_once __DIR__ . '/../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

echo "=== Verificando Notificações de Investidor ===\n\n";

// Mostrar estado atual
$check = $db->query("SELECT project_id, investor_id, is_read, created_at FROM investor_notifications ORDER BY created_at DESC LIMIT 10");
echo "Últimas 10 notificações:\n";
while ($row = $check->fetch()) {
    echo sprintf("Project: %d | Investor: %d | Read: %d | Created: %s\n", 
        $row['project_id'], 
        $row['investor_id'], 
        $row['is_read'],
        $row['created_at']
    );
}

echo "\n=== Resetando todas as notificações para 'não lido' ===\n";
$reset = $db->exec("UPDATE investor_notifications SET is_read = '0'");
echo "Resetadas: $reset notificações\n";

echo "\nConcluído! Todas as notificações foram marcadas como não lidas.\n";
?>
