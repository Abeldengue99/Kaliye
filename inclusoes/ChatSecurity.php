<?php
/**
 * Shared security policy for direct and group chat.
 */

require_once __DIR__ . '/Security.php';
require_once __DIR__ . '/auth_check.php';

class ChatSecurity {
    public const MAX_TEXT_LENGTH = 4000;
    public const DIRECT_RATE_LIMIT = 45;
    public const GROUP_RATE_LIMIT = 60;
    private const ENCRYPTION_PREFIX = 'kchat:v1:';

    public static function touchPresence(PDO $db, int $userId): void {
        try {
            self::ensurePresenceColumns($db);
            self::ensureSafetyTables($db);
            $db->prepare("UPDATE users SET last_activity = NOW() WHERE user_id = ?")->execute([$userId]);
        } catch (Throwable $e) {}
    }

    public static function ensurePresenceColumns(PDO $db): void {
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS last_activity TIMESTAMP NULL");
            } else {
                $db->exec("ALTER TABLE users ADD COLUMN last_activity DATETIME NULL");
            }
        } catch (Throwable $e) {}
    }

    public static function ensureTypingTable(PDO $db): void {
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $db->exec("CREATE TABLE IF NOT EXISTS chat_typing_status (
                    user_id INTEGER NOT NULL,
                    receiver_id INTEGER NOT NULL,
                    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, receiver_id)
                )");
            } else {
                $db->exec("CREATE TABLE IF NOT EXISTS chat_typing_status (
                    user_id INT NOT NULL,
                    receiver_id INT NOT NULL,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    PRIMARY KEY (user_id, receiver_id)
                )");
            }
        } catch (Throwable $e) {}
    }

    public static function normalizeText(string $content): string {
        $content = trim(str_replace("\0", '', $content));
        return mb_substr($content, 0, self::MAX_TEXT_LENGTH);
    }

    public static function ensureSafetyTables(PDO $db): void {
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $db->exec("CREATE TABLE IF NOT EXISTS chat_blocks (
                    block_id SERIAL PRIMARY KEY,
                    blocker_id INTEGER NOT NULL,
                    blocked_id INTEGER NOT NULL,
                    reason VARCHAR(80),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE(blocker_id, blocked_id)
                )");
                $db->exec("CREATE TABLE IF NOT EXISTS chat_reports (
                    report_id SERIAL PRIMARY KEY,
                    reporter_id INTEGER NOT NULL,
                    reported_user_id INTEGER NOT NULL,
                    message_id INTEGER NULL,
                    chat_scope VARCHAR(30) NOT NULL DEFAULT 'direct',
                    category VARCHAR(40) NOT NULL,
                    details TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $db->exec("CREATE TABLE IF NOT EXISTS chat_security_logs (
                    log_id SERIAL PRIMARY KEY,
                    user_id INTEGER NULL,
                    target_user_id INTEGER NULL,
                    event_type VARCHAR(60) NOT NULL,
                    severity VARCHAR(20) DEFAULT 'info',
                    metadata TEXT,
                    ip_address VARCHAR(80),
                    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
                )");
                $db->exec("ALTER TABLE messages ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");
                $db->exec("ALTER TABLE group_messages ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");
                $db->exec("ALTER TABLE mentor_group_messages ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");
            } else {
                $db->exec("CREATE TABLE IF NOT EXISTS chat_blocks (
                    block_id INT AUTO_INCREMENT PRIMARY KEY,
                    blocker_id INT NOT NULL,
                    blocked_id INT NOT NULL,
                    reason VARCHAR(80),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    UNIQUE KEY uniq_chat_block (blocker_id, blocked_id)
                )");
                $db->exec("CREATE TABLE IF NOT EXISTS chat_reports (
                    report_id INT AUTO_INCREMENT PRIMARY KEY,
                    reporter_id INT NOT NULL,
                    reported_user_id INT NOT NULL,
                    message_id INT NULL,
                    chat_scope VARCHAR(30) NOT NULL DEFAULT 'direct',
                    category VARCHAR(40) NOT NULL,
                    details TEXT,
                    status VARCHAR(20) DEFAULT 'pending',
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                $db->exec("CREATE TABLE IF NOT EXISTS chat_security_logs (
                    log_id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL,
                    target_user_id INT NULL,
                    event_type VARCHAR(60) NOT NULL,
                    severity VARCHAR(20) DEFAULT 'info',
                    metadata TEXT,
                    ip_address VARCHAR(80),
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP
                )");
                try { $db->exec("ALTER TABLE messages ADD COLUMN expires_at DATETIME NULL"); } catch (Throwable $e) {}
                try { $db->exec("ALTER TABLE group_messages ADD COLUMN expires_at DATETIME NULL"); } catch (Throwable $e) {}
                try { $db->exec("ALTER TABLE mentor_group_messages ADD COLUMN expires_at DATETIME NULL"); } catch (Throwable $e) {}
            }
        } catch (Throwable $e) {}
    }

    private static function encryptionKey(): string {
        $configured = getenv('KALIYE_CHAT_KEY') ?: '';
        $material = $configured !== '' ? $configured : __DIR__ . '|kaliye-chat-at-rest-v1';
        return hash('sha256', $material, true);
    }

    public static function protectContent(string $content): string {
        if ($content === '' || strpos($content, self::ENCRYPTION_PREFIX) === 0 || !function_exists('openssl_encrypt')) {
            return $content;
        }

        $iv = random_bytes(12);
        $tag = '';
        $cipher = openssl_encrypt($content, 'aes-256-gcm', self::encryptionKey(), OPENSSL_RAW_DATA, $iv, $tag);
        if ($cipher === false) {
            return $content;
        }

        return self::ENCRYPTION_PREFIX . base64_encode(json_encode([
            'iv' => base64_encode($iv),
            'tag' => base64_encode($tag),
            'ct' => base64_encode($cipher),
        ]));
    }

    public static function isProtectedContent(?string $content): bool {
        return strpos((string)$content, self::ENCRYPTION_PREFIX) === 0;
    }

    public static function revealContent(?string $content): string {
        $content = (string)$content;
        if ($content === '' || strpos($content, self::ENCRYPTION_PREFIX) !== 0 || !function_exists('openssl_decrypt')) {
            return $content;
        }

        $payload = json_decode(base64_decode(substr($content, strlen(self::ENCRYPTION_PREFIX))) ?: '', true);
        if (!is_array($payload)) {
            return '[mensagem protegida indisponivel]';
        }

        $plain = openssl_decrypt(
            base64_decode($payload['ct'] ?? '') ?: '',
            'aes-256-gcm',
            self::encryptionKey(),
            OPENSSL_RAW_DATA,
            base64_decode($payload['iv'] ?? '') ?: '',
            base64_decode($payload['tag'] ?? '') ?: ''
        );

        return $plain === false ? '[mensagem protegida indisponivel]' : $plain;
    }

    public static function hasBlockBetween(PDO $db, int $a, int $b): bool {
        self::ensureSafetyTables($db);
        try {
            $stmt = $db->prepare("SELECT 1 FROM chat_blocks WHERE (blocker_id = ? AND blocked_id = ?) OR (blocker_id = ? AND blocked_id = ?) LIMIT 1");
            $stmt->execute([$a, $b, $b, $a]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function blockUser(PDO $db, int $blockerId, int $blockedId, string $reason = 'manual'): bool {
        if ($blockerId <= 0 || $blockedId <= 0 || $blockerId === $blockedId) {
            return false;
        }

        self::ensureSafetyTables($db);
        try {
            if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                $stmt = $db->prepare("INSERT INTO chat_blocks (blocker_id, blocked_id, reason) VALUES (?, ?, ?) ON CONFLICT (blocker_id, blocked_id) DO UPDATE SET reason = EXCLUDED.reason");
            } else {
                $stmt = $db->prepare("INSERT INTO chat_blocks (blocker_id, blocked_id, reason) VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE reason = VALUES(reason)");
            }
            $stmt->execute([$blockerId, $blockedId, mb_substr($reason, 0, 80)]);
            self::logChatEvent($db, $blockerId, $blockedId, 'user_blocked', 'warning', ['reason' => $reason]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function reportUser(PDO $db, int $reporterId, int $reportedId, ?int $messageId, string $scope, string $category, string $details = ''): bool {
        if ($reporterId <= 0 || $reportedId <= 0 || $reporterId === $reportedId) {
            return false;
        }

        self::ensureSafetyTables($db);
        $allowed = ['spam', 'fraud', 'phishing', 'harassment', 'abuse', 'other'];
        $category = in_array($category, $allowed, true) ? $category : 'other';
        try {
            $stmt = $db->prepare("INSERT INTO chat_reports (reporter_id, reported_user_id, message_id, chat_scope, category, details) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([$reporterId, $reportedId, $messageId, mb_substr($scope, 0, 30), $category, mb_substr($details, 0, 1000)]);
            self::logChatEvent($db, $reporterId, $reportedId, 'user_reported', 'warning', [
                'category' => $category,
                'message_id' => $messageId,
                'scope' => $scope,
            ]);
            return true;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function logChatEvent(PDO $db, ?int $userId, ?int $targetUserId, string $eventType, string $severity = 'info', array $metadata = []): void {
        self::ensureSafetyTables($db);
        try {
            $stmt = $db->prepare("INSERT INTO chat_security_logs (user_id, target_user_id, event_type, severity, metadata, ip_address) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt->execute([
                $userId,
                $targetUserId,
                mb_substr($eventType, 0, 60),
                mb_substr($severity, 0, 20),
                json_encode($metadata, JSON_UNESCAPED_UNICODE),
                $_SERVER['REMOTE_ADDR'] ?? '',
            ]);
        } catch (Throwable $e) {}
    }

    public static function analyzeOutgoingMessage(PDO $db, int $senderId, string $scope, int $targetId, string $content): array {
        self::ensureSafetyTables($db);
        $plain = mb_strtolower(self::revealContent($content));
        $signals = [];
        $severity = 'info';

        if ($content !== '' && self::isRepeatedMessage($db, $senderId, $plain, $scope)) {
            $signals[] = 'repeated_message';
            $severity = 'warning';
        }

        $urls = self::extractUrls($content);
        foreach ($urls as $url) {
            $host = parse_url($url, PHP_URL_HOST) ?: '';
            $host = mb_strtolower($host);
            if (preg_match('/(^|\.)(bit\.ly|tinyurl\.com|t\.co|is\.gd|cutt\.ly|shorturl\.at)$/i', $host)) {
                $signals[] = 'shortened_url';
                $severity = 'warning';
            }
            if (preg_match('/\.(zip|mov|scr|exe|bat|cmd|msi|apk|jar|ps1)(\?|$)/i', $url)) {
                $signals[] = 'dangerous_download_link';
                $severity = 'critical';
            }
        }

        $criticalPatterns = [
            '/(partilha|manda|envia).{0,24}(senha|password|codigo|c[oó]digo|otp|pin)/iu',
            '/(envia|transfere|manda).{0,24}(dinheiro|kwanza|kz|akz)/iu',
            '/investe.{0,24}(agora|rapidamente|urgente)/iu',
            '/(lucro|retorno).{0,24}(garantido|100%|cem por cento)/iu',
        ];
        foreach ($criticalPatterns as $pattern) {
            if (preg_match($pattern, $plain)) {
                $signals[] = 'social_engineering';
                $severity = 'critical';
                break;
            }
        }

        if (!$signals) {
            return ['allowed' => true, 'signals' => []];
        }

        self::logChatEvent($db, $senderId, $targetId, 'message_safety_signal', $severity, [
            'scope' => $scope,
            'signals' => $signals,
            'urls' => $urls,
        ]);

        if (in_array('dangerous_download_link', $signals, true) || in_array('social_engineering', $signals, true)) {
            return [
                'allowed' => false,
                'reason' => 'Mensagem bloqueada por segurança: detectamos possível golpe, phishing ou ficheiro perigoso.',
                'signals' => $signals,
            ];
        }

        if (in_array('repeated_message', $signals, true)) {
            return [
                'allowed' => false,
                'reason' => 'Mensagem bloqueada por anti-spam. Evite repetir a mesma mensagem em pouco tempo.',
                'signals' => $signals,
            ];
        }

        return ['allowed' => true, 'signals' => $signals];
    }

    private static function extractUrls(string $content): array {
        preg_match_all('~https?://[^\s<>"\']+~i', $content, $matches);
        return array_values(array_unique($matches[0] ?? []));
    }

    private static function isRepeatedMessage(PDO $db, int $senderId, string $plain, string $scope): bool {
        if (mb_strlen($plain) < 8) {
            return false;
        }

        try {
            if ($scope === 'mentor_group') {
                $sql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
                    ? "SELECT message FROM mentor_group_messages WHERE sender_id = ? AND created_at >= NOW() - INTERVAL '10 minutes' ORDER BY created_at DESC LIMIT 8"
                    : "SELECT message FROM mentor_group_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY created_at DESC LIMIT 8";
            } elseif ($scope === 'group') {
                $sql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
                    ? "SELECT content FROM group_messages WHERE sender_id = ? AND sent_at >= NOW() - INTERVAL '10 minutes' ORDER BY sent_at DESC LIMIT 8"
                    : "SELECT content FROM group_messages WHERE sender_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY sent_at DESC LIMIT 8";
            } else {
                $sql = $db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql'
                    ? "SELECT content FROM messages WHERE sender_id = ? AND sent_at >= NOW() - INTERVAL '10 minutes' ORDER BY sent_at DESC LIMIT 8"
                    : "SELECT content FROM messages WHERE sender_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 10 MINUTE) ORDER BY sent_at DESC LIMIT 8";
            }
            $stmt = $db->prepare($sql);
            $stmt->execute([$senderId]);
            $matches = 0;
            foreach ($stmt->fetchAll(PDO::FETCH_COLUMN) as $previous) {
                if (mb_strtolower(self::revealContent((string)$previous)) === $plain) {
                    $matches++;
                }
            }
            return $matches >= 2;
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function getUser(PDO $db, int $userId): ?array {
        $stmt = $db->prepare("SELECT user_id, full_name, user_type, profile_pic, is_verified, verification_status, mentorship_status, last_activity FROM users WHERE user_id = ?");
        $stmt->execute([$userId]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);
        return $user ?: null;
    }

    public static function canDirectMessage(PDO $db, int $senderId, int $receiverId): array {
        if ($senderId <= 0 || $receiverId <= 0 || $senderId === $receiverId) {
            return ['allowed' => false, 'reason' => 'Destinatario invalido.'];
        }

        self::ensurePresenceColumns($db);
        $sender = self::getUser($db, $senderId);
        $receiver = self::getUser($db, $receiverId);
        if (!$sender || !$receiver) {
            return ['allowed' => false, 'reason' => 'Utilizador não encontrado.'];
        }

        if (self::hasBlockBetween($db, $senderId, $receiverId)) {
            return ['allowed' => false, 'reason' => 'Esta conversa esta bloqueada por segurança ou preferencia de utilizador.'];
        }

        $senderType = strtolower((string)$sender['user_type']);
        $receiverType = strtolower((string)$receiver['user_type']);

        if (in_array($senderType, ['admin', 'superadmin'], true)) {
            return ['allowed' => true, 'mode' => 'admin'];
        }

        if (($sender['verification_status'] ?? 'unsubmitted') !== 'verified') {
            return ['allowed' => false, 'reason' => 'Valide a sua identidade antes de enviar mensagens.'];
        }

        $hasHistory = self::hasDirectHistory($db, $senderId, $receiverId);
        if ($hasHistory) {
            return ['allowed' => true, 'mode' => 'reply'];
        }

        if (self::hasAcceptedConnection($db, $senderId, $receiverId)) {
            return ['allowed' => true, 'mode' => 'connection'];
        }

        if (self::hasActiveMentorship($db, $senderId, $receiverId)) {
            return ['allowed' => true, 'mode' => 'mentorship'];
        }

        $senderIsStudent = strpos($senderType, 'student') !== false || in_array($senderType, ['student', 'entrepreneur'], true);
        if ($senderIsStudent && $receiverType === 'investor') {
            return ['allowed' => false, 'reason' => 'Apenas investidores, mentores ou contactos existentes podem iniciar conversa com investidores.'];
        }

        if (in_array($senderType, ['mentor', 'investor'], true)) {
            return ['allowed' => true, 'mode' => 'professional'];
        }

        return ['allowed' => false, 'reason' => 'Para iniciar esta conversa, crie uma conexao ou tenha uma mentoria ativa.'];
    }

    public static function hasDirectHistory(PDO $db, int $a, int $b): bool {
        try {
            $stmt = $db->prepare("SELECT 1 FROM messages WHERE ((sender_id = ? AND receiver_id = ?) OR (sender_id = ? AND receiver_id = ?)) LIMIT 1");
            $stmt->execute([$a, $b, $b, $a]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function hasAcceptedConnection(PDO $db, int $a, int $b): bool {
        try {
            $stmt = $db->prepare("SELECT 1 FROM user_connections WHERE ((user_id_1 = ? AND user_id_2 = ?) OR (user_id_1 = ? AND user_id_2 = ?)) AND status = 'accepted' LIMIT 1");
            $stmt->execute([$a, $b, $b, $a]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function hasActiveMentorship(PDO $db, int $a, int $b): bool {
        try {
            $stmt = $db->prepare("
                SELECT 1 FROM mentorship_contracts
                WHERE ((mentor_id = ? AND student_id = ?) OR (mentor_id = ? AND student_id = ?)) AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$a, $b, $b, $a]);
            if ($stmt->fetchColumn()) return true;
        } catch (Throwable $e) {}

        try {
            $stmt = $db->prepare("
                SELECT 1 FROM mentorships
                WHERE ((mentor_id = ? AND mentee_id = ?) OR (mentor_id = ? AND mentee_id = ?)) AND status = 'active'
                LIMIT 1
            ");
            $stmt->execute([$a, $b, $b, $a]);
            return (bool)$stmt->fetchColumn();
        } catch (Throwable $e) {
            return false;
        }
    }

    public static function checkRateLimit(PDO $db, int $senderId, string $scope = 'direct'): array {
        $limit = $scope === 'group' ? self::GROUP_RATE_LIMIT : self::DIRECT_RATE_LIMIT;
        $user = self::getUser($db, $senderId);
        $isVerified = $user && (
            in_array($user['is_verified'] ?? false, [true, 1, '1', 't'], true) ||
            in_array($user['verification_status'] ?? '', ['verified', 'approved'], true)
        );
        if (!$isVerified) {
            $limit = $scope === 'group' ? 25 : 15;
        }
        try {
            if ($scope === 'group') {
                if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                    $sql = "
                        SELECT
                            (SELECT COUNT(*) FROM group_messages WHERE sender_id = ? AND sent_at >= NOW() - INTERVAL '1 minute') +
                            (SELECT COUNT(*) FROM mentor_group_messages WHERE sender_id = ? AND created_at >= NOW() - INTERVAL '1 minute')
                    ";
                } else {
                    $sql = "
                        SELECT
                            (SELECT COUNT(*) FROM group_messages WHERE sender_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)) +
                            (SELECT COUNT(*) FROM mentor_group_messages WHERE sender_id = ? AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE))
                    ";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute([$senderId, $senderId]);
                $count = (int)$stmt->fetchColumn();
            } else {
                if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) === 'pgsql') {
                    $sql = "SELECT COUNT(*) FROM messages WHERE sender_id = ? AND sent_at >= NOW() - INTERVAL '1 minute'";
                } else {
                    $sql = "SELECT COUNT(*) FROM messages WHERE sender_id = ? AND sent_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE)";
                }
                $stmt = $db->prepare($sql);
                $stmt->execute([$senderId]);
                $count = (int)$stmt->fetchColumn();
            }

            if ($count >= $limit) {
                self::logChatEvent($db, $senderId, null, 'rate_limit_hit', 'warning', ['scope' => $scope, 'limit' => $limit]);
                return ['allowed' => false, 'reason' => 'Muitas mensagens em pouco tempo. Aguarde alguns segundos.'];
            }
        } catch (Throwable $e) {}

        return ['allowed' => true];
    }

    public static function storeChatMedia(array $file, int $senderId): array {
        $mediaMimes = [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'application/pdf' => 'pdf',
            'video/mp4' => 'mp4',
            'video/quicktime' => 'mov',
            'audio/webm' => 'webm',
        ];

        $stored = Security::storeUploadedFile(
            $file,
            __DIR__ . '/../carregamentos/chat/' . $senderId . '/' . date('Y-m'),
            'carregamentos/chat/' . $senderId . '/' . date('Y-m'),
            $mediaMimes,
            25 * 1024 * 1024,
            'msg'
        );

        if (!$stored['ok']) {
            return ['ok' => false, 'error' => $stored['error']];
        }

        $type = 'document';
        if (strpos($stored['mime'], 'image/') === 0) {
            $type = 'image';
        } elseif (strpos($stored['mime'], 'video/') === 0 || strpos($stored['mime'], 'audio/') === 0) {
            $type = 'video';
        }

        return ['ok' => true, 'path' => $stored['path'], 'type' => $type, 'mime' => $stored['mime']];
    }

    public static function normalizeAvatar(array $user): string {
        return getUserAvatarUrl($user['user_type'] ?? 'student', $user['mentorship_status'] ?? 'unsubmitted', $user['profile_pic'] ?? '');
    }

    public static function onlineMeta(?string $lastActivity): array {
        if (!$lastActivity) {
            return ['is_online' => false, 'label' => 'Offline'];
        }

        $ts = strtotime($lastActivity);
        if (!$ts) {
            return ['is_online' => false, 'label' => 'Offline'];
        }

        $diff = time() - $ts;
        if ($diff <= 120) {
            return ['is_online' => true, 'label' => 'Online agora'];
        }
        if ($diff < 3600) {
            return ['is_online' => false, 'label' => 'Visto ha ' . max(1, floor($diff / 60)) . ' min'];
        }

        return ['is_online' => false, 'label' => 'Visto ha mais de 1h'];
    }
}
?>
