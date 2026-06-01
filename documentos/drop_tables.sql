-- FASE 3: ELIMINAÇÃO SEGURA
-- Data: 1 de junho de 2026

-- Antes: Contar tabelas
SELECT COUNT(*) as "Total de Tabelas" FROM information_schema.tables WHERE table_schema = 'public';

-- Eliminar tabelas vazias (zero risco)
DROP TABLE IF EXISTS test_table_1 CASCADE;
DROP TABLE IF EXISTS test_table_2 CASCADE;

-- Eliminar tabelas órfãs
DROP TABLE IF EXISTS legacy_comments CASCADE;
DROP TABLE IF EXISTS user_activity_logs CASCADE;

-- Depois: Contar tabelas
SELECT COUNT(*) as "Total de Tabelas" FROM information_schema.tables WHERE table_schema = 'public';
