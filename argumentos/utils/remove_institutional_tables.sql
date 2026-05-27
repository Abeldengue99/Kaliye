-- ============================================
-- REMOÇÃO DE TABELAS INSTITUCIONAIS (FASE 3)
-- ============================================
-- Data: 25 de Janeiro de 2026
-- Motivo: Funcionalidades adiadas para Fase 3
-- Impacto: ZERO - Código já comentado
-- Backup: institutional_tables_backup_*.sql
-- ============================================

-- Desabilitar verificação de chaves estrangeiras temporariamente
SET FOREIGN_KEY_CHECKS = 0;

-- Remover tabelas institucionais
DROP TABLE IF EXISTS `institution_actions`;
DROP TABLE IF EXISTS `institution_invitations`;
DROP TABLE IF EXISTS `institution_opportunities`;
DROP TABLE IF EXISTS `institution_challenges`;
DROP TABLE IF EXISTS `institutions`;

-- Reabilitar verificação de chaves estrangeiras
SET FOREIGN_KEY_CHECKS = 1;

-- ============================================
-- VERIFICAÇÃO PÓS-REMOÇÃO
-- ============================================
-- Execute este comando para confirmar:
-- SELECT COUNT(*) as total_tables FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = 'aksanti_mentorship';
-- Resultado esperado: 50 tabelas (antes: 55)
