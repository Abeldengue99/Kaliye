-- ============================================================================
-- KALIYE Database Schema - PostgreSQL
-- Cria todas as tabelas base necessárias para o funcionamento da plataforma
-- ============================================================================

-- 1. TABELA BASE: USERS (Utilizadores)
-- ============================================================================
CREATE TABLE IF NOT EXISTS users (
    user_id SERIAL PRIMARY KEY,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    full_name VARCHAR(255) NOT NULL,
    user_type VARCHAR(50) NOT NULL,
    profile_pic VARCHAR(255),
    bio TEXT,
    phone VARCHAR(20),
    location VARCHAR(100),
    verification_status VARCHAR(50) DEFAULT 'pending',
    is_verified BOOLEAN DEFAULT FALSE,
    mentorship_status VARCHAR(50),
    is_peer_mentor BOOLEAN DEFAULT FALSE,
    wallet_balance NUMERIC(15,2) DEFAULT 0,
    bank_iban VARCHAR(50),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    last_login TIMESTAMP
);

CREATE INDEX idx_users_email ON users(email);
CREATE INDEX idx_users_type ON users(user_type);
CREATE INDEX idx_users_verification ON users(verification_status);

-- 2. TABELA: INSTITUTIONS (Instituições)
-- ============================================================================
CREATE TABLE IF NOT EXISTS institutions (
    institution_id SERIAL PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    type VARCHAR(50) DEFAULT 'University',
    code VARCHAR(50) UNIQUE,
    logo_url VARCHAR(255),
    location VARCHAR(255),
    contact_email VARCHAR(255),
    website VARCHAR(255),
    is_active BOOLEAN DEFAULT TRUE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
);

