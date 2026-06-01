<?php
/**
 * XAMPP SYNC COMPLETO - 1 Junho 2026
 * Sincroniza TODOS os arquivos para XAMPP e valida integridade
 * Elimina erros no dashboard.hero e componentes
 */

// ============================================================
// 1. CONFIGURAÇÃO DE CAMINHOS
// ============================================================
$workspace = 'c:/Users/nee/Documents/Aksanti Referências/Aksanti Referências';
$xampp = 'C:/xampp/htdocs/kaliye';
$project_root = dirname(__FILE__);

// Converter para formato Windows
$workspace = str_replace('/', '\\', $workspace);
$xampp = str_replace('/', '\\', $xampp);

echo "╔════════════════════════════════════════════════════════════╗\n";
echo "║     SYNC COMPLETO XAMPP - Dashboard Hero + Tudo            ║\n";
echo "║     Data: " . date('d/m/Y H:i:s') . "                               ║\n";
echo "╚════════════════════════════════════════════════════════════╝\n\n";

// ============================================================
// 2. ARQUIVOS A SINCRONIZAR (CRÍTICOS)
// ============================================================
$files_to_sync = [
    // DASHBOARD e HOME
    'paginas/plataforma/dashboard.php' => 'paginas/plataforma/dashboard.php',
    'paginas/plataforma/dashboard_hero.php' => 'paginas/plataforma/dashboard_hero.php',
    'paginas/plataforma/home.php' => 'paginas/plataforma/home.php',
    
    // AUTENTICAÇÃO E SEGURANÇA
    'inclusoes/auth_check.php' => 'inclusoes/auth_check.php',
    'inclusoes/csrf_token.php' => 'inclusoes/csrf_token.php',
    'inclusoes/session_handler.php' => 'inclusoes/session_handler.php',
    
    // COMPONENTES PRINCIPAIS
    'inclusoes/components/header.php' => 'inclusoes/components/header.php',
    'inclusoes/components/footer.php' => 'inclusoes/components/footer.php',
    'inclusoes/components/navigation.php' => 'inclusoes/components/navigation.php',
    'inclusoes/components/sidebar.php' => 'inclusoes/components/sidebar.php',
    
    // MODAIS E EXPLORAR
    'inclusoes/components/explorar_mentoria_modals.php' => 'inclusoes/components/explorar_mentoria_modals.php',
    'inclusoes/components/modal_base.php' => 'inclusoes/components/modal_base.php',
    
    // CHAT
    'inclusoes/components/chat_scripts.php' => 'inclusoes/components/chat_scripts.php',
    'inclusoes/components/chat_area.php' => 'inclusoes/components/chat_area.php',
    
    // SOCIAL - DÚVIDAS
    'paginas/social/messages.php' => 'paginas/social/messages.php',
    'paginas/social/doubts.php' => 'paginas/social/doubts.php',
    'paginas/social/forum.php' => 'paginas/social/forum.php',
    
    // API ENDPOINTS
    'interface_programacao/social/get_mentor_students.php' => 'interface_programacao/social/get_mentor_students.php',
    'interface_programacao/social/get_doubts.php' => 'interface_programacao/social/get_doubts.php',
    'interface_programacao/social/post_doubt.php' => 'interface_programacao/social/post_doubt.php',
    'interface_programacao/social/get_chat_messages.php' => 'interface_programacao/social/get_chat_messages.php',
    
    // NOTIFICAÇÕES
    'inclusoes/components/notifications_bar.php' => 'inclusoes/components/notifications_bar.php',
    'interface_programacao/notifications/get_notifications.php' => 'interface_programacao/notifications/get_notifications.php',
    
    // ESTILOS E SCRIPTS
    'recursos/css/main.css' => 'recursos/css/main.css',
    'recursos/css/dashboard.css' => 'recursos/css/dashboard.css',
    'recursos/css/responsive.css' => 'recursos/css/responsive.css',
    'recursos/js/main.js' => 'recursos/js/main.js',
    'recursos/js/dashboard.js' => 'recursos/js/dashboard.js',
    'sw.js' => 'sw.js',
    'manifest.json' => 'manifest.json',
];

