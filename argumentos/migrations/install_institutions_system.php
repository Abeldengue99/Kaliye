<?php
// Script para executar SQL das Instituições
require_once __DIR__ . '/../../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Instalando Módulo de Instituições ===\n\n";
    
    $sql = file_get_contents(__DIR__ . '/../../base_dados/institutions_system.sql');
    
    $db->exec($sql);
    
    echo "✅ Base de dados atualizada com sucesso!\n";
    echo "✅ Tabela 'institutions' criada.\n";
    echo "✅ Tipo 'school_admin' adicionado.\n";
    echo "✅ Instituições de exemplo (UAN, UCAN, ISUTIC, ISPTEC) inseridas.\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
