<?php
/**
 * Helpers idempotentes para os fluxos administrados pela KALIYE.
 */

function ensureProjectMentorshipApplicationsSchema(PDO $db): void {
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
        return;
    }

    $db->exec("CREATE TABLE IF NOT EXISTS project_mentorship_applications (
        application_id SERIAL PRIMARY KEY,
        project_id INTEGER NOT NULL,
        mentor_id INTEGER NOT NULL,
        status VARCHAR(30) DEFAULT 'submitted',
        motivation TEXT,
        relevant_experience TEXT,
        proposed_support TEXT,
        availability TEXT,
        admin_response TEXT,
        reviewed_by INTEGER NULL,
        reviewed_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        UNIQUE(project_id, mentor_id)
    )");

    $columns = [
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS motivation TEXT",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS relevant_experience TEXT",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS proposed_support TEXT",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS availability TEXT",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS admin_response TEXT",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS reviewed_by INTEGER NULL",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL",
        "ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($columns as $sql) {
        $db->exec($sql);
    }

    $db->exec("CREATE INDEX IF NOT EXISTS idx_pma_status ON project_mentorship_applications(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_pma_project ON project_mentorship_applications(project_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_pma_mentor ON project_mentorship_applications(mentor_id)");
}

function ensureInvestmentApplicationsSchema(PDO $db): void {
    if ($db->getAttribute(PDO::ATTR_DRIVER_NAME) !== 'pgsql') {
        return;
    }

    $columns = [
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT 'AOA'",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investment_type VARCHAR(30) DEFAULT 'equity'",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS equity_percentage NUMERIC(5,2) NULL",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS expected_return_rate NUMERIC(7,2) NULL",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS maturity_date DATE NULL",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS terms TEXT",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investment_reason TEXT",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS strategic_value TEXT",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS due_diligence_notes TEXT",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS admin_response TEXT",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS proof_document_path VARCHAR(255)",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS reviewed_by INTEGER NULL",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(80)",
        "ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP"
    ];

    foreach ($columns as $sql) {
        $db->exec($sql);
    }

    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_investments_status ON project_investments(status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_investments_project ON project_investments(project_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_project_investments_investor ON project_investments(investor_id)");
}

function notifyAdmins(PDO $db, int $senderId, string $title, string $content, string $type = 'system', string $link = ''): void {
    $admins = $db->query("SELECT user_id FROM users WHERE user_type IN ('admin', 'superadmin')")->fetchAll(PDO::FETCH_COLUMN);
    if (!$admins) {
        return;
    }

    $stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at)
        VALUES (?, ?, ?, ?, ?, ?, CURRENT_TIMESTAMP)");

    foreach ($admins as $adminId) {
        $stmt->execute([(int)$adminId, $senderId ?: null, $title, $content, $type, $link]);
    }
}
?>
