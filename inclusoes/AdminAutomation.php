<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/SimpleSMS.php';

class AdminAutomation {
    private PDO $db;
    private array $settings = [];
    private ?int $actorId;

    public function __construct(?PDO $db = null, ?int $actorId = null) {
        $this->db = $db ?: (new Database())->getConnection();
        $this->actorId = $actorId;
        $this->ensureTables();
        $this->settings = $this->loadSettings();
    }

    public function run(bool $dryRun = false): array {
        if (!$this->enabled('automation_enabled', true)) {
            return ['success' => true, 'dry_run' => $dryRun, 'message' => 'Automacao desligada.', 'actions' => [], 'counts' => []];
        }

        $actions = [];
        $actions = array_merge($actions, $this->processPendingKyc($dryRun));
        $actions = array_merge($actions, $this->processPendingMentors($dryRun));
        $actions = array_merge($actions, $this->processPendingProjects($dryRun));
        $actions = array_merge($actions, $this->processPendingProgressReports($dryRun));
        $actions = array_merge($actions, $this->processPendingInvestments($dryRun));
        $actions = array_merge($actions, $this->processUnreadSupport($dryRun));
        $actions = array_merge($actions, $this->archiveOldNotifications($dryRun));
        $actions = array_merge($actions, $this->expireOldOtpCodes($dryRun));
        $actions = array_merge($actions, $this->expirePastMentorshipSlots($dryRun));
        $actions = array_merge($actions, $this->flagDormantUsers($dryRun));
        $actions = array_merge($actions, $this->cleanupAutomationHistory($dryRun));

        if (!$dryRun) {
            $this->saveSetting('automation_last_run_at', date('Y-m-d H:i:s'));
            $this->logRun($actions);
        }

        return [
            'success' => true,
            'dry_run' => $dryRun,
            'message' => count($actions) . ' acção(oes) processada(s).',
            'actions' => $actions,
            'counts' => array_count_values(array_column($actions, 'type')),
        ];
    }

    private function processPendingKyc(bool $dryRun): array {
        if (!$this->enabled('automation_kyc_reminders', true) || !$this->tableExists('users')) {
            return [];
        }

        $hours = $this->intSetting('automation_kyc_hours', 24, 1, 720);
        $timeColumn = $this->columnExists('users', 'updated_at') ? 'updated_at' : 'created_at';
        $sql = "SELECT user_id, full_name FROM users WHERE verification_status = 'pending' AND COALESCE($timeColumn, NOW()) <= NOW() - (? * INTERVAL '1 hour') LIMIT 50";
        return $this->notifyRows($sql, [$hours], 'kyc_pending', 'KYC pendente ha mais de ' . $hours . 'h', 'Validar identidade pendente', 'users/kyc_requests.php', $dryRun);
    }

    private function processPendingMentors(bool $dryRun): array {
        if (!$this->enabled('automation_mentor_reminders', true) || !$this->tableExists('users')) {
            return [];
        }

        $hours = $this->intSetting('automation_mentor_hours', 48, 1, 720);
        $timeColumn = $this->columnExists('users', 'updated_at') ? 'updated_at' : 'created_at';
        $sql = "SELECT user_id, full_name FROM users WHERE mentorship_status = 'pending' AND COALESCE($timeColumn, NOW()) <= NOW() - (? * INTERVAL '1 hour') LIMIT 50";
        return $this->notifyRows($sql, [$hours], 'mentor_pending', 'Candidatura de mentor ha mais de ' . $hours . 'h', 'Rever candidatura de mentor', 'users/mentor_applications.php', $dryRun);
    }

    private function processPendingProjects(bool $dryRun): array {
        if (!$this->enabled('automation_project_reminders', true) || !$this->tableExists('projects')) {
            return [];
        }

        $hours = $this->intSetting('automation_project_hours', 24, 1, 720);
        $sql = "SELECT project_id AS user_id, title AS full_name FROM projects WHERE approval_status = 'pending' AND COALESCE(created_at, NOW()) <= NOW() - (? * INTERVAL '1 hour') LIMIT 50";
        return $this->notifyRows($sql, [$hours], 'project_pending', 'Projeto por moderar ha mais de ' . $hours . 'h', 'Moderar projeto pendente', 'moderation/moderation.php', $dryRun, 'project_id');
    }

