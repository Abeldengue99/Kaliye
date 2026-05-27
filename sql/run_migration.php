<?php
/**
 * sql/run_migration.php
 * Script de migração — Executa o SQL de Rate Limiting na base de dados PostgreSQL.
 * Pode ser corrido via CLI: php run_migration.php
 * OU via browser: http://localhost/Aksanti%20Refer%C3%AAncias/sql/run_migration.php
 */

// Segurança: apenas permitir execução local
$allowed_ips = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
if (php_sapi_name() !== 'cli' && !in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowed_ips)) {
    http_response_code(403);
    die('Acesso negado. Este script só pode ser executado localmente.');
}

$is_cli = (php_sapi_name() === 'cli');

function out(string $msg, string $type = 'info'): void {
    global $is_cli;
    if ($is_cli) {
        $prefix = ['info' => '[INFO]', 'ok' => '[ OK ]', 'error' => '[ERRO]', 'warn' => '[WARN]'];
        echo ($prefix[$type] ?? '[    ]') . ' ' . strip_tags($msg) . PHP_EOL;
    } else {
        $colors = ['info' => '#94a3b8', 'ok' => '#10b981', 'error' => '#ef4444', 'warn' => '#f59e0b'];
        $color  = $colors[$type] ?? '#fff';
        echo "<p style='color:{$color};margin:4px 0;font-family:monospace;'>{$msg}</p>";
    }
}

