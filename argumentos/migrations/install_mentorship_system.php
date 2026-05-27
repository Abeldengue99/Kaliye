<?php
// Script para executar SQL do sistema de mentoria
require_once __DIR__ . '/../../configuracoes/base_dados.php';

try {
    $database = new Database();
    $db = $database->getConnection();
    
    echo "=== Instalando Sistema de Mentoria ===\n\n";
    
    // Ler arquivo SQL
    $sqlFile = __DIR__ . '/../../base_dados/mentorship_system.sql';
    if (!file_exists($sqlFile)) {
        throw new Exception("Arquivo SQL não encontrado: $sqlFile");
    }
    
    $sql = file_get_contents($sqlFile);
    
    // Separar queries por ponto e vírgula
    $statements = array_filter(array_map('trim', explode(';', $sql)));
    
    foreach ($statements as $statement) {
        if (empty($statement)) continue;
        
        try {
            $db->exec($statement);
        } catch (PDOException $e) {
            // Ignorar erro de coluna duplicada (1060) ou tabela existente (1050)
            if (strpos($e->getMessage(), '1060') !== false) {
                echo "⚠️ Coluna já existe (pulusando): " . substr($statement, 0, 50) . "...\n";
            } elseif (strpos($e->getMessage(), '1050') !== false) {
                echo "⚠️ Tabela já existe: " . substr($statement, 0, 50) . "...\n";
            } else {
                throw $e;
            }
        }
    }
    
    echo "\n✅ Base de dados atualizada com sucesso!\n";
    echo "✅ Tabelas verificadas/criadas:\n";
    echo "   - mentorship_contracts\n";
    echo "   - mentorship_sessions\n";
    echo "   - milestone_approvals\n";
    echo "   - mentor_monthly_reports\n";
    echo "\n✅ Campos verificados em 'projects'\n";
    echo "\nSistema pronto para uso!\n";
    
} catch (Exception $e) {
    echo "❌ Erro: " . $e->getMessage() . "\n";
    exit(1);
}
?>