    private function processPendingProgressReports(bool $dryRun): array {
        if (!$this->enabled('automation_progress_reminders', true) || !$this->tableExists('project_progress_reports')) {
            return [];
        }

        $hours = $this->intSetting('automation_progress_hours', 24, 1, 720);
        $sql = "SELECT report_id AS user_id, title AS full_name FROM project_progress_reports WHERE report_status = 'pending_admin' AND COALESCE(created_at, NOW()) <= NOW() - (? * INTERVAL '1 hour') LIMIT 50";
        return $this->notifyRows($sql, [$hours], 'progress_pending', 'Relatório de progresso ha mais de ' . $hours . 'h', 'Validar relatório de progresso', 'manage_progress.php', $dryRun, 'report_id');
    }

    private function processPendingInvestments(bool $dryRun): array {
        if (!$this->enabled('automation_investment_reminders', true) || !$this->tableExists('project_investments')) {
            return [];
        }

        $hours = $this->intSetting('automation_investment_hours', 12, 1, 720);
        $sql = "SELECT investment_id AS user_id, ('Investimento #' || investment_id) AS full_name FROM project_investments WHERE status = 'pending' AND COALESCE(created_at, NOW()) <= NOW() - (? * INTERVAL '1 hour') LIMIT 50";
        return $this->notifyRows($sql, [$hours], 'investment_pending', 'Investimento pendente ha mais de ' . $hours . 'h', 'Validar comprovativo financeiro', 'finance/finance_dashboard.php', $dryRun, 'investment_id');
    }

    private function processUnreadSupport(bool $dryRun): array {
        if (!$this->enabled('automation_support_escalation', true) || !$this->tableExists('support_messages')) {
            return [];
        }

        $hours = $this->intSetting('automation_support_hours', 6, 1, 720);
        $unread = $this->boolComparison('support_messages', 'is_read', false);
        $sql = "SELECT id AS user_id, ('Suporte #' || id) AS full_name FROM support_messages WHERE $unread AND COALESCE(created_at, NOW()) <= NOW() - (? * INTERVAL '1 hour') LIMIT 50";
        return $this->notifyRows($sql, [$hours], 'support_unread', 'Suporte sem leitura ha mais de ' . $hours . 'h', 'Responder suporte prioritario', 'moderation/support.php', $dryRun, 'support_id');
    }

    private function archiveOldNotifications(bool $dryRun): array {
        if (!$this->enabled('automation_archive_notifications', true) || !$this->tableExists('notifications')) {
            return [];
        }

        $days = $this->intSetting('automation_archive_notifications_days', 90, 7, 3650);
        $unread = $this->boolComparison('notifications', 'is_read', false);
        $readValue = $this->boolLiteral('notifications', 'is_read', true);
        $count = (int)$this->db->query("SELECT COUNT(*) FROM notifications WHERE $unread AND created_at <= NOW() - ($days * INTERVAL '1 day')")->fetchColumn();
        if ($count <= 0) {
            return [];
        }

        if (!$dryRun) {
            $stmt = $this->db->prepare("UPDATE notifications SET is_read = $readValue WHERE $unread AND created_at <= NOW() - (? * INTERVAL '1 day')");
            $stmt->execute([$days]);
        }

        return [['type' => 'notifications_archived', 'label' => $count . ' notificacao(oes) antigas marcadas como lidas']];
    }

    private function expireOldOtpCodes(bool $dryRun): array {
        if (!$this->enabled('automation_expire_otp_codes', true) || !$this->tableExists('otp_codes') || !$this->columnExists('otp_codes', 'used_at')) {
            return [];
        }

        $count = (int)$this->db->query("SELECT COUNT(*) FROM otp_codes WHERE used_at IS NULL AND expires_at < NOW()")->fetchColumn();
        if ($count <= 0) {
            return [];
        }

        if (!$dryRun) {
            $this->db->exec("UPDATE otp_codes SET used_at = NOW() WHERE used_at IS NULL AND expires_at < NOW()");
        }

        return [['type' => 'otp_expired', 'label' => $count . ' codigo(s) OTP expirado(s) encerrado(s)']];
    }