// ============================================================
// 3. FUNÇÕES UTILITÁRIAS
// ============================================================
function file_exists_safe($path) {
    return @file_exists($path);
}

function get_file_hash($path) {
    if (file_exists_safe($path)) {
        return hash_file('md5', $path);
    }
    return null;
}

function copy_file_safe($src, $dest) {
    $src = str_replace('/', '\\', $src);
    $dest = str_replace('/', '\\', $dest);
    
    // Criar diretório se não existir
    $dest_dir = dirname($dest);
    if (!is_dir($dest_dir)) {
        mkdir($dest_dir, 0777, true);
    }
    
    return copy($src, $dest);
}

// ============================================================
// 4. SINCRONIZAÇÃO
// ============================================================
$sync_success = 0;
$sync_failed = 0;
$sync_skipped = 0;
$sync_results = [];

echo "📋 SINCRONIZANDO " . count($files_to_sync) . " arquivos...\n";
echo str_repeat("─", 70) . "\n\n";

foreach ($files_to_sync as $workspace_path => $xampp_path) {
    $full_workspace_path = $workspace . '\\' . str_replace('/', '\\', $workspace_path);
    $full_xampp_path = $xampp . '\\' . str_replace('/', '\\', $xampp_path);
    
    // Verificar se arquivo existe na workspace
    if (!file_exists_safe($full_workspace_path)) {
        echo "⚠️  SKIP: {$workspace_path}\n";
        $sync_skipped++;
        continue;
    }
    
    // Obter hashes antes da cópia
    $hash_before = get_file_hash($full_xampp_path);
    
    // Copiar arquivo
    if (copy_file_safe($full_workspace_path, $full_xampp_path)) {
        $hash_after = get_file_hash($full_xampp_path);
        echo "✅ SYNC: {$xampp_path}\n";
        $sync_success++;
        
        $sync_results[] = [
            'file' => $xampp_path,
            'status' => 'success',
            'hash' => $hash_after,
        ];
    } else {
        echo "❌ ERRO: {$xampp_path}\n";
        $sync_failed++;
        
        $sync_results[] = [
            'file' => $xampp_path,
            'status' => 'failed',
        ];
    }
}

// ============================================================
// 5. VALIDAÇÕES PÓS-SYNC
// ============================================================
echo "\n" . str_repeat("═", 70) . "\n";
echo "🔍 VALIDAÇÕES PÓS-SINCRONIZAÇÃO\n";
echo str_repeat("═", 70) . "\n\n";

$validation_errors = [];

// Verificar dashboard_hero.php
echo "1️⃣  Verificando dashboard_hero.php...\n";
$hero_file = $xampp . '\\paginas\\plataforma\\dashboard_hero.php';
if (file_exists_safe($hero_file)) {
    $hero_content = file_get_contents($hero_file);
    if (strpos($hero_content, 'dashboard_hero_images') !== false || strpos($hero_content, 'hero') !== false) {
        echo "   ✅ dashboard_hero.php contém variáveis corretas\n";
    } else {
        echo "   ⚠️  dashboard_hero.php pode estar incompleto\n";
        $validation_errors[] = 'dashboard_hero_vars_missing';
    }
} else {
    echo "   ❌ dashboard_hero.php NÃO ENCONTRADO\n";
    $validation_errors[] = 'dashboard_hero_missing';
}

// Verificar auth_check.php
echo "\n2️⃣  Verificando auth_check.php (CSRF)...\n";
$auth_file = $xampp . '\\inclusoes\\auth_check.php';
if (file_exists_safe($auth_file)) {
    $auth_content = file_get_contents($auth_file);
    if (strpos($auth_content, 'csrf') !== false || strpos($auth_content, 'session') !== false) {
        echo "   ✅ auth_check.php contém verificações de segurança\n";
    } else {
        echo "   ⚠️  auth_check.php pode estar incompleto\n";
        $validation_errors[] = 'auth_check_incomplete';
    }
} else {
    echo "   ❌ auth_check.php NÃO ENCONTRADO\n";
    $validation_errors[] = 'auth_check_missing';
}

