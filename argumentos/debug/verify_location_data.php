<?php
/**
 * argumentos/debug/verify_location_data.php
 * Script para verificar se o campo location contém dados incorretos
 * (dados de focus_areas ou specialization_tags)
 */

require_once '../../configuracoes/base_dados.php';

$db = (new Database())->getConnection();

echo "=== VERIFICAÇÃO DE DADOS NO CAMPO LOCATION ===\n\n";

// 1. Verificar estrutura da tabela users
echo "[1] Verificando estrutura da tabela users...\n";
try {
    $stmt = $db->query("DESCRIBE users");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    $has_location = false;
    $has_focus_areas = false;
    $has_specialization = false;
    
    foreach ($columns as $col) {
        if ($col['Field'] === 'location') {
            echo "  ✓ Campo 'location' encontrado: " . $col['Type'] . "\n";
            $has_location = true;
        }
        if ($col['Field'] === 'focus_areas') {
            echo "  ✓ Campo 'focus_areas' encontrado: " . $col['Type'] . "\n";
            $has_focus_areas = true;
        }
        if ($col['Field'] === 'specialization_tags') {
            echo "  ✓ Campo 'specialization_tags' encontrado: " . $col['Type'] . "\n";
            $has_specialization = true;
        }
    }
    
    if (!$has_location) echo "  ✗ Campo 'location' NÃO encontrado!\n";
    if (!$has_focus_areas) echo "  ✗ Campo 'focus_areas' NÃO encontrado!\n";
    if (!$has_specialization) echo "  ✗ Campo 'specialization_tags' NÃO encontrado!\n";
} catch (Exception $e) {
    echo "  ✗ Erro ao consultar estrutura: " . $e->getMessage() . "\n";
}

echo "\n[2] Estatísticas dos campos...\n";

// 2. Contar registos com dados em cada campo
try {
    $stmt = $db->query("SELECT 
        COUNT(*) as total_users,
        SUM(IF(location IS NOT NULL AND location != '', 1, 0)) as location_preenchido,
        SUM(IF(focus_areas IS NOT NULL AND focus_areas != '', 1, 0)) as focus_areas_preenchido,
        SUM(IF(specialization_tags IS NOT NULL AND specialization_tags != '', 1, 0)) as specialization_preenchido
    FROM users");
    
    $stats = $stmt->fetch(PDO::FETCH_ASSOC);
    echo "  Total de utilizadores: " . $stats['total_users'] . "\n";
    echo "  Location preenchido: " . $stats['location_preenchido'] . "\n";
    echo "  Focus areas preenchido: " . $stats['focus_areas_preenchido'] . "\n";
    echo "  Specialization tags preenchido: " . $stats['specialization_preenchido'] . "\n";
} catch (Exception $e) {
    echo "  ✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n[3] Valores ÚNICOS no campo location (primeiros 30)...\n";
try {
    $stmt = $db->query("SELECT DISTINCT location FROM users WHERE location IS NOT NULL AND location != '' ORDER BY location LIMIT 30");
    $locations = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    if (empty($locations)) {
        echo "  ✗ Nenhum valor encontrado em location\n";
    } else {
        foreach ($locations as $loc) {
            echo "  - " . htmlspecialchars($loc) . "\n";
        }
    }
} catch (Exception $e) {
    echo "  ✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n[4] Procurando ANOMALIAS - location com dados suspeitos...\n";
try {
    // Procurar valores que parecem ser de focus_areas (múltiplos itens separados por vírgula)
    $stmt = $db->query("SELECT user_id, full_name, location FROM users 
        WHERE location LIKE '%,%' 
        LIMIT 10");
    
    $anomalias = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($anomalias)) {
        echo "  ✓ Nenhuma anomalia encontrada (location com múltiplas categorias)\n";
    } else {
        echo "  ⚠ ANOMALIAS ENCONTRADAS - location contém múltiplos valores:\n";
        foreach ($anomalias as $anom) {
            echo "    User ID: " . $anom['user_id'] . " | " . $anom['full_name'] . "\n";
            echo "      Location: " . htmlspecialchars($anom['location']) . "\n";
        }
    }
} catch (Exception $e) {
    echo "  ✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n[5] Comparando location com focus_areas e specialization_tags...\n";
try {
    // Verificar se há coincidências exatas entre location e focus_areas
    $stmt = $db->query("SELECT user_id, full_name, location, focus_areas, specialization_tags 
        FROM users 
        WHERE (location = focus_areas OR location = specialization_tags) 
        AND location IS NOT NULL 
        AND location != '' 
        LIMIT 10");
    
    $matches = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($matches)) {
        echo "  ✓ Nenhuma coincidência exata encontrada\n";
    } else {
        echo "  ⚠ COINCIDÊNCIAS ENCONTRADAS:\n";
        foreach ($matches as $match) {
            echo "    User ID: " . $match['user_id'] . " | " . $match['full_name'] . "\n";
            if ($match['location'] === $match['focus_areas']) {
                echo "      ✗ location = focus_areas: " . htmlspecialchars($match['location']) . "\n";
            }
            if ($match['location'] === $match['specialization_tags']) {
                echo "      ✗ location = specialization_tags: " . htmlspecialchars($match['location']) . "\n";
            }
        }
    }
} catch (Exception $e) {
    echo "  ✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n[6] Amostras de dados - 5 utilizadores aleatórios...\n";
try {
    $stmt = $db->query("SELECT user_id, full_name, location, focus_areas, specialization_tags 
        FROM users 
        WHERE user_type = 'student' OR user_type = 'mentor'
        ORDER BY RAND() 
        LIMIT 5");
    
    $samples = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($samples)) {
        echo "  ✗ Nenhum utilizador encontrado\n";
    } else {
        foreach ($samples as $sample) {
            echo "\n  [Utilizador ID: " . $sample['user_id'] . "]\n";
            echo "    Nome: " . $sample['full_name'] . "\n";
            echo "    Location: " . htmlspecialchars($sample['location'] ?? 'VAZIO') . "\n";
            echo "    Focus Areas: " . htmlspecialchars($sample['focus_areas'] ?? 'VAZIO') . "\n";
            echo "    Specialization: " . htmlspecialchars($sample['specialization_tags'] ?? 'VAZIO') . "\n";
        }
    }
} catch (Exception $e) {
    echo "  ✗ Erro: " . $e->getMessage() . "\n";
}

echo "\n=== FIM DA VERIFICAÇÃO ===\n";
?>
