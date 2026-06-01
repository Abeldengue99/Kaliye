-- =====================================================
-- FIX: Criar coluna group_image em mentor_chat_groups
-- =====================================================
-- Execute este script no pgAdmin ou via psql:
-- psql -h localhost -U seu_usuario -d sua_bd -f fix_group_image.sql

DO $$
BEGIN
    -- Verificar se coluna já existe
    IF NOT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'mentor_chat_groups' 
        AND column_name = 'group_image'
    ) THEN
        -- Coluna não existe, criar
        ALTER TABLE mentor_chat_groups ADD COLUMN group_image VARCHAR(500);
        RAISE NOTICE 'Coluna group_image criada com sucesso!';
    ELSE
        RAISE NOTICE 'Coluna group_image já existe. Nada a fazer.';
    END IF;
END $$;

-- Verificar resultado
SELECT column_name FROM information_schema.columns 
WHERE table_name = 'mentor_chat_groups' 
ORDER BY ordinal_position;
