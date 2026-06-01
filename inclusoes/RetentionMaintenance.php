<?php
/**
 * Lightweight retention maintenance for operational tables.
 *
 * This module stores a JSON snapshot in data_archive_snapshots and marks
 * eligible rows with archived_at/archive_reason so normal operational queries
 * can skip old records. Expired mentorship resource files are also removed from
 * carregamentos/resources after their metadata snapshot is saved.
 */
class RetentionMaintenance
{
    private PDO $db;

    public function __construct(PDO $db)
    {
        $this->db = $db;
    }

    public function ensureSchema(): void
    {
        $this->db->exec("
            CREATE TABLE IF NOT EXISTS data_archive_snapshots (
                snapshot_id SERIAL PRIMARY KEY,
                source_table VARCHAR(120) NOT NULL,
                source_pk VARCHAR(120) NOT NULL,
                archive_reason VARCHAR(160) NOT NULL,
                payload TEXT NOT NULL,
                created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                UNIQUE(source_table, source_pk, archive_reason)
            )
        ");

        $this->db->exec("
            CREATE TABLE IF NOT EXISTS settings (
                setting_key VARCHAR(120) PRIMARY KEY,
                setting_value TEXT,
                updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
            )
        ");

        if ($this->tableExists('users')) {
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS mentor_application_submitted_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS mentor_application_archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS mentor_application_archive_reason VARCHAR(160) NULL");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS investor_application_submitted_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS investor_application_archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS investor_application_archive_reason VARCHAR(160) NULL");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_users_mentor_application_active ON users (mentorship_status, mentor_application_archived_at)");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_users_investor_application_active ON users (investor_status, investor_application_archived_at)");
        }

        if ($this->tableExists('project_investments')) {
            $this->db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(160) NULL");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_project_investments_operational ON project_investments (status, archived_at, created_at)");
        }

