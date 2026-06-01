<?php
/**
 * argumentos/debug/fix_location_data.php
 * Script para CORRIGIR dados errados no campo location
 * 
 * USAR APENAS APÓS CONFIRMAR ANOMALIAS COM verify_location_data.php
 */

require_once '../../configuracoes/base_dados.php';

$db = (new Database())->getConnection();

// Lista de províncias válidas de Angola
$valid_provinces = [
    'Bengo', 'Benguela', 'Bié', 'Cabinda', 
    'Cuando Cubango', 'Cuanza Norte', 'Cuanza Sul', 
    'Cunene', 'Huambo', 'Huíla', 'Luanda', 
    'Lunda Norte', 'Lunda Sul', 'Malanje', 
    'Moxico', 'Namibe', 'Uíge', 'Zaire'
];

echo "=== CORREÇÃO DE DADOS - CAMPO LOCATION ===\n\n";

// Modo de execução
$mode = $_GET['mode'] ?? 'report';  // 'report' ou 'fix'
$fix_type = $_GET['fix_type'] ?? '';

if ($mode === 'fix' && empty($_GET['confirm'])) {
    echo "⚠ AVISO: Use ?mode=fix&confirm=yes para realmente fazer a correção\n";
    echo "         ?mode=fix&fix_type=default - Define location = 'Luanda' onde vazio\n";
    echo "         ?mode=fix&fix_type=extract - Extrai primeira palavra de location\n";
    echo "         ?mode=fix&fix_type=clear - Limpa location onde contém múltiplas categorias\n\n";
    $mode = 'report';
}

// ===========================
// MODO 1: RELATÓRIO (SEGURO)
// ===========================

if ($mode === 'report') {
    echo "[MODO: RELATÓRIO - Sem modificações]\n\n";
    
    // 1. Contar anomalias
    echo "[1] Registos com dados SUSPEITOS em location...\n";
    try {
        // Location com múltiplas categorias
        $stmt = $db->query("SELECT COUNT(*) as count FROM users 
            WHERE location LIKE '%,%'");
        $result = $stmt->fetch();
        echo "  - Location com vírgulas (suspeitamente como array): " . $result['count'] . "\n";
        
        // Location vazio
        $stmt = $db->query("SELECT COUNT(*) as count FROM users 
            WHERE location IS NULL OR location = ''");
        $result = $stmt->fetch();
        echo "  - Location vazio/NULL: " . $result['count'] . "\n";
        
        // Location muito longo (suspeito)
        $stmt = $db->query("SELECT COUNT(*) as count FROM users 
            WHERE LENGTH(location) > 50");
        $result = $stmt->fetch();
        echo "  - Location muito longo (>50 chars): " . $result['count'] . "\n";
        
    } catch (Exception $e) {
        echo "  ✗ Erro: " . $e->getMessage() . "\n";
    }
    
    // 2. Mostrar exemplos de anomalias
    echo "\n[2] Exemplos de valores suspeitos:\n";
    try {
        $stmt = $db->query("SELECT user_id, full_name, location FROM users 
            WHERE location IS NOT NULL AND location != '' 
            ORDER BY LENGTH(location) DESC 
            LIMIT 5");
        
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            echo "  ID: " . $row['user_id'] . " | " . $row['full_name'] . "\n";
            echo "    Location: " . htmlspecialchars($row['location']) . " (" . strlen($row['location']) . " chars)\n";
        }
    } catch (Exception $e) {
        echo "  ✗ Erro: " . $e->getMessage() . "\n";
    }
}

// ===========================
// MODO 2: CORREÇÃO (PERIGOSO)
// ===========================

elseif ($mode === 'fix' && $_GET['confirm'] === 'yes') {
    echo "[MODO: CORREÇÃO - Fazendo modificações]\n\n";
    
    // Tipo 1: Defina padrão para vazios
    if ($fix_type === 'default') {
        echo "[1] Definindo location = 'Luanda' para registos vazios...\n";
        try {
            $stmt = $db->prepare("UPDATE users SET location = 'Luanda' 
                WHERE location IS NULL OR location = ''");
            $stmt->execute();
            
            $affected = $stmt->rowCount();
            echo "  ✓ " . $affected . " registos atualizados para 'Luanda'\n";
            
            // Log
            $log = "UPDATE users SET location = 'Luanda' WHERE location IS NULL OR location = '' | " . $affected . " rows affected";
            echo "  Log: " . $log . "\n";
            
        } catch (Exception $e) {
            echo "  ✗ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    // Tipo 2: Extrai primeira palavra
    elseif ($fix_type === 'extract') {
        echo "[2] Extraindo primeira palavra de location onde há múltiplas...\n";
        try {
            // Primeiro, obter dados problemáticos
            $stmt = $db->query("SELECT user_id, location FROM users 
                WHERE location LIKE '%,%'");
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $updated = 0;
            
            foreach ($rows as $row) {
                $parts = explode(',', $row['location']);
                $first = trim($parts[0]);
                
                // Validar se é uma província válida
                if (in_array($first, $valid_provinces)) {
                    $stmt_update = $db->prepare("UPDATE users SET location = ? WHERE user_id = ?");
                    $stmt_update->execute([$first, $row['user_id']]);
                    $updated++;
                    echo "  ID " . $row['user_id'] . ": " . htmlspecialchars($row['location']) . " → " . $first . "\n";
                } else {
                    echo "  ⚠ ID " . $row['user_id'] . ": '" . $first . "' não é província válida\n";
                }
            }
            
            echo "  ✓ " . $updated . " registos corrigidos\n";
            
        } catch (Exception $e) {
            echo "  ✗ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    // Tipo 3: Limpar dados errados
    elseif ($fix_type === 'clear') {
        echo "[3] Limpando location para registos com múltiplas categorias...\n";
        try {
            // Encontrar e limpar
            $stmt = $db->query("SELECT user_id, location FROM users 
                WHERE location LIKE '%,%' OR LENGTH(location) > 50");
            
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $cleared = 0;
            
            $stmt_clear = $db->prepare("UPDATE users SET location = NULL WHERE user_id = ?");
            
            foreach ($rows as $row) {
                $stmt_clear->execute([$row['user_id']]);
                $cleared++;
                echo "  ID " . $row['user_id'] . ": LIMPO (era: " . htmlspecialchars(substr($row['location'], 0, 50)) . "...)\n";
            }
            
            echo "  ✓ " . $cleared . " registos limpos\n";
            
        } catch (Exception $e) {
            echo "  ✗ Erro: " . $e->getMessage() . "\n";
        }
    }
    
    else {
        echo "  ✗ fix_type inválido. Use: default, extract, ou clear\n";
    }
}

echo "\n=== OPÇÕES DISPONÍVEIS ===\n";
echo "1. ANÁLISE (Padrão):\n";
echo "   ?mode=report\n\n";

echo "2. CORREÇÃO:\n";
echo "   ?mode=fix&confirm=yes&fix_type=default  (Define 'Luanda' para vazios)\n";
echo "   ?mode=fix&confirm=yes&fix_type=extract  (Extrai primeira palavra válida)\n";
echo "   ?mode=fix&confirm=yes&fix_type=clear    (Limpa dados errados)\n\n";

echo "⚠ CUIDADO: Use fix_type=clear apenas após confirmar anomalias!\n";
?>
