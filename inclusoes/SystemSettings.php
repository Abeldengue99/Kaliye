<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';

function getSystemSetting(PDO $db, string $key, ?string $default = null): ?string {
    static $cache = [];
    $cacheKey = spl_object_id($db) . ':' . $key;

    if (!array_key_exists($cacheKey, $cache)) {
        $stmt = $db->prepare("SELECT setting_value FROM settings WHERE setting_key = ?");
        $stmt->execute([$key]);
        $value = $stmt->fetchColumn();
        $cache[$cacheKey] = $value === false ? $default : (string)$value;
    }

    return $cache[$cacheKey];
}

function systemSettingEnabled(PDO $db, string $key, bool $default = false): bool {
    $value = getSystemSetting($db, $key, $default ? '1' : '0');
    return in_array(strtolower((string)$value), ['1', 'true', 't', 'yes', 'y', 'on'], true);
}

function currentRequestIsAdminArea(): bool {
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    return strpos($script, '/administracao/') !== false || strpos($script, '/interface_programacao/admin/') !== false;
}

function enforceMaintenanceMode(PDO $db, string $baseUrl = ''): void {
    if (!systemSettingEnabled($db, 'maintenance_mode', false)) {
        return;
    }

    $isAdminSession = isset($_SESSION['user_type']) && in_array($_SESSION['user_type'], ['admin', 'superadmin'], true);
    if ($isAdminSession && currentRequestIsAdminArea()) {
        return;
    }

    http_response_code(503);
    header('Retry-After: 3600');
    $siteName = htmlspecialchars(getSystemSetting($db, 'site_name', 'KALIYE') ?? 'KALIYE', ENT_QUOTES, 'UTF-8');
    $loginUrl = htmlspecialchars($baseUrl . 'autenticacao/entrar.php', ENT_QUOTES, 'UTF-8');
    echo '<!DOCTYPE html><html lang="pt"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width, initial-scale=1.0"><title>Manutencao - ' . $siteName . '</title><style>body{margin:0;min-height:100vh;display:flex;align-items:center;justify-content:center;background:#070d1a;color:#fff;font-family:Inter,Arial,sans-serif}.box{max-width:560px;padding:32px;text-align:center}.tag{display:inline-flex;padding:8px 12px;border:1px solid rgba(247,148,29,.35);border-radius:999px;color:#f7941d;font-size:12px;font-weight:800;text-transform:uppercase;letter-spacing:.08em}h1{font-size:32px;margin:18px 0 10px}p{color:#94a3b8;line-height:1.6}a{color:#f7941d;text-decoration:none;font-weight:700}</style></head><body><main class="box"><span class="tag">Manutencao</span><h1>' . $siteName . ' esta temporariamente indisponivel</h1><p>Estamos a aplicar melhorias e verificacoes tecnicas. A equipa administrativa continua com acesso ao painel para concluir a operacao.</p><p><a href="' . $loginUrl . '">Entrar como administrador</a></p></main></body></html>';
    exit();
}
?>