-- 3. TABELA: USER_PROFILES (Perfis Estendidos)
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_profiles (
    profile_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL UNIQUE,
    specialization_tags TEXT,
    focus_areas TEXT,
    experience_summary TEXT,
    skills TEXT,
    portfolio_url VARCHAR(255),
    linkedin_url VARCHAR(255),
    github_url VARCHAR(255),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_user_profiles_user_id ON user_profiles(user_id);

-- 4. TABELA: OTP_CODES (Códigos de Verificação)
-- ============================================================================
CREATE TABLE IF NOT EXISTS otp_codes (
    code_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    code_hash VARCHAR(255) NOT NULL,
    purpose VARCHAR(50) NOT NULL,
    expires_at TIMESTAMP NOT NULL,
    used_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_otp_codes_user ON otp_codes(user_id);
CREATE INDEX idx_otp_codes_expires ON otp_codes(expires_at);

-- 5. TABELA: PROJECTS (Projetos)
-- ============================================================================
CREATE TABLE IF NOT EXISTS projects (
    project_id SERIAL PRIMARY KEY,
    owner_id INTEGER NOT NULL,
    title VARCHAR(255) NOT NULL,
    description TEXT,
    category VARCHAR(100),
    status VARCHAR(50) DEFAULT 'published',
    budget NUMERIC(15,2),
    currency VARCHAR(10) DEFAULT 'AOA',
    problem TEXT,
    solution TEXT,
    business_model TEXT,
    technical_details TEXT,
    needs TEXT,
    image_url VARCHAR(255),
    is_featured BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (owner_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_projects_owner ON projects(owner_id);
CREATE INDEX idx_projects_status ON projects(status);
CREATE INDEX idx_projects_category ON projects(category);

-- 6. TABELA: PROJECT_APPLICATIONS (Candidaturas)
-- ============================================================================
CREATE TABLE IF NOT EXISTS project_applications (
    application_id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL,
    applicant_id INTEGER NOT NULL,
    status VARCHAR(50) DEFAULT 'submitted',
    motivation TEXT,
    relevant_experience TEXT,
    proposed_support TEXT,
    availability TEXT,
    admin_response TEXT,
    reviewed_by INTEGER,
    reviewed_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(project_id, applicant_id),
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (applicant_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewed_by) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE INDEX idx_applications_project ON project_applications(project_id);
CREATE INDEX idx_applications_applicant ON project_applications(applicant_id);
CREATE INDEX idx_applications_status ON project_applications(status);

-- 7. TABELA: MENTORSHIP_CONTRACTS (Contratos de Mentoria)
-- ============================================================================
CREATE TABLE IF NOT EXISTS mentorship_contracts (
    contract_id SERIAL PRIMARY KEY,
    mentor_id INTEGER NOT NULL,
    student_id INTEGER NOT NULL,
    project_id INTEGER,
    status VARCHAR(50) DEFAULT 'active',
    start_date DATE,
    end_date DATE,
    terms TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (student_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE SET NULL
);

CREATE INDEX idx_contracts_mentor ON mentorship_contracts(mentor_id);
CREATE INDEX idx_contracts_student ON mentorship_contracts(student_id);
CREATE INDEX idx_contracts_status ON mentorship_contracts(status);

-- 8. TABELA: MENTOR_CHAT_GROUPS (Salas VIP de Mentoria)
-- ============================================================================
CREATE TABLE IF NOT EXISTS mentor_chat_groups (
    id SERIAL PRIMARY KEY,
    mentor_id INTEGER NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_mentor_groups_mentor ON mentor_chat_groups(mentor_id);

-- 9. TABELA: MENTOR_GROUP_MEMBERS (Membros de Grupos VIP)
-- ============================================================================
CREATE TABLE IF NOT EXISTS mentor_group_members (
    id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL,
    user_id INTEGER NOT NULL,
    joined_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(group_id, user_id),
    FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_group_members_group ON mentor_group_members(group_id);
CREATE INDEX idx_group_members_user ON mentor_group_members(user_id);

-- 10. TABELA: MENTOR_GROUP_MESSAGES (Mensagens de Grupos)
-- ============================================================================
CREATE TABLE IF NOT EXISTS mentor_group_messages (
    id SERIAL PRIMARY KEY,
    group_id INTEGER NOT NULL,
    sender_id INTEGER NOT NULL,
    content TEXT,
    message_type VARCHAR(50) DEFAULT 'text',
    file_url VARCHAR(255),
    status VARCHAR(50) DEFAULT 'sent',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (group_id) REFERENCES mentor_chat_groups(id) ON DELETE CASCADE,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_group_messages_group ON mentor_group_messages(group_id);
CREATE INDEX idx_group_messages_sender ON mentor_group_messages(sender_id);
CREATE INDEX idx_group_messages_created ON mentor_group_messages(created_at);

-- 11. TABELA: NOTIFICATIONS (Notificações)
-- ============================================================================
CREATE TABLE IF NOT EXISTS notifications (
    notification_id SERIAL PRIMARY KEY,
    user_id INTEGER NOT NULL,
    type VARCHAR(100),
    title VARCHAR(255),
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    archived_at TIMESTAMP,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_notifications_user ON notifications(user_id);
CREATE INDEX idx_notifications_read ON notifications(is_read);

-- 12. TABELA: AUDIT_LOGS (Logs de Auditoria)
-- ============================================================================
CREATE TABLE IF NOT EXISTS audit_logs (
    id SERIAL PRIMARY KEY,
    admin_id INTEGER,
    action VARCHAR(255),
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE INDEX idx_audit_logs_admin ON audit_logs(admin_id);
CREATE INDEX idx_audit_logs_created ON audit_logs(created_at);

-- 13. TABELA: USER_CONNECTIONS (Conexões entre Utilizadores)
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_connections (
    connection_id SERIAL PRIMARY KEY,
    user_1 INTEGER NOT NULL,
    user_2 INTEGER NOT NULL,
    status VARCHAR(50) DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(user_1, user_2),
    FOREIGN KEY (user_1) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (user_2) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_connections_user1 ON user_connections(user_1);
CREATE INDEX idx_connections_user2 ON user_connections(user_2);

-- 14. TABELA: DIRECT_MESSAGES (Mensagens Diretas)
-- ============================================================================
CREATE TABLE IF NOT EXISTS direct_messages (
    message_id SERIAL PRIMARY KEY,
    sender_id INTEGER NOT NULL,
    receiver_id INTEGER NOT NULL,
    content TEXT,
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (sender_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_direct_messages_sender ON direct_messages(sender_id);
CREATE INDEX idx_direct_messages_receiver ON direct_messages(receiver_id);

-- 15. TABELA: USER_REVIEWS (Avaliações de Utilizadores)
-- ============================================================================
CREATE TABLE IF NOT EXISTS user_reviews (
    review_id SERIAL PRIMARY KEY,
    mentor_id INTEGER NOT NULL,
    reviewer_id INTEGER NOT NULL,
    rating INTEGER CHECK (rating >= 1 AND rating <= 5),
    comment TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
    FOREIGN KEY (reviewer_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_reviews_mentor ON user_reviews(mentor_id);
CREATE INDEX idx_reviews_reviewer ON user_reviews(reviewer_id);

-- 16. TABELA: PROJECT_INVESTMENTS (Investimentos)
-- ============================================================================
CREATE TABLE IF NOT EXISTS project_investments (
    investment_id SERIAL PRIMARY KEY,
    project_id INTEGER NOT NULL,
    investor_id INTEGER NOT NULL,
    amount NUMERIC(15,2),
    currency VARCHAR(10) DEFAULT 'AOA',
    investment_type VARCHAR(50) DEFAULT 'equity',
    equity_percentage NUMERIC(5,2),
    status VARCHAR(50) DEFAULT 'pending',
    terms TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (project_id) REFERENCES projects(project_id) ON DELETE CASCADE,
    FOREIGN KEY (investor_id) REFERENCES users(user_id) ON DELETE CASCADE
);

CREATE INDEX idx_investments_project ON project_investments(project_id);
CREATE INDEX idx_investments_investor ON project_investments(investor_id);

-- 17. TABELA: SUPPORT_MESSAGES (Mensagens de Suporte)
-- ============================================================================
CREATE TABLE IF NOT EXISTS support_messages (
    id SERIAL PRIMARY KEY,
    user_id INTEGER,
    subject VARCHAR(255),
    content TEXT,
    status VARCHAR(50) DEFAULT 'open',
    is_read BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE SET NULL
);

CREATE INDEX idx_support_messages_user ON support_messages(user_id);
CREATE INDEX idx_support_messages_status ON support_messages(status);

-- ============================================================================
-- Fim do Script de Criação de Tabelas PostgreSQL
-- Data: 1 de junho de 2026
-- ============================================================================

\echo 'Database schema created successfully!'
