-- =================================================================
-- AKSANTI — MIGRAÇÃO v2: Votos de Estudantes + Equity Disponível
-- Execute este script uma única vez na base de dados PostgreSQL
-- =================================================================

-- F2: Tabela de Votos de Estudantes (endosso de peer)
CREATE TABLE IF NOT EXISTS project_votes (
    vote_id     SERIAL PRIMARY KEY,
    project_id  INTEGER NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
    voter_id    INTEGER NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    voted_at    TIMESTAMP DEFAULT NOW(),
    UNIQUE (project_id, voter_id)  -- Máximo 1 voto por utilizador por projecto
);

CREATE INDEX IF NOT EXISTS idx_project_votes_project ON project_votes(project_id);
CREATE INDEX IF NOT EXISTS idx_project_votes_voter   ON project_votes(voter_id);

-- F2.1: Auditoria leve para limitar novos votos por dia sem bloquear a remocao de votos
CREATE TABLE IF NOT EXISTS project_vote_events (
    event_id    SERIAL PRIMARY KEY,
    project_id  INTEGER NOT NULL REFERENCES projects(project_id) ON DELETE CASCADE,
    voter_id    INTEGER NOT NULL REFERENCES users(user_id) ON DELETE CASCADE,
    created_at  TIMESTAMP DEFAULT (CURRENT_TIMESTAMP AT TIME ZONE 'Africa/Luanda')
);

CREATE INDEX IF NOT EXISTS idx_project_vote_events_voter_created ON project_vote_events(voter_id, created_at DESC);
CREATE INDEX IF NOT EXISTS idx_project_vote_events_project ON project_vote_events(project_id);

-- F3: Equity Disponível no Projecto (percentagem máxima que o fundador cede)
ALTER TABLE projects ADD COLUMN IF NOT EXISTS equity_available NUMERIC(5,2) DEFAULT NULL;

-- F3: Equity já comprometida (calculada dinamicamente, mas guardamos para cache)
ALTER TABLE projects ADD COLUMN IF NOT EXISTS equity_committed NUMERIC(5,2) DEFAULT 0;

-- F1: Índice para verificação rápida de propostas por investidor
-- (a tabela project_investments já existe, só criamos o índice se não existir)
CREATE INDEX IF NOT EXISTS idx_investments_investor ON project_investments(investor_id);

-- Confirmar migração
SELECT 'Migração v2 executada com sucesso' AS resultado;
