<?php
session_start();
$admin_base = '../';
$base_url = '../../';
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin()) {
    header("Location: ../../autenticacao/entrar.php");
    exit();
}

$root = realpath(__DIR__ . '/../..');
$allowed_extensions = ['php', 'js', 'json', 'md', 'css'];
$ignored_dirs = [
    DIRECTORY_SEPARATOR . '.git' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'carregamentos' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'copias_seguranca' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR,
    DIRECTORY_SEPARATOR . 'node_modules' . DIRECTORY_SEPARATOR,
];

$checks = [
    [
        'label' => 'Rotulo antigo de projectos',
        'severity' => 'alta',
        'pattern' => '/\b(meus projectos|os meus projectos|projectos em alta|novo projecto|novos projectos|explorar projectos|pesquisar projectos|PROJECTOS)\b/iu',
        'hint' => 'Usar Projectos, Meus Projectos, Novo Projecto ou Projectos em destaque, conforme o contexto.',
    ],
    [
        'label' => 'Grafia brasileira ou não padronizada',
        'severity' => 'media',
        'pattern' => '/\b(usu[aá]rio|usu[aá]rios|gerenciar|gerencia|monitorar|controle|registro)\b/iu',
        'hint' => 'Preferir Utilizador, Utilizadores, Gerir, Gere, Monitorizar, Controlo e Registo.',
    ],
    [
        'label' => 'Projecto sem grafia angolana',
        'severity' => 'media',
        'pattern' => '/\b(projeto|projetos|Projeto|Projetos|PROJETO|PROJETOS)\b/u',
        'hint' => 'Padronizar para projecto/projectos quando for texto visivel ao utilizador.',
    ],
    [
        'label' => 'Possível falta de acento',
        'severity' => 'media',
        'pattern' => '/\b(não|Não|possível|Possível|permissão|Permissão|próprio|Próprio|validação|Validação|descrição|Descrição|informação|Informação|acção|Acção|sessão|Sessão|publicação|Publicação|aprovação|Aprovação|segurança|Segurança|histórico|Histórico|relatório|Relatório|dúvida|Dúvida|rápido|Rápido|técnico|Técnico|académico|Académico|próxima|Próxima)\b/u',
        'hint' => 'Corrigir acentuacao: não, possível, permissão, próprio, validação, descrição, informação, accao/acção, sessão, publicação, aprovação, segurança, histórico, relatório, dúvida, rápido, técnico, académico e próxima.',
    ],
    [
        'label' => 'Codificacao quebrada',
        'severity' => 'critica',
        'pattern' => '/(Ãƒ.|Ã‚.|ââ‚¬|ââ‚¬Å“|ââ‚¬ï¿½|Ã°Å¸|ï¿½)/u',
        'hint' => 'Rever o ficheiro em UTF-8 e corrigir caracteres acentuados que ficaram em mojibake.',
    ],
];

function auditParam(string $key, string $default = ''): string {
    return trim((string)($_GET[$key] ?? $default));
}

function splitAuditLines(string $content): array {
    $lines = preg_split('/\R/', $content);
    return is_array($lines) ? $lines : explode("\n", str_replace(["\r\n", "\r"], "\n", $content));
}

function csvCell($value): string {
    $value = (string)$value;
    return '"' . str_replace('"', '""', $value) . '"';
}

function auditLower(string $value): string {
    return function_exists('mb_strtolower') ? mb_strtolower($value, 'UTF-8') : strtolower($value);
}

$severity_filter = auditParam('severity', 'all');
$extension_filter = strtolower(auditParam('ext', 'all'));
$query_filter = auditParam('q');
$limit = (int)($_GET['limit'] ?? 250);
$limit = max(50, min(1000, $limit));

$results = [];
$totals = ['critica' => 0, 'alta' => 0, 'media' => 0];
$files_scanned = 0;
$files_with_encoding_risk = 0;
$extension_totals = [];
$file_totals = [];
$unreadable_files = [];
$scan_errors = [];

