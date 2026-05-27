<?php
/**
 * inclusoes/RateLimiter.php
 * 
 * Motor de Rate Limiting com Penalidades Progressivas para a plataforma KALIYE.
 * 
 * ESTRATÉGIA DE 3 FASES:
 * ┌─────────────────────────────────────────────────────────────────────┐
 * │  FASE 1 — Aviso         : 3 tentativas → mensagem de aviso         │
 * │  FASE 2 — Soft Lock     : 5 tentativas → bloqueio temporário (15m) │
 * │  FASE 3 — Hard Lock     : 10 tentativas → bloqueio permanente      │
 * │                           (requer contacto com a equipa)           │
 * └─────────────────────────────────────────────────────────────────────┘
 * 
 * WHITELIST: 127.0.0.1 e ::1 são sempre permitidos (desenvolvimento local).
 */
class RateLimiter {

    /**
     * IPs sempre permitidos (ambiente de desenvolvimento).
     */
    private static array $whitelist = ['127.0.0.1', '::1', '::ffff:127.0.0.1'];

    /**
     * Configurações de limites por tipo de ação.
     * Cada entrada define as 3 fases progressivas.
     * 
     * 'warning'   : [tentativas, janela_segundos]
     * 'soft_lock' : [tentativas, janela_segundos, duração_bloqueio_segundos]
     * 'hard_lock' : [tentativas, janela_segundos]
     */
    private static array $config = [
        'login' => [
            'warning'   => [3, 300],          // 3 tentativas em 5 min → aviso
            'soft_lock' => [5, 900, 900],      // 5 tentativas em 15 min → bloqueia 15 min
            'hard_lock' => [10, 3600],         // 10 tentativas em 1 hora → bloqueio permanente
        ],
        'register' => [
            'warning'   => [2, 300],
            'soft_lock' => [3, 3600, 3600],    // 3 registos / hora → bloqueia 1 hora
            'hard_lock' => [8, 86400],         // 8 registos / 24h → bloqueio permanente
        ],
        'forgot_password' => [
            'warning'   => [2, 600],
            'soft_lock' => [3, 1800, 1800],    // 3 pedidos / 30 min → bloqueia 30 min
            'hard_lock' => [8, 86400],
        ],
        'resend_otp' => [
            'warning'   => [3, 600],
            'soft_lock' => [5, 1800, 1800],    // 5 reenvios / 30 min → bloqueia 30 min
            'hard_lock' => [10, 3600],
        ],
        'withdrawal' => [
            'warning'   => [2, 1800],
            'soft_lock' => [3, 3600, 7200],    // 3 saques / hora → bloqueia 2 horas
            'hard_lock' => [5, 86400],         // 5 saques / 24h → bloqueio permanente
        ],
        'payment' => [
            'warning'   => [5, 1800],
            'soft_lock' => [10, 3600, 3600],
            'hard_lock' => [20, 86400],
        ],
        'api_general' => [
            'warning'   => [80, 60],
            'soft_lock' => [120, 60, 300],     // 120 req / min → bloqueia 5 min
            'hard_lock' => [500, 3600],
        ],
    ];

    // ─────────────────────────────────────────────────────────────
    // MÉTODO PRINCIPAL — Verificar se a ação é permitida
    // ─────────────────────────────────────────────────────────────

