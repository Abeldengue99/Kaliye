<?php
require_once 'configuracoes/base_dados.php';

\ = new Database();
\ = \->getConnection();

// Buscar os 3 usuários
\ = "SELECT user_id, email, full_name FROM users WHERE full_name ILIKE '%alexandrina%' OR full_name ILIKE '%aniel%' OR full_name ILIKE '%admin%' OR email ILIKE '%admin%' ORDER BY full_name";
\ = \->prepare(\);
\->execute();
\ = \->fetchAll();

echo "=== USUÁRIOS ENCONTRADOS ===\n";
foreach (\ as \) {
    echo "\nuser_id: " . \['user_id'];
    echo "\nname: " . \['full_name'];
    echo "\nemail: " . \['email'];
    echo "\n---\n";
}

if (empty(\)) {
    echo "Nenhum usuário encontrado\n";
}
?>
