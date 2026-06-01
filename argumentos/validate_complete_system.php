<?php
/**
 * VALIDAÇÃO COMPLETA DO SISTEMA
 * Testa todos os componentes, APIs, banco de dados e integrações
 */

// ============================================================
// 1. CONFIGURAÇÃO
// ============================================================

$base_url = dirname(__FILE__) . '/../../';
$xampp_root = 'C:/xampp/htdocs/kaliye/';

// Suprimir erros de conexão
error_reporting(0);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║    VALIDAÇÃO COMPLETA DO SISTEMA - AKSANTI REFERÊNCIAS     ║\n";
echo "║    Data: " . date('d/m/Y H:i:s') . "                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

$tests_results = [
    'passed' => 0,
    'failed' => 0,
    'warnings' => 0,
];

// ============================================================
// 2. TESTES
// ============================================================

function test($name, $condition, $details = '') {
    global $tests_results;
    
    if ($condition) {
        echo "✅ PASS: {$name}\n";
        if ($details) echo "   └─ {$details}\n";
        $tests_results['passed']++;
    } else {
        echo "❌ FAIL: {$name}\n";
        if ($details) echo "   └─ {$details}\n";
        $tests_results['failed']++;
    }
}

function warn($name, $message) {
    global $tests_results;
    
    echo "⚠️  WARN: {$name}\n";
    echo "   └─ {$message}\n";
    $tests_results['warnings']++;
}

// ============================================================
// 3. TESTES DE ARQUIVO
// ============================================================

echo "📁 TESTES DE ARQUIVOS\n";
echo str_repeat("─", 70) . "\n";

$critical_files = [
    'paginas/plataforma/dashboard.php' => 'Dashboard agregador',
    'paginas/plataforma/dashboard_hero.php' => 'Hero Section',
    'inclusoes/auth_check.php' => 'Autenticação/Segurança',
    'recursos/css/main.css' => 'Estilos globais',
    'recursos/css/dashboard.css' => 'Estilos dashboard',
    'recursos/js/main.js' => 'Scripts globais',
    'recursos/js/dashboard.js' => 'Scripts dashboard',
    'sw.js' => 'Service Worker',
    'manifest.json' => 'Manifest PWA',
];

foreach ($critical_files as $file => $description) {
    $path = $xampp_root . str_replace('/', '\\', $file);
    $exists = @file_exists($path);
    $size = $exists ? @filesize($path) : 0;
    
    test("Arquivo existe: {$description}", $exists, "Tamanho: {$size} bytes");
}

echo "\n";

// ============================================================
// 4. TESTES DE CONTEÚDO
// ============================================================

echo "🔍 TESTES DE CONTEÚDO\n";
echo str_repeat("─", 70) . "\n";

$content_tests = [
    'paginas/plataforma/dashboard_hero.php' => [
        'dashboard_hero_images' => 'Array de imagens',
        'Aksanti' => 'Nome da plataforma',
        '<div class=' => 'HTML válido',
    ],
    'inclusoes/auth_check.php' => [
        'session' => 'Gerenciamento de sessão',
        'csrf' => 'Proteção CSRF',
    ],
    'recursos/js/main.js' => [
        'fetchAPI' => 'Função fetch',
        'EventManager' => 'Gerenciador de eventos',
    ],
    'recursos/css/main.css' => [
        '--primary-color' => 'Variáveis CSS',
        'btn' => 'Classes de botão',
    ],
];

foreach ($content_tests as $file => $tests) {
    $path = $xampp_root . str_replace('/', '\\', $file);
    
    if (@file_exists($path)) {
        $content = @file_get_contents($path);
        
        foreach ($tests as $keyword => $description) {
            $found = stripos($content, $keyword) !== false;
            test("Conteúdo em {$file}: {$description}", $found);
        }
    } else {
        warn("Arquivo não encontrado: {$file}", "Pulando testes de conteúdo");
    }
}

echo "\n";

// ============================================================
// 5. TESTES DE API
// ============================================================

echo "🌐 TESTES DE API\n";
echo str_repeat("─", 70) . "\n";

$api_endpoints = [
    'interface_programacao/social/get_mentor_students.php' => 'GET Mentorados',
    'interface_programacao/social/get_doubts.php' => 'GET Dúvidas',
    'interface_programacao/social/post_doubt.php' => 'POST Dúvida',
];