    private function expirePastMentorshipSlots(bool $dryRun): array {
        if (!$this->enabled('automation_expire_mentorship_slots', true) || !$this->tableExists('mentorship_slots')) {
            return [];
        }

        $count = (int)$this->db->query("SELECT COUNT(*) FROM mentorship_slots WHERE status IN ('pending', 'booked', 'confirmed') AND end_time < NOW()")->fetchColumn();
        if ($count <= 0) {
            return [];
        }

        if (!$dryRun) {
            $this->db->exec("UPDATE mentorship_slots SET status = 'expired' WHERE status IN ('pending', 'booked', 'confirmed') AND end_time < NOW()");
        }

        return [['type' => 'mentorship_slots_expired', 'label' => $count . ' horario(s) de mentoria expirado(s) fechado(s)']];
    }

    private function flagDormantUsers(bool $dryRun): array {
        if (!$this->enabled('automation_dormant_user_alerts', true) || !$this->tableExists('users')) {
            return [];
        }

        $activityColumn = $this->columnExists('users', 'last_activity') ? 'last_activity' : ($this->columnExists('users', 'updated_at') ? 'updated_at' : 'created_at');
        $days = $this->intSetting('automation_dormant_user_days', 30, 7, 365);
        $sql = "SELECT user_id, full_name FROM users WHERE user_type NOT IN ('admin', 'superadmin') AND COALESCE($activityColumn, created_at) <= NOW() - (? * INTERVAL '1 day') LIMIT 50";
        return $this->notifyRows($sql, [$days], 'dormant_user', 'Utilizador sem atividade ha mais de ' . $days . ' dias', 'Rever utilizador inativo', 'users/manage_users.php', $dryRun);
    }

    private function cleanupAutomationHistory(bool $dryRun): array {
        if (!$this->enabled('automation_cleanup_history', true)) {
            return [];
        }

        $days = $this->intSetting('automation_history_days', 180, 30, 3650);
        $count = (int)$this->db->query("SELECT COUNT(*) FROM automation_events WHERE created_at <= NOW() - ($days * INTERVAL '1 day')")->fetchColumn();
        if ($count <= 0) {
            return [];
        }

        if (!$dryRun) {
            $stmt = $this->db->prepare("DELETE FROM automation_events WHERE created_at <= NOW() - (? * INTERVAL '1 day')");
            $stmt->execute([$days]);
        }

        return [['type' => 'automation_history_cleaned', 'label' => $count . ' evento(s) antigos de automacao removido(s)']];
    }

    private function notifyRows(string $sql, array $params, string $type, string $content, string $title, string $link, bool $dryRun, string $entityLabel = 'user_id'): array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
        $actions = [];

        foreach ($rows as $row) {
            $entityId = (int)$row['user_id'];
            $eventKey = $type . ':' . $entityId . ':' . date('Y-m-d');
            if (!$dryRun && !$this->registerEvent($eventKey, $type, $entityId, $content)) {
                continue;
            }

            if (!$dryRun) {
                $this->notifyAdmins($title, $content . ' - ' . ($row['full_name'] ?? ('#' . $entityId)), 'automation', 'administracao/' . $link);
                $this->notifyAdminsBySms($type, $title, $row['full_name'] ?? ('#' . $entityId));
            }

            $actions[] = ['type' => $type, 'label' => $title . ': ' . ($row['full_name'] ?? ($entityLabel . ' #' . $entityId))];
        }

