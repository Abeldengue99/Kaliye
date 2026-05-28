<?php
/**
 * Central security helpers for KALIYE.
 */
class Security {
    public static function hardenAuthenticatedSession(int $userId): void {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        session_regenerate_id(true);
        $_SESSION['last_auth_at'] = time();
        $_SESSION['session_started_at'] = $_SESSION['session_started_at'] ?? time();
        $_SESSION['device_fingerprint'] = self::deviceFingerprint();
        $_SESSION['authenticated_user_id'] = $userId;
    }

    public static function requireFreshAuth(int $maxAgeSeconds = 900): bool {
        if (session_status() !== PHP_SESSION_ACTIVE) {
            session_start();
        }

        $lastAuth = (int)($_SESSION['last_auth_at'] ?? 0);
        return $lastAuth > 0 && (time() - $lastAuth) <= $maxAgeSeconds;
    }

    public static function deviceFingerprint(): string {
        $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        $ua = $_SERVER['HTTP_USER_AGENT'] ?? '';
        $lang = $_SERVER['HTTP_ACCEPT_LANGUAGE'] ?? '';
        $encoding = $_SERVER['HTTP_ACCEPT_ENCODING'] ?? '';

        return hash('sha256', implode('|', [$ip, $ua, $lang, $encoding]));
    }

    public static function assessLoginRisk(PDO $db, int $userId, array $deviceInfo, array $geoInfo): array {
        $risk = 0;
        $signals = [];

        try {
            $stmt = $db->prepare("
                SELECT ip_address, user_agent, country, city
                FROM login_logs
                WHERE user_id = ?
                ORDER BY login_time DESC
                LIMIT 1
            ");
            $stmt->execute([$userId]);
            $last = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($last) {
                if (!empty($last['country']) && !empty($geoInfo['country']) && $last['country'] !== $geoInfo['country']) {
                    $risk += 45;
                    $signals[] = 'country_changed';
                }
                if (!empty($last['city']) && !empty($geoInfo['city']) && $last['city'] !== $geoInfo['city']) {
                    $risk += 20;
                    $signals[] = 'city_changed';
                }
                if (!empty($last['user_agent']) && !empty($deviceInfo['user_agent']) && $last['user_agent'] !== $deviceInfo['user_agent']) {
                    $risk += 20;
                    $signals[] = 'device_changed';
                }
                if (!empty($last['ip_address']) && !empty($deviceInfo['ip']) && $last['ip_address'] !== $deviceInfo['ip']) {
                    $risk += 15;
                    $signals[] = 'ip_changed';
                }
            }
        } catch (Throwable $e) {
            $signals[] = 'risk_lookup_failed';
        }

        return [
            'score' => min(100, $risk),
            'signals' => $signals,
            'requires_step_up' => $risk >= 60,
        ];
    }

    public static function uploadErrorMessage(int $errorCode): string {
        $messages = [
            UPLOAD_ERR_INI_SIZE => 'O ficheiro excede o limite configurado no servidor.',
            UPLOAD_ERR_FORM_SIZE => 'O ficheiro excede o limite permitido.',
            UPLOAD_ERR_PARTIAL => 'O upload ficou incompleto. Tente novamente.',
            UPLOAD_ERR_NO_FILE => 'Nenhum ficheiro foi recebido.',
            UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporaria do servidor não esta disponivel.',
            UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o ficheiro.',
            UPLOAD_ERR_EXTENSION => 'Uma extensao do servidor bloqueou o upload.',
        ];

        return $messages[$errorCode] ?? 'Erro desconhecido ao receber o ficheiro.';
    }

    public static function storeUploadedFile(array $file, string $absoluteDir, string $relativeDir, array $allowedMimeMap, int $maxBytes, string $prefix): array {
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            return ['ok' => false, 'error' => self::uploadErrorMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))];
        }
        if (!is_uploaded_file($file['tmp_name'] ?? '')) {
            return ['ok' => false, 'error' => 'Upload invalido.'];
        }

        $size = (int)($file['size'] ?? 0);
        if ($size <= 0 || $size > $maxBytes) {
            return ['ok' => false, 'error' => 'O ficheiro excede o tamanho permitido.'];
        }

        $mime = strtolower((string)($file['type'] ?? ''));
        if (function_exists('finfo_open')) {
            $finfo = finfo_open(FILEINFO_MIME_TYPE);
            if ($finfo) {
                $detected = strtolower((string)finfo_file($finfo, $file['tmp_name']));
                finfo_close($finfo);
                $mime = $detected ?: $mime;
            }
        }

        if (!isset($allowedMimeMap[$mime])) {
            return ['ok' => false, 'error' => 'Tipo de ficheiro não permitido.'];
        }

        $absoluteDir = rtrim($absoluteDir, "/\\") . DIRECTORY_SEPARATOR;
        if (!is_dir($absoluteDir) && !mkdir($absoluteDir, 0755, true) && !is_dir($absoluteDir)) {
            return ['ok' => false, 'error' => 'Não foi possível preparar a pasta de uploads.'];
        }

        $safePrefix = preg_replace('/[^a-zA-Z0-9_-]/', '_', $prefix) ?: 'upload';
        $filename = $safePrefix . '_' . time() . '_' . bin2hex(random_bytes(8)) . '.' . $allowedMimeMap[$mime];
        $target = $absoluteDir . $filename;

        if (!move_uploaded_file($file['tmp_name'], $target)) {
            return ['ok' => false, 'error' => 'Falha ao guardar o ficheiro.'];
        }

        return [
            'ok' => true,
            'path' => rtrim($relativeDir, "/\\") . '/' . $filename,
            'filename' => $filename,
            'mime' => $mime,
            'size' => $size,
        ];
    }

    public static function logActivity(PDO $db, ?int $userId, string $action, string $details = '', string $severity = 'info'): void {
        try {
            $stmt = $db->prepare("
                INSERT INTO activity_logs (user_id, action, ip_address, device_type, device_brand, details)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $userId,
                $action,
                $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0',
                'security',
                $severity,
                $details,
            ]);
        } catch (Throwable $e) {
            error_log('Security activity log failed: ' . $e->getMessage());
        }
    }
}
?>