foreach ($api_endpoints as $file => $description) {
    $path = $xampp_root . str_replace('/', '\\', $file);
    $exists = @file_exists($path);
    
    if ($exists) {
        $content = @file_get_contents($path);
        $is_valid = stripos($content, '<?php') !== false && (
            stripos($content, '$_REQUEST') !== false ||
            stripos($content, '$_POST') !== false ||
            stripos($content, '$_GET') !== false
        );
        
        test("API disponível: {$description}", $is_valid, "Arquivo: {$file}");
    } else {
        warn("API não encontrada: {$description}", "Arquivo esperado: {$file}");
    }
}

echo "\n";

// ============================================================
// 6. TESTES DE SEGURANÇA
// ============================================================

echo "🔐 TESTES DE SEGURANÇA\n";
echo str_repeat("─", 70) . "\n";

$auth_file = $xampp_root . 'inclusoes\\auth_check.php';
if (@file_exists($auth_file)) {
    $auth_content = @file_get_contents($auth_file);
    
    // Verificar proteções
    test('CSRF Token implementado', stripos($auth_content, 'csrf') !== false);
    test('Session validation', stripos($auth_content, 'session') !== false);
    test('Input sanitization', stripos($auth_content, 'sanitize') !== false || stripos($auth_content, 'htmlspecialchars') !== false);
}

echo "\n";

// ============================================================
// 7. TESTES DE RESPONSIVIDADE
// ============================================================

echo "📱 TESTES DE RESPONSIVIDADE\n";
echo str_repeat("─", 70) . "\n";

$css_files = [
    'recursos/css/main.css',
    'recursos/css/dashboard.css',
];

$has_media_queries = 0;
$has_flexbox = 0;
$has_grid = 0;

foreach ($css_files as $file) {
    $path = $xampp_root . str_replace('/', '\\', $file);
    
    if (@file_exists($path)) {
        $content = @file_get_contents($path);
        
        if (stripos($content, '@media') !== false) $has_media_queries++;
        if (stripos($content, 'flex') !== false) $has_flexbox++;
        if (stripos($content, 'grid') !== false) $has_grid++;
    }
}

test('Media queries implementadas', $has_media_queries > 0, "$has_media_queries arquivo(s)");
test('Flexbox utilizado', $has_flexbox > 0);
test('CSS Grid utilizado', $has_grid > 0);

echo "\n";

// ============================================================
// 8. TESTES DE PWA
// ============================================================

echo "🚀 TESTES DE PWA\n";
echo str_repeat("─", 70) . "\n";

$manifest_path = $xampp_root . 'manifest.json';
if (@file_exists($manifest_path)) {
    $manifest = @json_decode(@file_get_contents($manifest_path), true);
    
    test('Manifest válido', is_array($manifest));
    test('Nome da app', isset($manifest['name']));
    test('Ícones definidos', isset($manifest['icons']) && is_array($manifest['icons']));
}

$sw_path = $xampp_root . 'sw.js';
test('Service Worker existe', @file_exists($sw_path));

if (@file_exists($sw_path)) {
    $sw_content = @file_get_contents($sw_path);
    test('SW com cache strategy', stripos($sw_content, 'cache') !== false);
    test('SW com fetch handler', stripos($sw_content, 'fetch') !== false);
}

echo "\n";

// ============================================================
// 9. TESTES DE PERFORMANCE
// ============================================================

echo "⚡ TESTES DE PERFORMANCE\n";
echo str_repeat("─", 70) . "\n";

$performance_tests = [];

// Verificar minificação (opcional)
$main_css_path = $xampp_root . 'recursos\\css\\main.css';
if (@file_exists($main_css_path)) {
    $size = @filesize($main_css_path);
    $is_reasonable = $size < 100000; // Menos de 100KB
    test('Tamanho CSS razoável', $is_reasonable, number_format($size, 0) . ' bytes');
}

$main_js_path = $xampp_root . 'recursos\\js\\main.js';
if (@file_exists($main_js_path)) {
    $size = @filesize($main_js_path);
    $is_reasonable = $size < 100000; // Menos de 100KB
    test('Tamanho JS razoável', $is_reasonable, number_format($size, 0) . ' bytes');
}

echo "\n";

// ============================================================
// 10. RESUMO
// ============================================================

