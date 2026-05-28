<?php
// processos/debug/create_test_projects_domingos.php
require_once __DIR__ . '/../../configuracoes/base_dados.php';

$database = new Database();
$db = $database->getConnection();

$domingos_id = 19;

$projects = [
    [
        'title' => 'BioGas Connect - Energia Limpa Comunitária',
        'description' => 'Uma plataforma digital para gerir biodigestores comunitários em zonas periurbanas de Luanda. O sistema monitoriza a produção de biogás via IoT e gere a distribuição e faturação para as famílias, transformando resíduos orgânicos em energia barata e fertilizantes.',
        'category' => 'Energia & Ambiente',
        'budget_needed' => 2500000.00,
        'funding_goal' => 1500000.00,
        'minimum_investment' => 50000.00,
        'funding_type' => 'equity',
        'execution_time' => '12 meses',
        'team_size' => 3,
        'project_stage' => 'MVP',
        'target_audience' => 'Famílias em zonas sem rede de gás, agricultores urbanos.',
        'needs_to_advance' => 'Capital para hardware IoT e construção do primeiro protótipo de escala industrial.',
        'idea_origin' => 'Hackathon de Sustentabilidade 2024',
        'motivation' => 'Reduzir o custo de energia para famílias vulneráveis e diminuir o despejo de lixo orgânico.',
        'tags' => 'IoT, Biogás, Sustentabilidade, Luanda'
    ],
    [
        'title' => 'SolarPump Lease - Irrigação Inteligente',
        'description' => 'Modelo de leasing de bombas de irrigação alimentadas por energia solar para pequenos agricultores. Inclui um painel de controlo móvel que permite ao agricultor pagar pelo uso e monitorizar a humidade do solo, garantindo colheitas durante a época seca.',
        'category' => 'Agrotech',
        'budget_needed' => 4000000.00,
        'funding_goal' => 2500000.00,
        'minimum_investment' => 100000.00,
        'funding_type' => 'equity',
        'execution_time' => '18 meses',
        'team_size' => 4,
        'project_stage' => 'Projecto',
        'target_audience' => 'Pequenos e médios agricultores das províncias centrais.',
        'needs_to_advance' => 'Parceria com fornecedores de painéis solares e investimento inicial para stock de bombas.',
        'idea_origin' => 'Observação de campo no Huambo',
        'motivation' => 'Aumentar a produtividade agrícola nacional de forma sustentável.',
        'tags' => 'Agricutura, Solar, Fintech, Leasing'
    ]
];

try {
    $db->beginTransaction();

    foreach ($projects as $p) {
        $query = "INSERT INTO projects (
            owner_id, title, description, category, budget_needed, 
            funding_goal, minimum_investment, funding_type, 
            execution_time, team_size, project_stage, target_audience, 
            needs_to_advance, idea_origin, motivation, is_public, ai_status
        ) VALUES (
            :owner_id, :title, :description, :category, :budget, 
            :goal, :min_inv, :fund_type, 
            :exec_time, :team, :stage, :audience, 
            :needs, :origin, :motivation, 1, 'analyzed'
        )";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':owner_id' => $domingos_id,
            ':title' => $p['title'],
            ':description' => $p['description'],
            ':category' => $p['category'],
            ':budget' => $p['budget_needed'],
            ':goal' => $p['funding_goal'],
            ':min_inv' => $p['minimum_investment'],
            ':fund_type' => $p['funding_type'],
            ':exec_time' => $p['execution_time'],
            ':team' => $p['team_size'],
            ':stage' => $p['project_stage'],
            ':audience' => $p['target_audience'],
            ':needs' => $p['needs_to_advance'],
            ':origin' => $p['idea_origin'],
            ':motivation' => $p['motivation']
        ]);
        
        $project_id = $db->lastInsertId();
        echo "Projeto '{$p['title']}' criado com ID: $project_id\n";

        // Add Tags
        if (!empty($p['tags'])) {
            $tags = explode(',', $p['tags']);
            foreach ($tags as $tag_name) {
                $tag_name = trim($tag_name);
                
                // Ensure skill exists
                $s_stmt = $db->prepare("SELECT skill_id FROM skills WHERE name = ?");
                $s_stmt->execute([$tag_name]);
                $skill_id = $s_stmt->fetchColumn();
                
                if (!$skill_id) {
                    $db->prepare("INSERT INTO skills (name) VALUES (?)")->execute([$tag_name]);
                    $skill_id = $db->lastInsertId();
                }
                
                // Link
                $db->prepare("INSERT INTO project_tags (project_id, skill_id) VALUES (?, ?)")->execute([$project_id, $skill_id]);
            }
        }
    }

    $db->commit();
    echo "\nSucesso: 2 projetos adicionados para Domingos (ID 19).\n";

} catch (Exception $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo "Erro: " . $e->getMessage();
}

