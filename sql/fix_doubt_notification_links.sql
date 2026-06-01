-- Script para corrigir links de notificações de dúvidas
-- Altera paginas/social/duvidas.php para paginas/explorar/doubts.php

UPDATE notifications 
SET link = REPLACE(link, 'paginas/social/duvidas.php', 'paginas/explorar/doubts.php') 
WHERE link LIKE '%paginas/social/duvidas.php%';

-- Verificar se a correção foi bem-sucedida
SELECT COUNT(*) as notificacoes_corrigidas
FROM notifications 
WHERE link LIKE '%paginas/explorar/doubts.php%' AND link LIKE '%doubt_id%';

-- Verificar se ainda restam links incorretos
SELECT COUNT(*) as links_restantes_incorretos
FROM notifications 
WHERE link LIKE '%paginas/social/duvidas.php%';
