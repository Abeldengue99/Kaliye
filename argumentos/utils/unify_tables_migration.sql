-- ============================================
-- UNIFICAÇÃO DE TABELAS (NOTIFICAÇÕES E SOCIAL)
-- ============================================

-- 1. Migrar Notificações de Investidores
-- Mapeamento: investor_id -> user_id, project_id -> reference_id
INSERT IGNORE INTO notifications (user_id, title, content, type, link, is_read, created_at, reference_id)
SELECT 
    investor_id, 
    'Novo Projeto Publicado', 
    'Um novo projeto de seu interesse foi aprovado e está disponível para investimento.', 
    'investment', 
    CONCAT('pages/projects.php?id=', project_id), 
    is_read, 
    created_at, 
    project_id 
FROM investor_notifications;

-- 2. Migrar Likes Sociais para Project Likes
INSERT IGNORE INTO project_likes (project_id, user_id, created_at)
SELECT project_id, user_id, created_at FROM social_likes;

-- 3. Migrar Comentários Sociais para Project Comments
INSERT IGNORE INTO project_comments (project_id, user_id, content, created_at)
SELECT project_id, user_id, content, created_at FROM social_comments;

-- 4. Remover Tabelas Antigas (Serão apagadas após confirmação do código PHP)
-- DROP TABLE IF EXISTS investor_notifications;
-- DROP TABLE IF EXISTS social_likes;
-- DROP TABLE IF EXISTS social_comments;