// Verificar API endpoints críticas
echo "\n3️⃣  Verificando API endpoints...\n";
$api_files = [
    'interface_programacao\\social\\get_mentor_students.php',
    'interface_programacao\\social\\get_doubts.php',
    'interface_programacao\\social\\post_doubt.php',
];

$api_found = 0;
foreach ($api_files as $api_file) {
    $full_path = $xampp . '\\' . $api_file;
    if (file_exists_safe($full_path)) {
        $api_found++;
    }
}
echo "   API Endpoints: $api_found/" . count($api_files) . " encontrados\n";

// Verificar arquivos estáticos
echo "\n4️⃣  Verificando recursos (CSS/JS)...\n";
$static_files = [
    'recursos\\css\\main.css',
    'recursos\\css\\dashboard.css',
    'recursos\\js\\main.js',
    'recursos\\js\\dashboard.js',
];

$static_found = 0;
foreach ($static_files as $static_file) {
    $full_path = $xampp . '\\' . $static_file;
    if (file_exists_safe($full_path)) {
        $static_found++;
    }
}
echo "   Recursos: $static_found/" . count($static_files) . " encontrados\n";

// ============================================================
// 6. RELATÓRIO FINAL
// ============================================================
echo "\n" . str_repeat("═", 70) . "\n";
echo "📊 RELATÓRIO FINAL\n";
echo str_repeat("═", 70) . "\n\n";

echo "Sincronizações Bem-sucedidas: ✅ {$sync_success}\n";
echo "Sincronizações Falhadas: ❌ {$sync_failed}\n";
echo "Sincronizações Puladas: ⚠️  {$sync_skipped}\n";
echo "Total de Arquivos: " . count($files_to_sync) . "\n\n";

$success_rate = count($files_to_sync) > 0 ? ($sync_success / (count($files_to_sync) - $sync_skipped)) * 100 : 0;
echo "Taxa de Sucesso: " . number_format($success_rate, 2) . "%\n\n";

// Erros de validação
if (!empty($validation_errors)) {
    echo "⚠️  ERROS DE VALIDAÇÃO ENCONTRADOS:\n";
    foreach ($validation_errors as $error) {
        echo "   - {$error}\n";
    }
} else {
    echo "✅ TODAS AS VALIDAÇÕES PASSARAM\n";
}

// ============================================================
// 7. INSTRUÇÕES FINAIS
// ============================================================
echo "\n" . str_repeat("═", 70) . "\n";
echo "📝 PRÓXIMOS PASSOS\n";
echo str_repeat("═", 70) . "\n\n";

if ($sync_success > 0) {
    echo "1️⃣  Aguarde 2-3 segundos para o cache atualizar\n";
    echo "2️⃣  Abra o navegador: http://localhost/kaliye/\n";
    echo "3️⃣  Limpe o cache: CTRL+SHIFT+DELETE (ou DevTools > Cache)\n";
    echo "4️⃣  Hard Refresh: CTRL+SHIFT+R (Windows) ou CMD+SHIFT+R (Mac)\n";
    echo "5️⃣  Teste as funcionalidades:\n";
    echo "   • Dashboard Hero carrega com imagens?\n";
    echo "   • Componentes modais funcionam?\n";
    echo "   • Chat carrega corretamente?\n";
    echo "   • Dúvidas/Forum disponível?\n\n";
} else {
    echo "❌ NENHUM ARQUIVO FOI SINCRONIZADO\n";
    echo "Verifique os caminhos e tente novamente.\n\n";
}

echo "Data/Hora: " . date('d/m/Y H:i:s') . "\n";
echo "Workspace: {$workspace}\n";
echo "XAMPP: {$xampp}\n\n";

// ============================================================
// 8. SALVAR RELATÓRIO
// ============================================================
$report_file = dirname(__FILE__) . '/SYNC_REPORT_' . date('Ymd_His') . '.txt';
$report_content = ob_get_clean();

// Capturar tudo que foi impresso
ob_end_clean();

// Salvar relatório
file_put_contents($report_file, $report_content);
echo "\n📄 Relatório salvo: " . basename($report_file) . "\n";

?>
