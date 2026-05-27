<?php
// argumentos/migrations/drop_academic_check.php
// Remove a restrição CHECK do nível académico para permitir escrita livre (Elite UX)

require_once __DIR__ . '/../../configuracoes/base_dados.php';

$db = (new Database())->getConnection();

try {
    echo "Analizando esquema da tabela 'users'...\n";
    
    // O PostgreSQL mantém os nomes das constraints. Segundo o erro do utilizador, o nome é 'users_academic_level_check'
    $sql = "ALTER TABLE users DROP CONSTRAINT IF EXISTS users_academic_level_check";
    $db->exec($sql);
    
    echo "SUCESSO: Restrição 'users_academic_level_check' removida.\n";
    echo "Agora os utilizadores podem preencher livremente o seu nível de formação.\n";

} catch (PDOException $e) {
    echo "ERRO CRÍTICO: " . $e->getMessage() . "\n";
}
?>
