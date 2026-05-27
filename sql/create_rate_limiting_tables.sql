-- ============================================================
-- AKSANTI REFERÊNCIAS — Rate Limiting Tables
-- Sistema de Penalidades Progressivas
-- 
-- Execute este script uma única vez na base de dados PostgreSQL.
-- ============================================================

-- ------------------------------------------------------------
-- TABELA 1: rate_limit_attempts
-- Regista cada tentativa individual por action_key.
-- Limpos automaticamente pelo RateLimiter após expiração.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limit_attempts (
    id          BIGSERIAL PRIMARY KEY,
    action_key  VARCHAR(255) NOT NULL,   -- ex: "login:192.168.1.1"
    ip_address  INET,
    created_at  TIMESTAMPTZ DEFAULT NOW()
);

-- Índice composto para consultas ultra-rápidas por chave + tempo
CREATE INDEX IF NOT EXISTS idx_rla_key_time 
    ON rate_limit_attempts(action_key, created_at DESC);

-- Índice para limpeza eficiente de registos antigos
CREATE INDEX IF NOT EXISTS idx_rla_created 
    ON rate_limit_attempts(created_at);


-- ------------------------------------------------------------
-- TABELA 2: rate_limit_blocks
-- Regista bloqueios ativos (soft = temporário, hard = permanente).
-- Um registo por action_key — único por utilizador/IP/ação.
-- ------------------------------------------------------------
CREATE TABLE IF NOT EXISTS rate_limit_blocks (
    id              BIGSERIAL PRIMARY KEY,
    action_key      VARCHAR(255) NOT NULL UNIQUE,  -- chave única
    block_type      VARCHAR(10) NOT NULL DEFAULT 'soft', -- 'soft' | 'hard'
    attempt_count   INT DEFAULT 0,                 -- total de violações acumuladas
    blocked_at      TIMESTAMPTZ DEFAULT NOW(),
    unblock_at      TIMESTAMPTZ,                   -- NULL = bloqueio permanente (hard)
    reason          TEXT,                          -- motivo legível para auditoria
    -- Campos de desbloqueio por admin
    unblocked_by    INT REFERENCES users(user_id) ON DELETE SET NULL,
    unblocked_at    TIMESTAMPTZ,
    admin_note      TEXT
);

-- Índice para verificação rápida de bloqueios ativos
CREATE INDEX IF NOT EXISTS idx_rlb_action_key 
    ON rate_limit_blocks(action_key);

CREATE INDEX IF NOT EXISTS idx_rlb_block_type 
    ON rate_limit_blocks(block_type, unblock_at);


-- ------------------------------------------------------------
-- COMENTÁRIO SOBRE A ESTRATÉGIA DE PENALIDADES PROGRESSIVAS:
--
-- FASE 1 (Aviso):    3 tentativas em 5 min   → mensagem de aviso
-- FASE 2 (Soft Lock): 5 tentativas em 15 min → bloqueio de 15 min
-- FASE 3 (Hard Lock): 10 tentativas em 1 hora → bloqueio até contacto
--
-- Para SAQUES (financeiro):
-- FASE 2: 3 pedidos / hora → soft lock 2h
-- FASE 3: 5 pedidos / 24h  → hard lock (contactar equipa)
-- ------------------------------------------------------------
