<?php
require_once 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

try {
    // 1. Remove a restrição antiga (se existir)
    $db->exec("ALTER TABLE doubts DROP CONSTRAINT IF EXISTS doubts_status_check");
    
    // 2. Adiciona a nova restrição com suporte para conversão de mentoria
    $db->exec("ALTER TABLE doubts ADD CONSTRAINT doubts_status_check CHECK (status IN ('open', 'closed', 'answered', 'mentorship_requested'))");
    
    echo "Sucesso: Tabela 'doubts' atualizada com suporte para mentoria.\n";
} catch (PDOException $e) {
    echo "Erro: " . $e->getMessage() . "\n";
}
