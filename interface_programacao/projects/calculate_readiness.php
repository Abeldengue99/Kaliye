<?php
/**
 * interface_programacao/projects/calculate_readiness.php
 * Algoritmo de Inteligência de Mercado para avaliar o potencial de projetos.
 */

function calculateProjectReadiness($p) {
    $score = 25; // Pontuação base (Existência do projeto)

    // 1. Estágio de Desenvolvimento (Peso: 40%)
    $stages = [
        'Projecto' => 10,
        'MVP' => 30,
        'Lançamento' => 45,
        'Crescimento' => 60,
        'Escala' => 75
    ];
    $stage = $p['project_stage'] ?? 'Projecto';
    $score += isset($stages[$stage]) ? $stages[$stage] : 5;

    // 2. Densidade de Dados (Peso: 15%)
    $descLen = mb_strlen($p['description'] ?? '');
    if ($descLen > 800) $score += 15;
    else if ($descLen > 300) $score += 10;
    else if ($descLen > 100) $score += 5;

    // 3. Media Power (Peso: 20%)
    if (!empty($p['video_url'] ?? null) || !empty($p['pitch_video_url'] ?? null)) $score += 15;
    if (!empty($p['image_url'] ?? null)) $score += 5;

    // 4. Estratégia & Fundo (Peso: 25%)
    if (!empty($p['budget_needed'] ?? null) && $p['budget_needed'] > 0) $score += 10;
    if (!empty($p['target_audience'] ?? null)) $score += 5;
    if (!empty($p['execution_time'] ?? null)) $score += 5;
    if (!empty($p['category'] ?? null)) $score += 5;

    return min(100, $score); // Teto de 100%
}

/**
 * Função global para atualizar o score de um projeto específico
 */
function updateProjectScore($db, $project_id) {
    $col = $db->prepare("
        SELECT 1
        FROM information_schema.columns
        WHERE table_schema = 'public'
          AND table_name = 'projects'
          AND column_name = 'market_score'
        LIMIT 1
    ");
    $col->execute();
    if (!$col->fetchColumn()) {
        return 0;
    }

    $stmt = $db->prepare("SELECT * FROM projects WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $project = $stmt->fetch();

    if ($project) {
        $score = calculateProjectReadiness($project);
        $upd = $db->prepare("UPDATE projects SET market_score = ? WHERE project_id = ?");
        $upd->execute([$score, $project_id]);
        return $score;
    }
    return 0;
}
?>
