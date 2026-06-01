<?php
/**
 * AVISO CRÍTICO
 * 
 * Este sistema usa EXCLUSIVAMENTE PostgreSQL
 * Não use MySQL ou outro motor de base de dados
 * 
 * Alguns ficheiros antigos podem conter sintaxe MySQL (AUTO_INCREMENT, TINYINT)
 * Estes ficheiros foram DESCONTINUADOS e não devem ser usados
 */

echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <title>⚠️ AVISO - PostgreSQL Only</title>
    <style>
        body {
            background: #1e293b;
            color: #e2e8f0;
            font-family: Arial, sans-serif;
            padding: 40px;
        }
        .warning {
            background: #ef4444;
            border: 2px solid #dc2626;
            border-radius: 8px;
            padding: 30px;
            max-width: 800px;
            margin: 0 auto;
        }
        h1 { color: #fff; margin-top: 0; }
        .note { background: rgba(255,255,255,0.1); padding: 15px; border-radius: 5px; margin: 20px 0; }
        .files { background: rgba(0,0,0,0.2); padding: 15px; border-radius: 5px; margin: 20px 0; font-family: monospace; }
        .postgresql { color: #10b981; font-weight: bold; }
        .mysql { color: #ef4444; font-weight: bold; text-decoration: line-through; }
    </style>
</head>
<body>
    <div class='warning'>
        <h1>⚠️ AVISO CRÍTICO - BASE DE DADOS</h1>
        
        <div class='note'>
            <strong>Este sistema usa EXCLUSIVAMENTE PostgreSQL</strong><br>
            Não use MySQL, SQLite ou outro motor de base de dados
        </div>
        
        <h2>✅ Base de Dados Oficial:</h2>
        <p class='postgresql'>PostgreSQL 18 (em 127.0.0.1:5432)</p>
        
        <h2>❌ Ficheiros com Sintaxe MySQL Descontinuados:</h2>
        <div class='files'>
            argumentos/restore_database.php<br>
            argumentos/final_db_sync.php<br>
            argumentos/migration_institution_features.php<br>
            argumentos/create_social_tables.php<br>
            argumentos/add_commission_system.php<br>
        </div>
        
        <p><strong>Estes ficheiros não devem ser usados.</strong> Use apenas:</p>
        <div class='files'>
            sql/init_database_postgresql.sql<br>
            argumentos/init_database.php<br>
        </div>
        
        <h2>📋 Próximos Passos:</h2>
        <ol>
            <li>Use <a href='init_database.php' style='color: #60a5fa;'>init_database.php</a> para criar tabelas</li>
            <li>Nunca execute ficheiros com 'mysql' no nome</li>
            <li>Sempre use PostgreSQL (pgsql:// no DSN)</li>
            <li>Consulte <a href='../sql/init_database_postgresql.sql' style='color: #60a5fa;'>init_database_postgresql.sql</a> para o schema</li>
        </ol>
    </div>
</body>
</html>";
?>
