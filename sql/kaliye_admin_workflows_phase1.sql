-- KALIYE phase 1: mentor and investor applications managed by admin.
-- Safe to run more than once on PostgreSQL.

CREATE TABLE IF NOT EXISTS project_mentorship_applications (
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
);

ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS motivation TEXT;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS relevant_experience TEXT;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS proposed_support TEXT;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS availability TEXT;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS admin_response TEXT;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS reviewed_by INTEGER NULL;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL;
ALTER TABLE project_mentorship_applications ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_pma_status ON project_mentorship_applications(status);
CREATE INDEX IF NOT EXISTS idx_pma_project ON project_mentorship_applications(project_id);
CREATE INDEX IF NOT EXISTS idx_pma_mentor ON project_mentorship_applications(mentor_id);

ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS currency VARCHAR(10) DEFAULT 'AOA';
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investment_type VARCHAR(30) DEFAULT 'equity';
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS equity_percentage NUMERIC(5,2) NULL;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS expected_return_rate NUMERIC(7,2) NULL;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS maturity_date DATE NULL;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS terms TEXT;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS investment_reason TEXT;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS strategic_value TEXT;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS due_diligence_notes TEXT;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS admin_response TEXT;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS proof_document_path VARCHAR(255);
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS reviewed_by INTEGER NULL;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS reviewed_at TIMESTAMP NULL;
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS payment_reference VARCHAR(80);
ALTER TABLE project_investments ADD COLUMN IF NOT EXISTS updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;

CREATE INDEX IF NOT EXISTS idx_project_investments_status ON project_investments(status);
CREATE INDEX IF NOT EXISTS idx_project_investments_project ON project_investments(project_id);
CREATE INDEX IF NOT EXISTS idx_project_investments_investor ON project_investments(investor_id);
