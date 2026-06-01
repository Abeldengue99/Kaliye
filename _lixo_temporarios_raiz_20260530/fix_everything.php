<?php
$directory = new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.(php|js|json|md|html)$/i', RecursiveRegexIterator::GET_MATCH);

$replacements = [
    // Projecto -> Projecto
    '/\bIdeias\b/' => 'Projectos',
    '/\bideias\b/' => 'projectos',
    '/\bIDEIAS\b/' => 'PROJECTOS',
    '/\bIdeia\b/' => 'Projecto',
    '/\bideia\b/' => 'projecto',
    '/\bIDEIA\b/' => 'PROJECTO',

    // Accents
    '/\bnao\b/' => 'não',
    '/\bNao\b/' => 'Não',
    '/\bsao\b/' => 'são',
    '/\bSao\b/' => 'São',
    '/\btambem\b/' => 'também',
    '/\bTambem\b/' => 'Também',
    '/\bvoce\b/' => 'você',
    '/\bVoce\b/' => 'Você',
    '/\batraves\b/' => 'através',
    '/\bAtraves\b/' => 'Através',
    '/\bacao\b/' => 'acção',
    '/\bAcao\b/' => 'Acção',
    '/\bacoes\b/' => 'acções',
    '/\bAcoes\b/' => 'Acções',
    '/\bavancar\b/' => 'avançar',
    '/\bAvancar\b/' => 'Avançar',
    '/\bexperiencia\b/' => 'experiência',
    '/\bExperiencia\b/' => 'Experiência',
    '/\bconcluido\b/' => 'concluído',
    '/\bConcluido\b/' => 'Concluído',
    '/\bestrategia\b/' => 'estratégia',
    '/\bEstrategia\b/' => 'Estratégia',
    '/\binovacao\b/' => 'inovação',
    '/\bInovacao\b/' => 'Inovação',
    '/\bvisao\b/' => 'visão',
    '/\bVisao\b/' => 'Visão',
    '/\bmissao\b/' => 'missão',
    '/\bMissao\b/' => 'Missão',
    '/\binformacao\b/' => 'informação',
    '/\bInformacao\b/' => 'Informação',
    '/\bpublicacao\b/' => 'publicação',
    '/\bPublicacao\b/' => 'Publicação',
    '/\bvalidacao\b/' => 'validação',
    '/\bValidacao\b/' => 'Validação',
    '/\bdescricao\b/' => 'descrição',
    '/\bDescricao\b/' => 'Descrição',
    '/\bhistorico\b/' => 'histórico',
    '/\bHistorico\b/' => 'Histórico',
    '/\brelatorio\b/' => 'relatório',
    '/\bRelatorio\b/' => 'Relatório',
    '/\bduvida\b/' => 'dúvida',
    '/\bDuvida\b/' => 'Dúvida',
    '/\bduvidas\b/' => 'dúvidas',
    '/\bDuvidas\b/' => 'Dúvidas',
    '/\bpossivel\b/' => 'possível',
    '/\bPossivel\b/' => 'Possível',
    '/\bpermissao\b/' => 'permissão',
    '/\bPermissao\b/' => 'Permissão',
    '/\bproprio\b/' => 'próprio',
    '/\bProprio\b/' => 'Próprio',
    '/\bseguranca\b/' => 'segurança',
    '/\bSeguranca\b/' => 'Segurança',
    '/\brapido\b/' => 'rápido',
    '/\bRapido\b/' => 'Rápido',
    '/\btecnico\b/' => 'técnico',
    '/\bTecnico\b/' => 'Técnico',
    '/\bacademico\b/' => 'académico',
    '/\bAcademico\b/' => 'Académico',
    '/\bproxima\b/' => 'próxima',
    '/\bProxima\b/' => 'Próxima',
    '/\bsessao\b/' => 'sessão',
    '/\bSessao\b/' => 'Sessão',
    '/\baprovacao\b/' => 'aprovação',
    '/\bAprovacao\b/' => 'Aprovação',

    // Gender Fixes
    '/\buma projecto\b/i' => 'um projecto',
    '/\buma boa projecto\b/i' => 'um bom projecto',
    '/\ba teu projecto\b/i' => 'o teu projecto',
    '/\btua projecto\b/i' => 'teu projecto',
    '/\ba seu projecto\b/i' => 'o seu projecto',
    '/\bsua projecto\b/i' => 'seu projecto',
    '/\ba meu projecto\b/i' => 'o meu projecto',
    '/\bminha projecto\b/i' => 'meu projecto',
    '/\baquela projecto\b/i' => 'aquele projecto',
    '/\bda projecto\b/i' => 'do projecto',
    '/\bna projecto\b/i' => 'no projecto',
    '/\bnesta projecto\b/i' => 'neste projecto',
    '/\bessa projecto\b/i' => 'esse projecto',
    '/\besta projecto\b/i' => 'este projecto',
    '/\bnova projecto\b/i' => 'novo projecto',
    '/\bprimeira projecto\b/i' => 'primeiro projecto',
    '/\boutra projecto\b/i' => 'outro projecto',
    '/\bQual é o projecto\b/i' => 'Qual é o projecto',
    '/\ba projecto\b/i' => 'o projecto',
    '/\bPROJECTO \/ PROJECTO\b/i' => 'PROJECTO',
    '/\buma projecto não age sozinha\b/i' => 'um projecto não age sozinho',
    '/\bUma projecto não age sozinha\b/i' => 'Um projecto não age sozinho',
    
    '/\bumas projectos\b/i' => 'uns projectos',
    '/\bas teus projectos\b/i' => 'os teus projectos',
    '/\btuas projectos\b/i' => 'teus projectos',
    '/\bas seus projectos\b/i' => 'os seus projectos',
    '/\bsuas projectos\b/i' => 'seus projectos',
    '/\bas meus projectos\b/i' => 'os meus projectos',
    '/\bminhas projectos\b/i' => 'meus projectos',
    '/\baquelas projectos\b/i' => 'aqueles projectos',
    '/\bdas projectos\b/i' => 'dos projectos',
    '/\bnas projectos\b/i' => 'nos projectos',
    '/\bnestas projectos\b/i' => 'nestes projectos',
    '/\bessas projectos\b/i' => 'esses projectos',
    '/\bestas projectos\b/i' => 'estes projectos',
    '/\bnovas projectos\b/i' => 'novos projectos',
    '/\bprimeiras projectos\b/i' => 'primeiros projectos',
    '/\boutras projectos\b/i' => 'outros projectos',
    '/\bas projectos\b/i' => 'os projectos',
];

foreach($regex as $file) {
    $filePath = $file[0];
    if (strpos($filePath, 'node_modules') !== false || strpos($filePath, '.git') !== false) {
        continue;
    }

    $content = file_get_contents($filePath);
    if ($content === false) continue;

    $original = $content;

    foreach ($replacements as $pattern => $replacement) {
        $content = preg_replace($pattern, $replacement, $content);
    }

    if ($original !== $content) {
        file_put_contents($filePath, $content);
        echo "Fixed: $filePath\n";
    }
}
?>