if ($root === false) {
    $scan_errors[] = 'Não foi possível localizar a raiz do projecto.';
} else {
    $iterator = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($root, FilesystemIterator::SKIP_DOTS)
    );

    foreach ($iterator as $file) {
        if (!$file->isFile()) {
            continue;
        }

        $path = $file->getPathname();
        $relative = str_replace($root . DIRECTORY_SEPARATOR, '', $path);
        $relative = str_replace(DIRECTORY_SEPARATOR, '/', $relative);
        $extension = strtolower(pathinfo($path, PATHINFO_EXTENSION));

        if ($relative === 'administracao/system/content_audit.php') {
            continue;
        }

        if (!in_array($extension, $allowed_extensions, true)) {
            continue;
        }

        if ($extension_filter !== 'all' && $extension !== $extension_filter) {
            continue;
        }

        $skip = false;
        foreach ($ignored_dirs as $ignored_dir) {
            if (strpos($path, $ignored_dir) !== false) {
                $skip = true;
                break;
            }
        }
        if ($skip) {
            continue;
        }

        $content = @file_get_contents($path);
        if ($content === false) {
            $unreadable_files[] = $relative;
            continue;
        }

        $files_scanned++;
        $extension_totals[$extension] = ($extension_totals[$extension] ?? 0) + 1;

        $has_invalid_utf8 = preg_match('//u', $content) !== 1;
        if ($has_invalid_utf8) {
            $files_with_encoding_risk++;
            $totals['critica']++;
            $file_totals[$relative] = ($file_totals[$relative] ?? 0) + 1;
            $results[] = [
                'file' => $relative,
                'line' => 1,
                'label' => 'Ficheiro com bytes invalidos',
                'severity' => 'critica',
                'match' => 'UTF-8 invalido',
                'hint' => 'Abrir o ficheiro como UTF-8 e gravar novamente antes de rever textos visiveis.',
                'preview' => 'O ficheiro contem sequencias que podem quebrar expressoes regulares Unicode.',
            ];
        }

        $lines = splitAuditLines($content);
        foreach ($lines as $index => $line) {
            foreach ($checks as $check) {
                $matched = @preg_match($check['pattern'], $line, $match);
                if ($matched !== 1) {
                    continue;
                }

                $severity = $check['severity'];
                $totals[$severity]++;
                $file_totals[$relative] = ($file_totals[$relative] ?? 0) + 1;
                $results[] = [
                    'file' => $relative,
                    'line' => $index + 1,
                    'label' => $check['label'],
                    'severity' => $severity,
                    'match' => $match[0],
                    'hint' => $check['hint'],
                    'preview' => trim(substr($line, 0, 220)),
                ];
            }
        }
    }
}

if ($severity_filter !== 'all') {
    $results = array_values(array_filter($results, fn($item) => $item['severity'] === $severity_filter));
}

if ($query_filter !== '') {
    $needle = auditLower($query_filter);
    $results = array_values(array_filter($results, function ($item) use ($needle) {
        $haystack = auditLower(implode(' ', [$item['file'], $item['label'], $item['match'], $item['hint'], $item['preview']]));
        return strpos($haystack, $needle) !== false;
    }));
}

usort($results, function ($a, $b) {
    $order = ['critica' => 0, 'alta' => 1, 'media' => 2];
    return [$order[$a['severity']] ?? 9, $a['file'], $a['line']] <=> [$order[$b['severity']] ?? 9, $b['file'], $b['line']];
});

arsort($file_totals);
$top_files = array_slice($file_totals, 0, 8, true);
$visible_results = array_slice($results, 0, $limit);
$total_findings = count($results);
$risk_score = ($totals['critica'] * 5) + ($totals['alta'] * 3) + $totals['media'];
$estimated_minutes = max(0, (int)ceil(($totals['critica'] * 8) + ($totals['alta'] * 4) + ($totals['media'] * 2)));
$next_action = $totals['critica'] > 0 ? 'Corrigir codificacao UTF-8' : ($totals['alta'] > 0 ? 'Actualizar rotulos antigos' : 'Rever padronizacao fina');

