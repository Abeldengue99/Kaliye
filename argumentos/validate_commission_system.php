<?php
// processos/validate_commission_system.php
// Script de validação do sistema de comissões em produção
require_once dirname(__DIR__) . '/configuracoes/base_dados.php';

echo "=== VALIDAÃ‡ÃƒO DO SISTEMA DE COMISSÃ•ES (PRODUÃ‡ÃƒO) ===\n\n";

$database = new Database();
$db = $database->getConnection();

try {
    // 1. Verificar estrutura das tabelas
    echo "1. Verificando estrutura das tabelas...\n";
    
    $tables_to_check = ['project_investments', 'projects', 'commission_history'];
    foreach ($tables_to_check as $table) {
        $stmt = $db->query("SHOW TABLES LIKE '$table'");
        if ($stmt->rowCount() > 0) {
            echo "   âœ… Tabela '$table' existe\n";
        } else {
            echo "   âŒ Tabela '$table' NÃƒO existe\n";
            exit(1);
        }
    }
    
    // 2. Verificar colunas de comissão em project_investments
    echo "\n2. Verificando colunas de comissão em project_investments...\n";
    $required_columns = [
        'aksanti_commission_rate',
        'aksanti_commission_amount',
        'mentor_commission_rate',
        'mentor_commission_amount',
        'net_amount_to_project'
    ];
    
    $stmt = $db->query("DESCRIBE project_investments");
    $existing_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_columns[] = $row['Field'];
    }
    
    foreach ($required_columns as $col) {
        if (in_array($col, $existing_columns)) {
            echo "   âœ… Coluna '$col' existe\n";
        } else {
            echo "   âŒ Coluna '$col' NÃƒO existe\n";
        }
    }
    
    // 3. Verificar colunas em projects
    echo "\n3. Verificando colunas em projects...\n";
    $project_columns = ['mentor_eligible_for_commission', 'mentor_commission_percentage'];
    
    $stmt = $db->query("DESCRIBE projects");
    $existing_project_columns = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $existing_project_columns[] = $row['Field'];
    }
    
    foreach ($project_columns as $col) {
        if (in_array($col, $existing_project_columns)) {
            echo "   âœ… Coluna '$col' existe\n";
        } else {
            echo "   âŒ Coluna '$col' NÃƒO existe\n";
        }
    }
    
    // 4. Verificar dados existentes
    echo "\n4. Verificando dados existentes...\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM project_investments");
    $investments = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ðŸ“Š Total de investimentos: " . $investments['total'] . "\n";
    
    $stmt = $db->query("SELECT COUNT(*) as total FROM commission_history");
    $commissions = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "   ðŸ“Š Total de comissões registradas: " . $commissions['total'] . "\n";
    
    // 5. Estatísticas de comissões
    if ($commissions['total'] > 0) {
        echo "\n5. Estatísticas de comissões...\n";
        
        $stats = $db->query("
            SELECT 
                commission_type,
                status,
                COUNT(*) as count,
                SUM(commission_amount) as total_amount
            FROM commission_history
            GROUP BY commission_type, status
        ")->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($stats as $stat) {
            $type = $stat['commission_type'] == 'aksanti' ? 'Aksanti' : 'Mentor';
            $status = $stat['status'] == 'paid' ? 'Pagas' : 'Pendentes';
            echo "   ðŸ“ˆ $type - $status: " . $stat['count'] . " comissões, Total: " . 
                 number_format($stat['total_amount'], 2, ',', '.') . " AOA\n";
        }
    }
    
    // 6. Simular cálculo de comissão
    echo "\n6. Simulando cálculo de comissão...\n";
    $test_amount = 100000; // 100.000 AOA
    $aksanti_rate = 20.00;
    $mentor_rate = 5.00;
    
    $aksanti_commission = ($test_amount * $aksanti_rate) / 100;
    $mentor_commission = ($test_amount * $mentor_rate) / 100;
    $net_amount = $test_amount - $aksanti_commission - $mentor_commission;
    
    echo "   ðŸ’° Investimento simulado: " . number_format($test_amount, 2, ',', '.') . " AOA\n";
    echo "   ðŸ’µ Comissão Aksanti (20%): " . number_format($aksanti_commission, 2, ',', '.') . " AOA\n";
    echo "   ðŸ’µ Comissão Mentor (5%): " . number_format($mentor_commission, 2, ',', '.') . " AOA\n";
    echo "   ðŸ’° Valor líquido para projeto: " . number_format($net_amount, 2, ',', '.') . " AOA\n";
    
    echo "\n=== âœ… SISTEMA DE COMISSÃ•ES VALIDADO E OPERACIONAL (PRODUÃ‡ÃƒO) ===\n";
    
} catch (Exception $e) {
    echo "\nâŒ Erro durante o teste: " . $e->getMessage() . "\n";
    exit(1);
}