// Cabeçalho HTML (só se correr no browser)
if (!$is_cli): ?>
<!DOCTYPE html>
<html lang="pt"><head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Migração — Rate Limiting</title>
<link rel="icon" type="image/png" sizes="32x32" href="../recursos/images/marca/favicon-k-32x32.png">
<link rel="shortcut icon" href="../favicon-k.ico">
<style>
    body { background: #070d1a; color: #fff; font-family: monospace; padding: 2rem; }
    h1 { font-family: sans-serif; color: #f7941d; margin-bottom: 1rem; }
    .box { background: #0f172a; border: 1px solid rgba(255,255,255,0.08); border-radius: 12px; padding: 1.5rem; max-width: 700px; }
    .ok    { color: #10b981; } .error { color: #ef4444; }
    .warn  { color: #f59e0b; } .info  { color: #94a3b8; }
    hr { border-color: rgba(255,255,255,0.08); margin: 1rem 0; }
    .badge { display:inline-block; padding:0.2rem 0.7rem; border-radius:50px; font-size:0.75rem; font-weight:700; }
    .badge-ok { background:rgba(16,185,129,0.15); color:#10b981; border:1px solid rgba(16,185,129,0.3); }
    .badge-err { background:rgba(239,68,68,0.15); color:#ef4444; border:1px solid rgba(239,68,68,0.3); }
</style>
</head><body>
<h1>🛡️ Aksanti — Migração Rate Limiting</h1>
<div class="box">
<?php endif;

// ─── INÍCIO DA MIGRAÇÃO ───────────────────────────────────────────────────────

out("Iniciando migração das tabelas de Rate Limiting...", 'info');
out("Timestamp: " . date('Y-m-d H:i:s'), 'info');

if (!$is_cli) echo "<hr>";

// Carregar configuração da BD
$config_path = __DIR__ . '/../configuracoes/base_dados.php';
if (!file_exists($config_path)) {
    out("ERRO: Ficheiro de configuração não encontrado: {$config_path}", 'error');
    if (!$is_cli) echo "</div></body></html>";
    exit(1);
}
require_once $config_path;

// Conectar à base de dados
out("A ligar à base de dados PostgreSQL...", 'info');
try {
    $database = new Database();
    $db = $database->getConnection();
    out("✓ Ligação estabelecida com sucesso.", 'ok');
} catch (Exception $e) {
    out("✗ Falha na ligação: " . $e->getMessage(), 'error');
    if (!$is_cli) echo "</div></body></html>";
    exit(1);
}

if (!$is_cli) echo "<hr>";

// ─── DEFINIÇÃO DOS SQL A EXECUTAR ─────────────────────────────────────────────

$migrations = [

    // 1. Tabela de tentativas individuais
    'Criar tabela rate_limit_attempts' => "
        CREATE TABLE IF NOT EXISTS rate_limit_attempts (
            id          BIGSERIAL PRIMARY KEY,
            action_key  VARCHAR(255) NOT NULL,
            ip_address  INET,
            created_at  TIMESTAMPTZ DEFAULT NOW()
        )
    ",

    // 2. Índice composto para performance
    'Criar índice idx_rla_key_time' => "
        CREATE INDEX IF NOT EXISTS idx_rla_key_time 
            ON rate_limit_attempts(action_key, created_at DESC)
    ",

    // 3. Índice de limpeza
    'Criar índice idx_rla_created' => "
        CREATE INDEX IF NOT EXISTS idx_rla_created 
            ON rate_limit_attempts(created_at)
    ",

    // 4. Tabela de bloqueios ativos
    'Criar tabela rate_limit_blocks' => "
        CREATE TABLE IF NOT EXISTS rate_limit_blocks (
            id              BIGSERIAL PRIMARY KEY,
            action_key      VARCHAR(255) NOT NULL UNIQUE,
            block_type      VARCHAR(10) NOT NULL DEFAULT 'soft',
            attempt_count   INT DEFAULT 0,
            blocked_at      TIMESTAMPTZ DEFAULT NOW(),
            unblock_at      TIMESTAMPTZ,
            reason          TEXT,
            unblocked_by    INT,
            unblocked_at    TIMESTAMPTZ,
            admin_note      TEXT
        )
    ",

    // 5. Índice por action_key
    'Criar índice idx_rlb_action_key' => "
        CREATE INDEX IF NOT EXISTS idx_rlb_action_key 
            ON rate_limit_blocks(action_key)
    ",

    // 6. Índice para listagem de bloqueios ativos
    'Criar índice idx_rlb_block_type' => "
        CREATE INDEX IF NOT EXISTS idx_rlb_block_type 
            ON rate_limit_blocks(block_type, unblock_at)
    ",
];

// ─── EXECUÇÃO ─────────────────────────────────────────────────────────────────

$success_count = 0;
$error_count   = 0;

foreach ($migrations as $label => $sql) {
    out("» {$label}...", 'info');
    try {
        $db->exec(trim($sql));
        out("  ✓ Concluído.", 'ok');
        $success_count++;
    } catch (PDOException $e) {
        $msg = $e->getMessage();
        // "already exists" não é erro real
        if (strpos($msg, 'already exists') !== false) {
            out("  ⚠ Já existe (ignorado).", 'warn');
            $success_count++;
        } else {
            out("  ✗ ERRO: " . $msg, 'error');
            $error_count++;
        }
    }
}

if (!$is_cli) echo "<hr>";

// ─── VERIFICAÇÃO PÓS-MIGRAÇÃO ─────────────────────────────────────────────────

out("A verificar tabelas criadas...", 'info');
try {
    $tables_check = $db->query("
        SELECT table_name 
        FROM information_schema.tables 
        WHERE table_schema = 'public' 
          AND table_name IN ('rate_limit_attempts', 'rate_limit_blocks')
        ORDER BY table_name
    ")->fetchAll(PDO::FETCH_COLUMN);

    foreach (['rate_limit_attempts', 'rate_limit_blocks'] as $expected) {
        if (in_array($expected, $tables_check)) {
            out("  ✓ Tabela <strong>{$expected}</strong> confirmada.", 'ok');
        } else {
            out("  ✗ Tabela <strong>{$expected}</strong> NÃO encontrada!", 'error');
            $error_count++;
        }
    }
} catch (PDOException $e) {
    out("Erro na verificação: " . $e->getMessage(), 'error');
}

if (!$is_cli) echo "<hr>";

// ─── RESULTADO FINAL ──────────────────────────────────────────────────────────

out("", 'info');
out("═══════════════════════════════════════", 'info');
if ($error_count === 0) {
    out("✅ MIGRAÇÃO CONCLUÍDA COM SUCESSO!", 'ok');
    out("   Tabelas criadas: 2 | Índices criados: 4", 'ok');
    out("   O sistema de Rate Limiting está operacional.", 'ok');
} else {
    out("⚠️  MIGRAÇÃO COM {$error_count} ERRO(S)", 'warn');
    out("   Verifica os erros acima e tenta novamente.", 'warn');
}
out("═══════════════════════════════════════", 'info');

if (!$is_cli) {
    $badge = $error_count === 0
        ? '<span class="badge badge-ok">✓ Sucesso</span>'
        : '<span class="badge badge-err">✗ ' . $error_count . ' Erro(s)</span>';
    echo "<p style='margin-top:1rem;font-family:sans-serif;'>Estado final: {$badge}</p>";
    echo "</div></body></html>";
}

exit($error_count > 0 ? 1 : 0);
?>