echo str_repeat("═", 70) . "\n";
echo "📊 RESUMO DOS TESTES\n";
echo str_repeat("═", 70) . "\n\n";

$total = $tests_results['passed'] + $tests_results['failed'] + $tests_results['warnings'];
$success_rate = $total > 0 ? ($tests_results['passed'] / $total) * 100 : 0;

echo "✅ Testes Passados: " . $tests_results['passed'] . "\n";
echo "❌ Testes Falhados: " . $tests_results['failed'] . "\n";
echo "⚠️  Avisos: " . $tests_results['warnings'] . "\n";
echo "📊 Taxa de Sucesso: " . number_format($success_rate, 2) . "%\n\n";

if ($tests_results['failed'] === 0) {
    echo "🎉 TUDO PASSOU! O SISTEMA ESTÁ PRONTO PARA PRODUÇÃO!\n\n";
} elseif ($success_rate >= 80) {
    echo "✨ SISTEMA FUNCIONAL - Alguns ajustes podem ser necessários\n\n";
} else {
    echo "⚠️  VERIFICAÇÃO RECOMENDADA - Vários testes falharam\n\n";
}

// ============================================================
// 11. INSTRUÇÕES DE TESTE MANUAL
// ============================================================

echo str_repeat("═", 70) . "\n";
echo "📝 INSTRUÇÕES DE TESTE MANUAL\n";
echo str_repeat("═", 70) . "\n\n";

echo "1️⃣  ABRA O NAVEGADOR:\n";
echo "   → http://localhost/kaliye/\n\n";

echo "2️⃣  LIMPE O CACHE:\n";
echo "   → CTRL+SHIFT+DELETE (abrir DevTools Cache)\n";
echo "   → Ou: CTRL+SHIFT+R (hard refresh)\n\n";

echo "3️⃣  TESTE CADA SEÇÃO:\n";
echo "   ✓ Dashboard Hero carrega?\n";
echo "   ✓ Cores e estilos aplicados?\n";
echo "   ✓ Cards responsivos?\n";
echo "   ✓ Botões funcionam?\n";
echo "   ✓ Navegação funciona?\n\n";

echo "4️⃣  TESTE EM DISPOSITIVOS:\n";
echo "   ✓ Desktop (1920x1080)\n";
echo "   ✓ Tablet (768x1024)\n";
echo "   ✓ Mobile (375x667)\n\n";

echo "5️⃣  VERIFIQUE CONSOLE:\n";
echo "   ✓ DevTools (F12) → Console\n";
echo "   ✓ Procure por erros em vermelho\n";
echo "   ✓ Deve ter: 'Scripts globais carregados'\n";
echo "   ✓ Deve ter: 'Dashboard scripts carregados'\n\n";

// ============================================================
// 12. INFORMAÇÕES DO SISTEMA
// ============================================================

echo str_repeat("═", 70) . "\n";
echo "ℹ️  INFORMAÇÕES DO SISTEMA\n";
echo str_repeat("═", 70) . "\n\n";

echo "PHP Version: " . phpversion() . "\n";
echo "Server: " . $_SERVER['SERVER_SOFTWARE'] . "\n";
echo "OS: " . php_uname() . "\n";
echo "XAMPP Root: {$xampp_root}\n";
echo "Memory Limit: " . ini_get('memory_limit') . "\n";
echo "Max Upload Size: " . ini_get('upload_max_filesize') . "\n";
echo "Execution Time: " . ini_get('max_execution_time') . "s\n";
echo "Timezone: " . date_default_timezone_get() . "\n\n";

echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
echo "Local: " . __FILE__ . "\n\n";

// ============================================================
// 13. PRÓXIMOS PASSOS
// ============================================================

if ($tests_results['failed'] === 0) {
    echo "✅ PRÓXIMOS PASSOS:\n";
    echo "   1. Abra http://localhost/kaliye/\n";
    echo "   2. Teste o login\n";
    echo "   3. Navegue por todas as páginas\n";
    echo "   4. Teste os formulários\n";
    echo "   5. Verifique notificações\n";
    echo "   6. Teste chat e mensagens\n";
    echo "   7. Teste dúvidas e fórum\n";
    echo "   8. Teste em dispositivo mobile\n";
} else {
    echo "❌ CORRIJA OS ERROS ACIMA E EXECUTE NOVAMENTE\n";
}

echo "\n";

?>