if (($_GET['format'] ?? '') === 'csv') {
    header('Content-Type: text/csv; charset=UTF-8');
    header('Content-Disposition: attachment; filename=kaliye_content_audit_' . date('Y-m-d_H-i') . '.csv');
    echo "\xEF\xBB\xBF";
    echo "prioridade,ficheiro,linha,ocorrencia,sugestao,preview\n";
    foreach ($results as $item) {
        echo implode(',', [
            csvCell($item['severity']),
            csvCell($item['file']),
            csvCell($item['line']),
            csvCell($item['match']),
            csvCell($item['hint']),
            csvCell($item['preview']),
        ]) . "\n";
    }
    exit();
}
?>
<!DOCTYPE html>
<html lang="pt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Auditoria Linguistica - KALIYE Admin</title>
    <link rel="icon" type="image/png" sizes="32x32" href="<?= $base_url ?>recursos/images/marca/favicon-k-32x32.png">
    <link rel="stylesheet" href="../../recursos/css/style.css">
    <link rel="stylesheet" href="../../recursos/css/pages/admin_dashboard.css?v=<?= filemtime(__DIR__ . '/../../recursos/css/pages/admin_dashboard.css') ?>">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        .audit-toolbar, .audit-automation, .audit-grid { display: grid; gap: 1rem; }
        .audit-toolbar { grid-template-columns: 1fr auto; align-items: end; margin: 1.5rem 0; }
        .audit-filters { display: grid; grid-template-columns: 1.2fr repeat(3, minmax(120px, 0.35fr)); gap: 0.75rem; }
        .audit-input, .audit-select { width: 100%; min-height: 42px; border-radius: 10px; border: 1px solid rgba(255,255,255,0.1); background: rgba(255,255,255,0.045); color: #fff; padding: 0 0.85rem; font-weight: 700; }
        .audit-select option { background: #0d1628; color: #fff; }
        .audit-button { min-height: 42px; border-radius: 10px; border: 1px solid rgba(247,148,29,0.25); background: rgba(247,148,29,0.14); color: #f7941d; padding: 0 1rem; font-weight: 900; text-decoration: none; display: inline-flex; align-items: center; justify-content: center; gap: 0.5rem; cursor: pointer; }
        .audit-button.primary { background: #f7941d; color: #07111f; border-color: #f7941d; }
        .audit-summary { display: grid; grid-template-columns: repeat(5, minmax(150px, 1fr)); gap: 1rem; margin: 1rem 0; }
        .audit-card, .audit-panel { background: rgba(255,255,255,0.035); border: 1px solid rgba(255,255,255,0.07); border-radius: 12px; padding: 1.15rem; }
        .audit-card strong { display: block; color: #fff; font-size: 1.75rem; line-height: 1; }
        .audit-card span, .panel-label { color: rgba(255,255,255,0.55); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; font-weight: 800; }
        .audit-automation { grid-template-columns: 1.1fr 0.9fr; margin: 1.25rem 0; }
        .automation-list { display: grid; gap: 0.7rem; margin-top: 0.9rem; }
        .automation-item { display: flex; justify-content: space-between; gap: 1rem; padding: 0.75rem; border-radius: 10px; background: rgba(255,255,255,0.035); color: rgba(255,255,255,0.76); font-size: 0.82rem; }
        .automation-item strong { color: #fff; }
        .audit-table { width: 100%; border-collapse: collapse; overflow: hidden; border-radius: 12px; background: rgba(255,255,255,0.025); }
        .audit-table th, .audit-table td { padding: 0.85rem 0.95rem; border-bottom: 1px solid rgba(255,255,255,0.06); text-align: left; vertical-align: top; }
        .audit-table th { color: rgba(255,255,255,0.52); font-size: 0.72rem; text-transform: uppercase; letter-spacing: 1px; }
        .audit-table td { color: rgba(255,255,255,0.82); font-size: 0.82rem; line-height: 1.45; }
        .severity { display: inline-flex; padding: 0.25rem 0.55rem; border-radius: 999px; font-size: 0.68rem; font-weight: 900; text-transform: uppercase; }
        .severity-critica { background: rgba(239,68,68,0.14); color: #fca5a5; }
        .severity-alta { background: rgba(247,148,29,0.16); color: #fbbf24; }
        .severity-media { background: rgba(59,130,246,0.14); color: #93c5fd; }
        .audit-file { color: #f7941d; font-weight: 800; }
        .audit-preview { color: rgba(255,255,255,0.58); font-family: Consolas, monospace; font-size: 0.76rem; word-break: break-word; }
        .audit-note { padding: 1rem 1.2rem; border-radius: 12px; background: rgba(16,185,129,0.08); border: 1px solid rgba(16,185,129,0.18); color: rgba(255,255,255,0.72); margin-bottom: 1rem; }
        .progress-track { height: 8px; border-radius: 999px; background: rgba(255,255,255,0.08); overflow: hidden; margin-top: 0.5rem; }
        .progress-fill { height: 100%; border-radius: 999px; background: linear-gradient(90deg, #ef4444, #f7941d, #10b981); width: <?= min(100, max(6, $risk_score)) ?>%; }
        @media (max-width: 1100px) { .audit-summary { grid-template-columns: 1fr 1fr; } .audit-toolbar, .audit-automation, .audit-filters { grid-template-columns: 1fr; } .audit-table { display: block; overflow-x: auto; } }
    </style>
</head>
<body>
    <?php include '../barra_lateral.php'; ?>

    <main class="admin-main-content">
        <header class="dashboard-header">
            <div class="header-title">
                <a href="reports.php" style="color: var(--aksanti-orange); text-decoration: none; font-size: 0.85rem; font-weight: 700; display: inline-flex; align-items: center; gap: 0.5rem; margin-bottom: 0.5rem; text-transform: uppercase; letter-spacing: 1px;">
                    <i class="fas fa-arrow-left"></i> Voltar ao Hub
                </a>
                <h1>Auditoria Linguistica</h1>
                <p style="color: rgba(255,255,255,0.5); font-weight: 500;">Monitoriza textos visiveis, acentuacao, grafia angolana e vestigios de codificacao quebrada.</p>
            </div>
            <div style="background: rgba(247,148,29,0.1); color: var(--aksanti-orange); padding: 0.55rem 1rem; border-radius: 10px; font-size: 0.75rem; font-weight: 900; border: 1px solid rgba(247,148,29,0.2);">
                <i class="fas fa-language"></i> PT-AO QUALITY
            </div>
        </header>

        <form class="audit-toolbar" method="get">
            <div class="audit-filters">
                <input class="audit-input" type="search" name="q" value="<?= htmlspecialchars($query_filter) ?>" placeholder="Pesquisar ficheiro, ocorrencia ou sugestao">
                <select class="audit-select" name="severity">
                    <?php foreach (['all' => 'Todas', 'critica' => 'Critica', 'alta' => 'Alta', 'media' => 'Media'] as $value => $label): ?>
                        <option value="<?= $value ?>" <?= $severity_filter === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="audit-select" name="ext">
                    <option value="all">Todas extensoes</option>
                    <?php foreach ($allowed_extensions as $ext): ?>
                        <option value="<?= $ext ?>" <?= $extension_filter === $ext ? 'selected' : '' ?>>.<?= $ext ?></option>
                    <?php endforeach; ?>
                </select>
                <select class="audit-select" name="limit">
                    <?php foreach ([100, 250, 500, 1000] as $option): ?>
                        <option value="<?= $option ?>" <?= $limit === $option ? 'selected' : '' ?>><?= $option ?> linhas</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="display:flex; gap:0.6rem; flex-wrap:wrap;">
                <button class="audit-button primary" type="submit"><i class="fas fa-filter"></i> Filtrar</button>
                <a class="audit-button" href="?<?= htmlspecialchars(http_build_query(array_merge($_GET, ['format' => 'csv']))) ?>"><i class="fas fa-file-csv"></i> CSV</a>
            </div>
        </form>

        <?php if ($scan_errors): ?>
            <div class="audit-note" style="background: rgba(239,68,68,0.08); border-color: rgba(239,68,68,0.18);">
                <?= htmlspecialchars(implode(' ', $scan_errors)) ?>
            </div>
        <?php endif; ?>

        <section class="audit-summary">
            <div class="audit-card"><strong><?= number_format($files_scanned, 0, ',', '.') ?></strong><span>Ficheiros analisados</span></div>
            <div class="audit-card"><strong><?= number_format($totals['critica'], 0, ',', '.') ?></strong><span>Criticos UTF-8</span></div>
            <div class="audit-card"><strong><?= number_format($totals['alta'], 0, ',', '.') ?></strong><span>Rotulos antigos</span></div>
            <div class="audit-card"><strong><?= number_format($total_findings, 0, ',', '.') ?></strong><span>Ocorrencias filtradas</span></div>
            <div class="audit-card"><strong><?= number_format($estimated_minutes, 0, ',', '.') ?>m</strong><span>Esforco estimado</span></div>
        </section>

        <section class="audit-automation">
            <div class="audit-panel">
                <span class="panel-label">Fila automatizada</span>
                <h3 style="margin:0.35rem 0; color:#fff;">Próxima accao: <?= htmlspecialchars($next_action) ?></h3>
                <p style="margin:0; color:rgba(255,255,255,0.58); font-size:0.86rem;">Pontuacao de risco <?= (int)$risk_score ?>, com prioridade a ficheiros que podem quebrar Unicode e rotulos visiveis antigos.</p>
                <div class="progress-track"><div class="progress-fill"></div></div>
                <div class="automation-list">
                    <div class="automation-item"><strong>Exportacao operacional</strong><span>CSV pronto para delegar revisoes</span></div>
                    <div class="automation-item"><strong>Deteccao resiliente</strong><span><?= (int)$files_with_encoding_risk ?> ficheiro(s) com risco UTF-8</span></div>
                    <div class="automation-item"><strong>Escopo seguro</strong><span>Nenhum ficheiro e alterado automaticamente</span></div>
                </div>
            </div>
            <div class="audit-panel">
                <span class="panel-label">Top ficheiros para revisao</span>
                <div class="automation-list">
                    <?php if (!$top_files): ?>
                        <div class="automation-item"><strong>Sem pendencias</strong><span>Auditoria limpa nos filtros actuais</span></div>
                    <?php endif; ?>
                    <?php foreach ($top_files as $file => $count): ?>
                        <div class="automation-item"><strong><?= htmlspecialchars($file) ?></strong><span><?= (int)$count ?> ocorrencia(s)</span></div>
                    <?php endforeach; ?>
                </div>
            </div>
        </section>

        <div class="audit-note">
            Esta auditoria agora tolera ficheiros com codificacao danificada, sinaliza o risco e continua a varredura. Use os filtros para priorizar e o CSV para criar filas de revisao.
        </div>

        <table class="audit-table">
            <thead>
                <tr>
                    <th>Prioridade</th>
                    <th>Ficheiro</th>
                    <th>Ocorrencia</th>
                    <th>Sugestao</th>
                    <th>Pre-visualizacao</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!$visible_results): ?>
                    <tr>
                        <td colspan="5" style="text-align:center; padding: 3rem; color: rgba(255,255,255,0.45);">Nenhuma ocorrencia encontrada nos criterios activos.</td>
                    </tr>
                <?php endif; ?>
                <?php foreach ($visible_results as $item): ?>
                    <?php $severity_class = ['critica' => 'critica', 'alta' => 'alta', 'media' => 'media'][$item['severity']] ?? 'media'; ?>
                    <tr>
                        <td><span class="severity severity-<?= htmlspecialchars($severity_class) ?>"><?= htmlspecialchars($item['severity']) ?></span><br><span style="color: rgba(255,255,255,0.35); font-size: 0.7rem;"><?= htmlspecialchars($item['label']) ?></span></td>
                        <td><span class="audit-file"><?= htmlspecialchars($item['file']) ?>:<?= (int)$item['line'] ?></span></td>
                        <td><?= htmlspecialchars($item['match']) ?></td>
                        <td><?= htmlspecialchars($item['hint']) ?></td>
                        <td class="audit-preview"><?= htmlspecialchars($item['preview']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </main>
</body>
</html>
