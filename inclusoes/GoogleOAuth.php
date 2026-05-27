<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/SystemSettings.php';
require_once __DIR__ . '/Security.php';

class GoogleOAuth {
    private PDO $db;
    private array $settings;

    public function __construct(PDO $db) {
        $this->db = $db;
        $this->settings = $this->loadSettings();
    }

    public function isEnabled(): bool {
        return $this->enabledValue($this->settings['google_auth_enabled'] ?? '0')
            && $this->clientId() !== ''
            && $this->clientSecret() !== '';
    }

    public function authorizationUrl(string $mode = 'login'): string {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Cadastro com Google indisponivel.');
        }

        $state = bin2hex(random_bytes(24));
        $_SESSION['google_oauth_state'] = $state;
        $_SESSION['google_oauth_mode'] = $mode === 'register' ? 'register' : 'login';

        $params = [
            'client_id' => $this->clientId(),
            'redirect_uri' => $this->redirectUri(),
            'response_type' => 'code',
            'scope' => 'openid email profile',
            'state' => $state,
            'prompt' => 'select_account',
            'access_type' => 'online',
        ];

        return 'https://accounts.google.com/o/oauth2/v2/auth?' . http_build_query($params);
    }

    public function handleCallback(string $code, string $state): array {
        if (!$this->isEnabled()) {
            throw new RuntimeException('Cadastro com Google indisponivel.');
        }

        if (empty($_SESSION['google_oauth_state']) || !hash_equals($_SESSION['google_oauth_state'], $state)) {
            throw new RuntimeException('Pedido Google invalido. Tente novamente.');
        }

        unset($_SESSION['google_oauth_state']);
        $token = $this->exchangeCodeForToken($code);
        $profile = $this->fetchGoogleProfile($token['access_token'] ?? '');

        if (empty($profile['sub']) || empty($profile['email'])) {
            throw new RuntimeException('Nao foi possivel obter o email da conta Google.');
        }

        if (isset($profile['email_verified']) && !$this->enabledValue($profile['email_verified'])) {
            throw new RuntimeException('A conta Google ainda nao tem email verificado.');
        }

        $this->ensureUserColumns();
        $user = $this->findOrCreateUser($profile);
        $this->startSession($user);

        return $user;
    }

    public function profileNeedsCompletion(array $user): bool {
        $required = [
            $user['phone'] ?? '',
            $user['birth_date'] ?? '',
            $user['id_number'] ?? '',
            $user['user_type'] ?? '',
        ];

        foreach ($required as $value) {
            if (trim((string)$value) === '') {
                return true;
            }
        }

        return !$this->enabledValue($user['profile_completed'] ?? '0');
    }

    public function completeGoogleProfile(int $userId, array $data): void {
        $this->ensureUserColumns();

        $fullName = trim((string)($data['full_name'] ?? ''));
        $phone = trim((string)($data['phone'] ?? ''));
        $idNumber = strtoupper(trim((string)($data['id_number'] ?? '')));
        $birthDate = trim((string)($data['birth_date'] ?? ''));
        $userType = trim((string)($data['user_type'] ?? ''));

        if ($fullName === '' || $phone === '' || $idNumber === '' || $birthDate === '' || $userType === '') {
            throw new InvalidArgumentException('Preencha todos os campos obrigatorios.');
        }

        if (!in_array($userType, ['univ_student', 'high_student', 'mentor', 'investor'], true)) {
            throw new InvalidArgumentException('Tipo de perfil invalido.');
        }

        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $birthDate)) {
            throw new InvalidArgumentException('Data de nascimento invalida.');
        }

        $stmt = $this->db->prepare("
            UPDATE users
            SET full_name = ?, phone = ?, id_number = ?, birth_date = ?, user_type = ?, profile_completed = TRUE, updated_at = NOW()
            WHERE user_id = ?
        ");
        $stmt->execute([$fullName, $phone, $idNumber, $birthDate, $userType, $userId]);

        $_SESSION['user_name'] = $fullName;
        $_SESSION['full_name'] = $fullName;
        $_SESSION['user_type'] = $userType;
        $_SESSION['profile_completed'] = true;
        $_SESSION['google_profile_incomplete'] = false;
    }

    private function findOrCreateUser(array $profile): array {
        $stmt = $this->db->prepare("SELECT * FROM users WHERE google_sub = ? OR LOWER(email) = LOWER(?) LIMIT 1");
        $stmt->execute([$profile['sub'], $profile['email']]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user) {
            $update = $this->db->prepare("
                UPDATE users
                SET google_sub = COALESCE(google_sub, ?),
                    auth_provider = CASE WHEN auth_provider = 'local' THEN 'google' ELSE auth_provider END,
                    is_verified = TRUE,
                    profile_pic = COALESCE(NULLIF(profile_pic, ''), ?),
                    last_login_at = NOW()
                WHERE user_id = ?
            ");
            $update->execute([$profile['sub'], $profile['picture'] ?? null, (int)$user['user_id']]);

            $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
            $stmt->execute([(int)$user['user_id']]);
            return $stmt->fetch(PDO::FETCH_ASSOC);
        }

        $passwordHash = password_hash(bin2hex(random_bytes(32)), PASSWORD_BCRYPT);
        $name = trim((string)($profile['name'] ?? 'Utilizador KALIYE'));
        $picture = trim((string)($profile['picture'] ?? ''));

        $insert = $this->db->prepare("
            INSERT INTO users (
                full_name, email, password_hash, user_type, is_verified, verification_status,
                auth_provider, google_sub, profile_pic, profile_completed, created_at, last_login_at
            )
            VALUES (?, ?, ?, 'univ_student', TRUE, 'unsubmitted', 'google', ?, ?, FALSE, NOW(), NOW())
            RETURNING user_id
        ");
        $insert->execute([$name, $profile['email'], $passwordHash, $profile['sub'], $picture]);
        $userId = (int)$insert->fetchColumn();

        $stmt = $this->db->prepare("SELECT * FROM users WHERE user_id = ? LIMIT 1");
        $stmt->execute([$userId]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    private function startSession(array $user): void {
        Security::hardenAuthenticatedSession((int)$user['user_id']);

        $_SESSION['user_id'] = $user['user_id'];
        $_SESSION['user_name'] = $user['full_name'];
        $_SESSION['full_name'] = $user['full_name'];
        $_SESSION['user_type'] = $user['user_type'] ?: 'univ_student';
        $_SESSION['mentorship_status'] = $user['mentorship_status'] ?? 'unsubmitted';
        $_SESSION['verification_status'] = $user['verification_status'] ?? 'unsubmitted';
        $_SESSION['email_verified'] = true;
        $_SESSION['is_email_verified'] = true;
        $_SESSION['is_verified'] = (($user['verification_status'] ?? '') === 'verified');
        $_SESSION['auth_provider'] = $user['auth_provider'] ?? 'google';
        $_SESSION['profile_completed'] = !$this->profileNeedsCompletion($user);
        $_SESSION['google_profile_incomplete'] = !$_SESSION['profile_completed'];
        $_SESSION['last_activity_at'] = time();
    }

    private function exchangeCodeForToken(string $code): array {
        $response = $this->postForm('https://oauth2.googleapis.com/token', [
            'code' => $code,
            'client_id' => $this->clientId(),
            'client_secret' => $this->clientSecret(),
            'redirect_uri' => $this->redirectUri(),
            'grant_type' => 'authorization_code',
        ]);

        if (empty($response['access_token'])) {
            throw new RuntimeException('Falha ao validar a conta Google.');
        }

        return $response;
    }

    private function fetchGoogleProfile(string $accessToken): array {
        if ($accessToken === '') {
            throw new RuntimeException('Token Google ausente.');
        }

        $ch = curl_init('https://openidconnect.googleapis.com/v1/userinfo');
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Authorization: Bearer ' . $accessToken]);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Falha ao obter perfil Google: ' . ($raw ?: $error));
        }

        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    private function postForm(string $url, array $fields): array {
        if (!function_exists('curl_init')) {
            throw new RuntimeException('A extensao cURL do PHP e necessaria para Google OAuth.');
        }

        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_POST, true);
        curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($fields));
        curl_setopt($ch, CURLOPT_HTTPHEADER, ['Content-Type: application/x-www-form-urlencoded']);
        curl_setopt($ch, CURLOPT_TIMEOUT, 20);
        $raw = curl_exec($ch);
        $error = curl_error($ch);
        $status = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($status < 200 || $status >= 300) {
            throw new RuntimeException('Falha na comunicacao OAuth: ' . ($raw ?: $error));
        }

        $data = json_decode((string)$raw, true);
        return is_array($data) ? $data : [];
    }

    private function ensureUserColumns(): void {
        $columns = [
            'auth_provider' => "VARCHAR(30) DEFAULT 'local'",
            'google_sub' => 'VARCHAR(120)',
            'profile_completed' => 'BOOLEAN DEFAULT FALSE',
            'profile_pic' => 'TEXT',
            'phone' => 'VARCHAR(40)',
            'id_number' => 'VARCHAR(60)',
            'birth_date' => 'DATE',
            'updated_at' => 'TIMESTAMP NULL',
            'last_login_at' => 'TIMESTAMP NULL',
            'mentorship_status' => "VARCHAR(40) DEFAULT 'unsubmitted'",
            'verification_status' => "VARCHAR(40) DEFAULT 'unsubmitted'",
        ];

        foreach ($columns as $column => $definition) {
            try {
                $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS {$column} {$definition}");
            } catch (Throwable $e) {
                // Older databases or restricted users may already have the column.
            }
        }

        try {
            $this->db->exec("CREATE UNIQUE INDEX IF NOT EXISTS users_google_sub_unique ON users (google_sub) WHERE google_sub IS NOT NULL");
        } catch (Throwable $e) {
            error_log('Google OAuth index creation failed: ' . $e->getMessage());
        }
    }

    private function redirectUri(): string {
        $configured = trim((string)($this->settings['google_redirect_uri'] ?? ''));
        if ($configured !== '') {
            return $configured;
        }

        $scheme = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $host = $_SERVER['HTTP_HOST'] ?? 'localhost';
        $base = rtrim(dirname($_SERVER['SCRIPT_NAME'] ?? '/'), '/\\');
        $root = preg_replace('#/autenticacao$#', '', $base);

        return $scheme . '://' . $host . $root . '/autenticacao/google_callback.php';
    }

    private function clientId(): string {
        return trim((string)($this->settings['google_client_id'] ?? ''));
    }

    private function clientSecret(): string {
        return trim((string)($this->settings['google_client_secret'] ?? ''));
    }

    private function loadSettings(): array {
        $stmt = $this->db->query("SELECT setting_key, setting_value FROM settings WHERE setting_key LIKE 'google_%'");
        return $stmt ? ($stmt->fetchAll(PDO::FETCH_KEY_PAIR) ?: []) : [];
    }

    private function enabledValue($value): bool {
        return in_array(strtolower((string)$value), ['1', 'true', 't', 'yes', 'y', 'on'], true);
    }
}