    /**
     * Verifica se uma ação é permitida para o identificador dado.
     * Regista a tentativa e aplica penalidades progressivas.
     * 
     * @param PDO    $db         Conexão ativa à base de dados
     * @param string $action     Tipo de ação (ex: 'login', 'withdrawal')
     * @param string $identifier Identificador único (IP, user_id, email)
     * @param string $ip         Endereço IP real do pedido
     * @return array {
     *   'allowed'    => bool,       // true = deixar passar
     *   'phase'      => string,     // 'ok' | 'warning' | 'soft_lock' | 'hard_lock'
     *   'message'    => string,     // mensagem para o utilizador
     *   'retry_after'=> int,        // segundos até poder tentar novamente
     *   'remaining'  => int,        // tentativas restantes antes de bloqueio
     * }
     */
    public static function check(PDO $db, string $action, string $identifier, string $ip = ''): array {
        // 1. Whitelist — IPs locais nunca são bloqueados
        if (self::isWhitelisted($ip)) {
            return self::result(true, 'ok', '', 0, 999);
        }

        $action_key = self::buildKey($action, $identifier);
        $cfg = self::$config[$action] ?? self::$config['api_general'];

        // 2. Verificar se já existe um bloqueio ativo
        $block = self::getActiveBlock($db, $action_key);
        if ($block) {
            if ($block['block_type'] === 'hard') {
                return self::result(false, 'hard_lock',
                    'A tua conta foi temporariamente bloqueada por segurança. Por favor, contacta a equipa KALIYE para desbloquear.',
                    -1, 0
                );
            } else {
                $retry = max(0, strtotime($block['unblock_at']) - time());
                return self::result(false, 'soft_lock',
                    'Demasiadas tentativas. Aguarda ' . self::formatDuration($retry) . ' para tentar novamente.',
                    $retry, 0
                );
            }
        }

        // 3. Contar tentativas recentes e verificar fases
        [$hard_max, $hard_window]                  = $cfg['hard_lock'];
        [$soft_max, $soft_window, $soft_duration]  = $cfg['soft_lock'];
        [$warn_max, $warn_window]                  = $cfg['warning'];

        // Verificar Hard Lock (janela mais larga)
        $count_hard = self::countAttempts($db, $action_key, $hard_window);
        if ($count_hard >= $hard_max) {
            self::createBlock($db, $action_key, 'hard', $count_hard,
                "Atingiu o limite máximo de {$hard_max} tentativas em " . self::formatDuration($hard_window) . "."
            );
            self::logAttempt($db, $action_key, $ip);
            return self::result(false, 'hard_lock',
                'A tua conta foi bloqueada por excesso de tentativas suspeitas. Por favor, contacta a equipa KALIYE para desbloquear.',
                -1, 0
            );
        }

        // Verificar Soft Lock (janela intermédia)
        $count_soft = self::countAttempts($db, $action_key, $soft_window);
        if ($count_soft >= $soft_max) {
            self::createBlock($db, $action_key, 'soft', $count_soft,
                "Atingiu {$soft_max} tentativas em " . self::formatDuration($soft_window) . ".",
                $soft_duration
            );
            self::logAttempt($db, $action_key, $ip);
            return self::result(false, 'soft_lock',
                'Demasiadas tentativas. Por segurança, aguarda ' . self::formatDuration($soft_duration) . ' antes de tentar novamente.',
                $soft_duration, 0
            );
        }

        // 4. Registar esta tentativa
        self::logAttempt($db, $action_key, $ip);

        // 5. Calcular tentativas restantes e gerar aviso se necessário
        $count_soft_new = $count_soft + 1;
        $remaining = max(0, $soft_max - $count_soft_new);

        if ($count_soft_new >= $warn_max) {
            $msg = "Atenção: apenas {$remaining} tentativa(s) restante(s) antes de ser bloqueado temporariamente.";
            return self::result(true, 'warning', $msg, 0, $remaining);
        }

        // 6. Limpeza periódica (1 em cada 50 chamadas)
        if (mt_rand(1, 50) === 1) {
            self::cleanup($db);
        }

        return self::result(true, 'ok', '', 0, $remaining);
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTODOS DE ADMINISTRAÇÃO — Para o painel admin
    // ─────────────────────────────────────────────────────────────

    /**
     * Desbloqueia manualmente um identificador (chamado pelo admin).
     */
    public static function unblock(PDO $db, string $action, string $identifier, int $admin_id, string $note = ''): bool {
        $action_key = self::buildKey($action, $identifier);
        try {
            $stmt = $db->prepare("
                UPDATE rate_limit_blocks 
                SET unblock_at = NOW(), unblocked_by = ?, unblocked_at = NOW(), admin_note = ?
                WHERE action_key = ? AND (unblock_at IS NULL OR unblock_at > NOW())
            ");
            $stmt->execute([$admin_id, $note, $action_key]);
            // Limpar tentativas também
            $db->prepare("DELETE FROM rate_limit_attempts WHERE action_key = ?")->execute([$action_key]);
            return $stmt->rowCount() > 0;
        } catch (PDOException $e) {
            return false;
        }
    }

    /**
     * Lista todos os bloqueios ativos (para o painel admin).
     */
    public static function getActiveBlocks(PDO $db): array {
        try {
            $stmt = $db->query("
                SELECT action_key, block_type, attempt_count, blocked_at, unblock_at, reason
                FROM rate_limit_blocks
                WHERE unblocked_at IS NULL 
                  AND (unblock_at IS NULL OR unblock_at > NOW())
                ORDER BY blocked_at DESC
                LIMIT 100
            ");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            return [];
        }
    }

    /**
     * Retorna o estado atual de rate limit para um identificador (para debug/admin).
     */
    public static function getStatus(PDO $db, string $action, string $identifier): array {
        $action_key = self::buildKey($action, $identifier);
        $cfg = self::$config[$action] ?? self::$config['api_general'];
        [, $soft_window] = $cfg['soft_lock'];
        $count = self::countAttempts($db, $action_key, $soft_window);
        $block = self::getActiveBlock($db, $action_key);
        return [
            'action_key'    => $action_key,
            'attempts'      => $count,
            'active_block'  => $block,
            'is_blocked'    => $block !== null,
        ];
    }

    // ─────────────────────────────────────────────────────────────
    // MÉTODOS INTERNOS
    // ─────────────────────────────────────────────────────────────

    private static function isWhitelisted(string $ip): bool {
        return in_array($ip, self::$whitelist);
    }

    private static function buildKey(string $action, string $identifier): string {
        return $action . ':' . hash('sha256', $identifier); // Hash para privacidade
    }

    private static function countAttempts(PDO $db, string $action_key, int $window_seconds): int {
        try {
            $stmt = $db->prepare("
                SELECT COUNT(*) FROM rate_limit_attempts
                WHERE action_key = ? 
                  AND created_at >= NOW() - INTERVAL '1 second' * ?
            ");
            $stmt->execute([$action_key, $window_seconds]);
            return (int)$stmt->fetchColumn();
        } catch (PDOException $e) {
            return 0; // Em caso de erro de BD, não bloquear (fail open)
        }
    }

    private static function logAttempt(PDO $db, string $action_key, string $ip): void {
        try {
            $db->prepare("
                INSERT INTO rate_limit_attempts (action_key, ip_address, created_at)
                VALUES (?, ?::INET, NOW())
            ")->execute([$action_key, $ip ?: '0.0.0.0']);
        } catch (PDOException $e) {
            // Silencioso — não bloquear a execução por erro de log
        }
    }

    private static function getActiveBlock(PDO $db, string $action_key): ?array {
        try {
            $stmt = $db->prepare("
                SELECT block_type, unblock_at, reason
                FROM rate_limit_blocks
                WHERE action_key = ?
                  AND unblocked_at IS NULL
                  AND (unblock_at IS NULL OR unblock_at > NOW())
                LIMIT 1
            ");
            $stmt->execute([$action_key]);
            $row = $stmt->fetch(PDO::FETCH_ASSOC);
            return $row ?: null;
        } catch (PDOException $e) {
            return null;
        }
    }

    private static function createBlock(PDO $db, string $action_key, string $type, int $count, string $reason, int $duration_seconds = 0): void {
        try {
            $unblock_at = ($type === 'hard' || $duration_seconds === 0)
                ? null
                : date('Y-m-d H:i:s', time() + $duration_seconds);

            // Upsert — se já existir, atualizar
            $stmt = $db->prepare("
                INSERT INTO rate_limit_blocks (action_key, block_type, attempt_count, blocked_at, unblock_at, reason)
                VALUES (?, ?, ?, NOW(), ?, ?)
                ON CONFLICT (action_key) DO UPDATE SET
                    block_type    = EXCLUDED.block_type,
                    attempt_count = EXCLUDED.attempt_count,
                    blocked_at    = NOW(),
                    unblock_at    = EXCLUDED.unblock_at,
                    reason        = EXCLUDED.reason,
                    unblocked_at  = NULL,
                    unblocked_by  = NULL,
                    admin_note    = NULL
            ");
            $stmt->execute([$action_key, $type, $count, $unblock_at, $reason]);
        } catch (PDOException $e) {
            // Silencioso
        }
    }

    /**
     * Remove registos de tentativas expirados da base de dados.
     * Chamado automaticamente com probabilidade de 1/50 por pedido.
     */
    public static function cleanup(PDO $db): void {
        try {
            // Manter apenas os últimos 24h de tentativas
            $db->exec("DELETE FROM rate_limit_attempts WHERE created_at < NOW() - INTERVAL '24 hours'");
            // Remover bloqueios soft expirados há mais de 7 dias
            $db->exec("DELETE FROM rate_limit_blocks WHERE block_type = 'soft' AND unblock_at < NOW() - INTERVAL '7 days'");
        } catch (PDOException $e) {
            // Silencioso
        }
    }

    /**
     * Formata duração em segundos para texto legível em português.
     */
    private static function formatDuration(int $seconds): string {
        if ($seconds <= 0)  return 'algum tempo';
        if ($seconds < 60)  return "{$seconds} segundo(s)";
        if ($seconds < 3600) {
            $min = round($seconds / 60);
            return "{$min} minuto(s)";
        }
        $hrs = round($seconds / 3600);
        return "{$hrs} hora(s)";
    }

    /**
     * Constrói o array de resultado padronizado.
     */
    private static function result(bool $allowed, string $phase, string $message, int $retry_after, int $remaining): array {
        return compact('allowed', 'phase', 'message', 'retry_after', 'remaining');
    }

    /**
     * Obtém o IP real do visitante, considerando proxies/load balancers.
     */
    public static function getRealIP(): string {
        $candidates = [
            $_SERVER['HTTP_CF_CONNECTING_IP'] ?? '',    // Cloudflare
            $_SERVER['HTTP_X_FORWARDED_FOR'] ?? '',      // Proxy/Load Balancer
            $_SERVER['HTTP_X_REAL_IP'] ?? '',
            $_SERVER['REMOTE_ADDR'] ?? '',
        ];
        foreach ($candidates as $ip) {
            $ip = trim(explode(',', $ip)[0]); // Pode vir uma lista separada por vírgulas
            if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) {
                return $ip;
            }
        }
        return $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';
    }
}
?>
