SELECT 
    table_name,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name) as cols,
    pg_total_relation_size(table_name::regclass)::bigint as size,
    (SELECT COUNT(*) FROM information_schema.columns WHERE table_name = t.table_name AND column_name = 'id') as has_id
FROM information_schema.tables t
WHERE table_schema = 'public'
ORDER BY table_name;