        if ($this->tableExists('notifications')) {
            $this->db->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE notifications ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(160) NULL");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_notifications_operational ON notifications (user_id, archived_at, created_at)");
        }

        if ($this->tableExists('institution_invitations')) {
            $this->db->exec("ALTER TABLE institution_invitations ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE institution_invitations ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(160) NULL");
        }

        if ($this->tableExists('free_mentorship_requests')) {
            $this->db->exec("ALTER TABLE free_mentorship_requests ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE free_mentorship_requests ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(160) NULL");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_operational ON free_mentorship_requests (status, archived_at, updated_at)");
        }

        if ($this->tableExists('mentorship_resources')) {
            $this->db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS original_filename VARCHAR(255)");
            $this->db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS file_type VARCHAR(120)");
            $this->db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS file_size BIGINT DEFAULT 0");
            $this->db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
            $this->db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(160) NULL");
            $this->db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_resources_retention ON mentorship_resources (archived_at, expires_at, created_at)");
        }
    }

    public function run(bool $dryRun = false): array
    {
        $this->ensureSchema();

        return [
            'mentor_applications' => $this->archiveMentorApplications($dryRun),
            'investor_profiles' => $this->archiveInvestorProfiles($dryRun),
            'investment_applications' => $this->archiveInvestmentApplications($dryRun),
            'notifications' => $this->archiveNotifications($dryRun),
            'invitations' => $this->archiveInvitations($dryRun),
            'completed_mentorships' => $this->archiveCompletedMentorships($dryRun),
            'mentorship_resources' => $this->archiveMentorshipResources($dryRun),
            'mentorship_tasks' => $this->archiveMentorshipTasks($dryRun),
            'mentorship_notices' => $this->archiveMentorshipNotices($dryRun),
        ];
    }

    public function runIfDue(int $intervalDays = 90): array
    {
        $this->ensureSchema();

        $lastRun = null;
        $stmt = $this->db->prepare("SELECT setting_value FROM settings WHERE setting_key = 'retention_last_run_at' LIMIT 1");
        $stmt->execute();
        $value = $stmt->fetchColumn();
        if ($value) {
            $lastRun = strtotime((string)$value);
        }

        if ($lastRun && $lastRun > strtotime("-{$intervalDays} days")) {
            return ['skipped' => 'retention recently executed'];
        }

        $result = $this->run(false);
        $save = $this->db->prepare("
            INSERT INTO settings (setting_key, setting_value)
            VALUES ('retention_last_run_at', ?)
            ON CONFLICT (setting_key) DO UPDATE SET setting_value = EXCLUDED.setting_value
        ");
        $save->execute([date('Y-m-d H:i:s')]);

        return $result;
    }

    private function archiveMentorApplications(bool $dryRun): int
    {
        if (!$this->tableExists('users')) {
            return 0;
        }

        $where = "
            mentorship_status = 'pending'
            AND mentor_application_archived_at IS NULL
            AND COALESCE(mentor_application_submitted_at, submitted_at, updated_at, created_at) <= NOW() - (90 * INTERVAL '1 day')
        ";

        return $this->snapshotAndArchive('users', 'user_id', $where, 'mentor_application_expired', [
            'archive_column' => 'mentor_application_archived_at',
            'reason_column' => 'mentor_application_archive_reason',
        ], $dryRun);
    }

    private function archiveInvestmentApplications(bool $dryRun): int
    {
        if (!$this->tableExists('project_investments')) {
            return 0;
        }

        $where = "
            archived_at IS NULL
            AND status IN ('pending', 'rejected', 'cancelled')
            AND COALESCE(created_at, updated_at) <= NOW() - (90 * INTERVAL '1 day')
        ";

        return $this->snapshotAndArchive('project_investments', 'investment_id', $where, 'investment_application_expired', [], $dryRun);
    }

    private function archiveInvestorProfiles(bool $dryRun): int
    {
        if (!$this->tableExists('users')) {
            return 0;
        }

        $where = "
            investor_status = 'pending'
            AND investor_application_archived_at IS NULL
            AND COALESCE(investor_application_submitted_at, submitted_at, updated_at, created_at) <= NOW() - (90 * INTERVAL '1 day')
        ";

        return $this->snapshotAndArchive('users', 'user_id', $where, 'investor_application_expired', [
            'archive_column' => 'investor_application_archived_at',
            'reason_column' => 'investor_application_archive_reason',
        ], $dryRun);
    }

    private function archiveNotifications(bool $dryRun): int
    {
        if (!$this->tableExists('notifications')) {
            return 0;
        }

        $readClause = $this->boolComparison('notifications', 'is_read', true);
        $where = "
            archived_at IS NULL
            AND $readClause
            AND created_at <= NOW() - (180 * INTERVAL '1 day')
        ";

        return $this->snapshotAndArchive('notifications', 'notification_id', $where, 'old_notification', [], $dryRun);
    }

    private function archiveInvitations(bool $dryRun): int
    {
        if (!$this->tableExists('institution_invitations')) {
            return 0;
        }

        $dateColumn = $this->columnExists('institution_invitations', 'expires_at') ? 'expires_at' : 'created_at';
        $where = "
            archived_at IS NULL
            AND COALESCE($dateColumn, created_at) <= NOW() - (90 * INTERVAL '1 day')
        ";

        return $this->snapshotAndArchive('institution_invitations', 'invitation_id', $where, 'expired_invitation', [], $dryRun);
    }

    private function archiveCompletedMentorships(bool $dryRun): int
    {
        if (!$this->tableExists('free_mentorship_requests')) {
            return 0;
        }

        $where = "
            archived_at IS NULL
            AND status = 'completed'
            AND COALESCE(completed_at, updated_at, created_at) <= NOW() - (180 * INTERVAL '1 day')
        ";

        return $this->snapshotAndArchive('free_mentorship_requests', 'request_id', $where, 'completed_mentorship_history', [], $dryRun);
    }

    private function archiveMentorshipResources(bool $dryRun): int
    {
        if (!$this->tableExists('mentorship_resources')) {
            return 0;
        }

        $where = "
            archived_at IS NULL
            AND COALESCE(expires_at, created_at + (180 * INTERVAL '1 day')) <= NOW()
        ";

        $stmt = $this->db->query("SELECT resource_id, resource_type, file_url FROM mentorship_resources WHERE $where");
        $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
        if (!$resources) {
            return 0;
        }

        $archived = $this->snapshotAndArchive('mentorship_resources', 'resource_id', $where, 'mentorship_resource_expired', [], $dryRun);
        if (!$dryRun && $archived > 0) {
            $this->deleteExpiredMentorshipResourceFiles($resources);
        }

        return $archived;
    }

    private function archiveMentorshipTasks(bool $dryRun): int
    {
        if (!$this->tableExists('mentorship_tasks')) {
            return 0;
        }

        $where = "
            expires_at IS NOT NULL
            AND expires_at <= NOW()
        ";

        $count = (int)$this->db->query("SELECT COUNT(*) FROM mentorship_tasks WHERE $where")->fetchColumn();
        if ($count <= 0) {
            return $count;
        }

        if (!$dryRun) {
            // Snapshot before deletion
            $this->db->exec("
                INSERT INTO data_archive_snapshots (source_table, source_pk, archive_reason, payload, created_at)
                SELECT 'mentorship_tasks', CAST(task_id AS TEXT), 'task_expired', row_to_json(src)::TEXT, NOW()
                FROM (SELECT * FROM mentorship_tasks WHERE $where) src
                ON CONFLICT (source_table, source_pk, archive_reason) DO NOTHING
            ");

            // Delete expired tasks
            $this->db->exec("DELETE FROM mentorship_tasks WHERE $where");
        }

        return $count;
    }

    private function archiveMentorshipNotices(bool $dryRun): int
    {
        if (!$this->tableExists('mentorship_notices')) {
            return 0;
        }

        $where = "
            expires_at IS NOT NULL
            AND expires_at <= NOW()
        ";

        $count = (int)$this->db->query("SELECT COUNT(*) FROM mentorship_notices WHERE $where")->fetchColumn();
        if ($count <= 0) {
            return $count;
        }

        if (!$dryRun) {
            // Snapshot before deletion
            $this->db->exec("
                INSERT INTO data_archive_snapshots (source_table, source_pk, archive_reason, payload, created_at)
                SELECT 'mentorship_notices', CAST(notice_id AS TEXT), 'notice_expired', row_to_json(src)::TEXT, NOW()
                FROM (SELECT * FROM mentorship_notices WHERE $where) src
                ON CONFLICT (source_table, source_pk, archive_reason) DO NOTHING
            ");

            // Delete expired notices
            $this->db->exec("DELETE FROM mentorship_notices WHERE $where");
        }

        return $count;
    }

    private function snapshotAndArchive(string $table, string $pk, string $where, string $reason, array $options, bool $dryRun): int
    {
        $count = (int)$this->db->query("SELECT COUNT(*) FROM $table WHERE $where")->fetchColumn();
        if ($count <= 0 || $dryRun) {
            return $count;
        }

        $archiveColumn = $options['archive_column'] ?? 'archived_at';
        $reasonColumn = $options['reason_column'] ?? 'archive_reason';

        $this->db->beginTransaction();
        try {
            $this->db->exec("
                INSERT INTO data_archive_snapshots (source_table, source_pk, archive_reason, payload, created_at)
                SELECT '$table', CAST($pk AS TEXT), '$reason', row_to_json(src)::TEXT, NOW()
                FROM (SELECT * FROM $table WHERE $where) src
                ON CONFLICT (source_table, source_pk, archive_reason) DO NOTHING
            ");

            $updated = $this->db->exec("
                UPDATE $table
                SET $archiveColumn = NOW(),
                    $reasonColumn = '$reason'
                WHERE $where
            ");
            $this->db->commit();

            return (int)$updated;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function tableExists(string $table): bool
    {
        $stmt = $this->db->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?)");
        $stmt->execute([$table]);
        return (bool)$stmt->fetchColumn();
    }

    private function columnExists(string $table, string $column): bool
    {
        $stmt = $this->db->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ?)");
        $stmt->execute([$table, $column]);
        return (bool)$stmt->fetchColumn();
    }

    private function columnDataType(string $table, string $column): ?string
    {
        $stmt = $this->db->prepare("SELECT data_type FROM information_schema.columns WHERE table_schema = 'public' AND table_name = ? AND column_name = ? LIMIT 1");
        $stmt->execute([$table, $column]);
        $type = $stmt->fetchColumn();
        return $type === false ? null : strtolower((string)$type);
    }

    private function boolLiteral(string $table, string $column, bool $value): string
    {
        return $this->columnDataType($table, $column) === 'boolean'
            ? ($value ? 'TRUE' : 'FALSE')
            : ($value ? '1' : '0');
    }

    private function boolComparison(string $table, string $column, bool $value): string
    {
        return $column . ' = ' . $this->boolLiteral($table, $column, $value);
    }

    private function deleteExpiredMentorshipResourceFiles(array $resources): void
    {
        $projectRoot = realpath(dirname(__DIR__));
        if (!$projectRoot) {
            return;
        }

        $resourcesRoot = realpath($projectRoot . DIRECTORY_SEPARATOR . 'carregamentos' . DIRECTORY_SEPARATOR . 'resources');
        if (!$resourcesRoot) {
            return;
        }

        $resourcesRootWithSlash = rtrim($resourcesRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        foreach ($resources as $resource) {
            if (($resource['resource_type'] ?? '') !== 'file') {
                continue;
            }

            $fileUrl = trim((string)($resource['file_url'] ?? ''));
            if ($fileUrl === '' || preg_match('#^https?://#i', $fileUrl)) {
                continue;
            }

            $normalizedUrl = str_replace('\\', '/', ltrim($fileUrl, "/\\"));
            if (strpos($normalizedUrl, 'carregamentos/resources/') !== 0) {
                continue;
            }

            $candidate = $projectRoot . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $normalizedUrl);
            $realFile = realpath($candidate);
            if ($realFile && strpos($realFile, $resourcesRootWithSlash) === 0 && is_file($realFile)) {
                @unlink($realFile);
            }
        }
    }
}