        return $actions;
    }

    private function notifyAdmins(string $title, string $content, string $type, string $link): void {
        if (!$this->tableExists('notifications')) {
            return;
        }

        $admins = $this->db->query("SELECT user_id FROM users WHERE user_type IN ('admin', 'superadmin')")->fetchAll(PDO::FETCH_COLUMN);
        $unreadValue = $this->boolLiteral('notifications', 'is_read', false);
        $stmt = $this->db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, is_read, created_at) VALUES (?, ?, ?, ?, ?, ?, $unreadValue, NOW())");
        foreach ($admins as $adminId) {
            $stmt->execute([(int)$adminId, $this->actorId, $title, $content, $type, $link]);
        }
    }

    private function notifyAdminsBySms(string $type, string $title, string $entityName): void {
        if (!$this->enabled('automation_sms_alerts', false) || !$this->enabled('sms_enabled', false) || !$this->tableExists('users')) {
            return;
        }

        $urgentTypes = ['support_unread', 'investment_pending', 'kyc_pending', 'mentor_pending'];
        if (!in_array($type, $urgentTypes, true)) {
            return;
        }

        $phoneColumn = $this->firstExistingColumn('users', ['phone', 'phone_number', 'telefone', 'telemovel', 'whatsapp']);
        if ($phoneColumn === null) {
            return;
        }

        $stmt = $this->db->query("SELECT $phoneColumn AS phone FROM users WHERE user_type IN ('admin', 'superadmin') AND $phoneColumn IS NOT NULL AND $phoneColumn <> '' LIMIT 10");
        $phones = $stmt ? $stmt->fetchAll(PDO::FETCH_COLUMN) : [];
        if (!$phones) {
            return;
        }

        $sms = new SimpleSMS($this->db);
        $message = 'KALIYE Admin: ' . $title . ' - ' . $entityName;
        foreach ($phones as $phone) {
            $sms->send($phone, $message);
        }
    }

    private function registerEvent(string $eventKey, string $type, int $entityId, string $details): bool {
        $stmt = $this->db->prepare("
            INSERT INTO automation_events (event_key, entity_type, entity_id, action, details, created_at)
            VALUES (?, ?, ?, ?, ?, NOW())
            ON CONFLICT (event_key) DO NOTHING
        ");
        $stmt->execute([$eventKey, $type, $entityId, 'notify', $details]);
        return $stmt->rowCount() > 0;
    }

    private function logRun(array $actions): void {
        if ($this->tableExists('audit_logs')) {
            $stmt = $this->db->prepare("INSERT INTO audit_logs (admin_id, action, details, created_at) VALUES (?, ?, ?, NOW())");
            $stmt->execute([$this->actorId, 'automation_run', count($actions) . ' acção(oes): ' . implode('; ', array_slice(array_column($actions, 'label'), 0, 8))]);
        }

        $stmt = $this->db->prepare("INSERT INTO automation_runs (actor_id, actions_count, details, created_at) VALUES (?, ?, ?, NOW())");
        $stmt->execute([$this->actorId, count($actions), json_encode($actions, JSON_UNESCAPED_UNICODE)]);
    }

    private function ensureTables(): void {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS automation_events (
                id SERIAL PRIMARY KEY,
                event_key VARCHAR(180) NOT NULL UNIQUE,
                entity_type VARCHAR(80) NOT NULL,
                entity_id INTEGER NOT NULL,
                action VARCHAR(80) NOT NULL,
                details TEXT,
                created_at TIMESTAMP DEFAULT NOW()
            )
        ");
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS automation_runs (
                id SERIAL PRIMARY KEY,
                actor_id INTEGER NULL,
                actions_count INTEGER NOT NULL DEFAULT 0,
                details TEXT,
                created_at TIMESTAMP DEFAULT NOW()
            )
        ");
    }

    private function loadSettings(): array {
        if (!$this->tableExists('settings')) {
            return [];
        }

        return $this->db->query("SELECT setting_key, setting_value FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR) ?: [];
    }

    private function saveSetting(string $key, string $value): void {
        $stmt = $this->db->prepare("INSERT INTO settings (setting_key, setting_value) VALUES (?, ?) ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value");
        $stmt->execute([$key, $value]);
    }

    private function enabled(string $key, bool $default): bool {
        $value = $this->settings[$key] ?? ($default ? '1' : '0');
        return in_array(strtolower((string)$value), ['1', 'true', 't', 'yes', 'on'], true);
    }

    private function intSetting(string $key, int $default, int $min, int $max): int {
        $value = (int)($this->settings[$key] ?? $default);
        return max($min, min($max, $value));
    }

    private function tableExists(string $table): bool {
        $stmt = $this->db->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?)");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool {
        $stmt = $this->db->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?)");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }

    private function firstExistingColumn(string $table, array $columns): ?string {
        foreach ($columns as $column) {
            if ($this->columnExists($table, $column)) {
                return $column;
            }
        }

        return null;
    }

    private function columnDataType(string $table, string $column): ?string {
        $stmt = $this->db->prepare("SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ? LIMIT 1");
        $stmt->execute([$table, $column]);
        $type = $stmt->fetchColumn();
        return $type === false ? null : strtolower((string)$type);
    }

    private function boolLiteral(string $table, string $column, bool $value): string {
        return $this->columnDataType($table, $column) === 'boolean'
            ? ($value ? 'TRUE' : 'FALSE')
            : ($value ? '1' : '0');
    }

    private function boolComparison(string $table, string $column, bool $value, ?string $qualifiedColumn = null): string {
        $left = $qualifiedColumn ?: $column;
        return $left . ' = ' . $this->boolLiteral($table, $column, $value);
    }
}
?>
