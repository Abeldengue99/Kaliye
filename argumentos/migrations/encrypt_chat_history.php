<?php
/**
 * Encrypts existing plaintext chat messages at rest.
 *
 * Usage:
 *   php argumentos/migrations/encrypt_chat_history.php
 *   or, locally:
 *   http://localhost/aksanti/argumentos/migrations/encrypt_chat_history.php
 */
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $allowedIps = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];
    if (!in_array($_SERVER['REMOTE_ADDR'] ?? '', $allowedIps, true)) {
        http_response_code(403);
        exit('Local migration only.');
    }
    header('Content-Type: text/plain; charset=utf-8');
}

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

$db = (new Database())->getConnection();
ChatSecurity::ensureSafetyTables($db);

function table_exists(PDO $db, string $table): bool
{
    try {
        if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
            $stmt = $db->prepare('SELECT to_regclass(?) IS NOT NULL');
            $stmt->execute([$table]);
            return (bool)$stmt->fetchColumn();
        }

        $stmt = $db->prepare('SELECT 1 FROM information_schema.tables WHERE table_name = ? LIMIT 1');
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    } catch (Throwable $e) {
        return false;
    }
}

function encrypt_table(PDO $db, string $table, string $idColumn, string $contentColumn, ?string $typeColumn = null): int
{
    if (!table_exists($db, $table)) {
        return 0;
    }

    $where = "{$contentColumn} IS NOT NULL AND {$contentColumn} <> ''";
    if ($typeColumn !== null) {
        $where .= " AND COALESCE({$typeColumn}, 'text') = 'text'";
    }

    $stmt = $db->query("SELECT {$idColumn} AS id, {$contentColumn} AS body FROM {$table} WHERE {$where}");
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    $updated = 0;

    $update = $db->prepare("UPDATE {$table} SET {$contentColumn} = ? WHERE {$idColumn} = ?");
    foreach ($rows as $row) {
        $body = (string)($row['body'] ?? '');
        if ($body === '' || ChatSecurity::isProtectedContent($body)) {
            continue;
        }

        $protected = ChatSecurity::protectContent($body);
        if ($protected === $body) {
            continue;
        }

        $update->execute([$protected, $row['id']]);
        $updated++;
    }

    return $updated;
}

$db->beginTransaction();
try {
    $counts = [
        'messages' => encrypt_table($db, 'messages', 'message_id', 'content'),
        'group_messages' => encrypt_table($db, 'group_messages', 'message_id', 'content'),
        'mentor_group_messages' => encrypt_table($db, 'mentor_group_messages', 'id', 'message', 'message_type'),
    ];

    $db->commit();
    foreach ($counts as $table => $count) {
        echo "{$table}: {$count} mensagens cifradas." . PHP_EOL;
    }
    echo "Histórico de chat cifrado com AES-256-GCM." . PHP_EOL;
} catch (Throwable $e) {
    $db->rollBack();
    $message = 'Falha na migracao: ' . $e->getMessage() . PHP_EOL;
    if ($isCli && defined('STDERR')) {
        fwrite(STDERR, $message);
    } else {
        echo $message;
    }
    exit(1);
}
