<?php
/**
 * VALIDAÇÃO ARQUITETURAL DE FAVICON
 * Garante que o favicon seja 100% consistente, centralizado e protegido contra regressões.
 * Deteta e remove automaticamente favicons hardcoded.
 */

$base_dir = dirname(__DIR__);
$favicon_component = 'inclusoes/components/favicon.php';

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    VALIDAÇÃO ARQUITETURAL: FAVICON CENTRALIZADO            ║\n";
echo "║    Data: " . date('d/m/Y H:i:s') . "                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$iter = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($base_dir));
$files_checked = 0;
$files_fixed = 0;

foreach ($iter as $file) {
    if ($file->isFile()) {
        $path = $file->getPathname();
        
        // Skip the node_modules, git, and the central favicon component
        if (strpos($path, 'node_modules') !== false || strpos($path, '.git') !== false) {
            continue;
        }
        
        // Normaliza as barras para a verificação
        $normalized_path = str_replace('\\', '/', $path);
        if (strpos($normalized_path, $favicon_component) !== false) {
            continue;
        }

        $ext = $file->getExtension();
        if (in_array($ext, ['php', 'html'])) {
            $files_checked++;
            $content = file_get_contents($path);
            
            // Verifica se há favicons hardcoded (incluindo variações apple e antigas)
            $hasIcon = preg_match('/<link[^>]*rel=["\'](?:shortcut )?icon["\'][^>]*>/i', $content) || preg_match('/<link[^>]*rel=["\']apple-touch-icon["\'][^>]*>/i', $content);
            
            if ($hasIcon) {
                echo "⚠️ VIOLAÇÃO ARQUITETURAL DETETADA: Favicon duplicado em " . str_replace($base_dir, '', $path) . "\n";
                
                // AUTO-REPARAÇÃO: Remover todas as tags de ícone
                $newContent = preg_replace('/<link[^>]*rel=["\'](?:shortcut )?icon["\'][^>]*>[\r\n]*/i', '', $content);
                $newContent = preg_replace('/<link[^>]*rel=["\']apple-touch-icon["\'][^>]*>[\r\n]*/i', '', $newContent);
                
                if ($ext === 'php') {
                    // Injetar chamada dinâmica ao componente central se houver tag </head>
                    $relative_depth = max(0, substr_count(str_replace('\\', '/', str_replace($base_dir, '', $path)), '/') - 1);
                    $prefix = str_repeat('../', $relative_depth);
                    
                    $replacement = "    <?php \n    if (!function_exists('renderKaliyeFavicons')) {\n        require_once __DIR__ . '/' . (\$base_url ?? '{$prefix}') . 'inclusoes/components/favicon.php';\n    }\n    renderKaliyeFavicons(\$base_url ?? './'); \n    ?>\n";
                    
                    if (strpos($newContent, '</head>') !== false && strpos($newContent, 'renderKaliyeFavicons') === false) {
                        $newContent = preg_replace('/(<\/head>)/i', $replacement . "$1", $newContent);
                    }
                }
                
                if ($content !== $newContent) {
                    file_put_contents($path, $newContent);
                    $files_fixed++;
                    echo "✅ CORREÇÃO APLICADA: Favicon hardcoded removido e centralizado.\n\n";
                }
            }
        }
    }
}

echo "══════════════════════════════════════════════════════════════════════\n";
echo "📊 RESUMO DA VALIDAÇÃO E SEGURANÇA\n";
echo "══════════════════════════════════════════════════════════════════════\n";
echo "Ficheiro Central Único: " . $favicon_component . "\n";
echo "Total de ficheiros verificados: $files_checked\n";
echo "Ficheiros corrigidos automaticamente: $files_fixed\n";
echo "Status Final: " . ($files_fixed > 0 ? "⚠️ Regressões corrigidas." : "✅ 100% Consistente. Nenhuma duplicação detetada.") . "\n\n";

?>
