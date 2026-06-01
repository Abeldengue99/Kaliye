<?php
/**
 * test_group_image_migration.php
 * Script de teste/forçar para criar a coluna group_image
 * Acesso: http://localhost/kaliye/test_group_image_migration.php
 */

require_once 'configuracoes/base_dados.php';

header('Content-Type: text/html; charset=utf-8');
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <title>Teste de Migração - group_image</title>
    <style>
        body { font-family: Arial; margin: 20px; background: #0d1117; color: #c9d1d9; }
        .box { border: 1px solid #30363d; padding: 15px; margin: 10px 0; border-radius: 6px; }
        .success { background: #0d3922; border-color: #1a6e3f; }
        .error { background: #3d1f1a; border-color: #6e423f; }
        .info { background: #0d2d4a; border-color: #1a4d6e; }
        code { background: #161b22; padding: 2px 6px; border-radius: 3px; font-family: monospace; }
    </style>
</head>
<body>
    <h1>🔧 Teste de Migração - Coluna group_image</h1>
    
    <?php
    try {
        $db = (new Database())->getConnection();
        echo '<div class="box info">✅ Conexão à BD estabelecida</div>';
        
        // Verificar se tabela existe
        $table_check = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.tables 
            WHERE table_name = 'mentor_chat_groups'
        )")->fetchColumn();
        
        if ($table_check) {
            echo '<div class="box info">✅ Tabela <code>mentor_chat_groups</code> existe</div>';
        } else {
            echo '<div class="box error">❌ Tabela <code>mentor_chat_groups</code> NÃO existe!</div>';
            exit;
        }
        
        // Verificar se coluna já existe
        $column_check = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'mentor_chat_groups' 
            AND column_name = 'group_image'
        )")->fetchColumn();
        
        if ($column_check) {
            echo '<div class="box success">✅ Coluna <code>group_image</code> JÁ EXISTS</div>';
        } else {
            echo '<div class="box error">❌ Coluna <code>group_image</code> NÃO existe. Criando...</div>';
            
            // Tentar criar com DO statement (robusto)
            try {
                $db->exec("
                    DO $$
                    BEGIN
                        IF NOT EXISTS (
                            SELECT 1 FROM information_schema.columns 
                            WHERE table_name = 'mentor_chat_groups' 
                            AND column_name = 'group_image'
                        ) THEN
                            ALTER TABLE mentor_chat_groups ADD COLUMN group_image VARCHAR(500);
                        END IF;
                    END $$;
                ");
                echo '<div class="box success">✅ Coluna criada com sucesso (DO statement)!</div>';
            } catch (Exception $e1) {
                echo '<div class="box error">❌ DO statement falhou: ' . $e1->getMessage() . '</div>';
                echo '<div class="box info">Tentando método alternativo (ALTER simples)...</div>';
                
                // Tentar método simples (pode falhar se coluna existe)
                try {
                    $db->exec("ALTER TABLE mentor_chat_groups ADD COLUMN group_image VARCHAR(500)");
                    echo '<div class="box success">✅ Coluna criada com ALTER TABLE!</div>';
                } catch (Exception $e2) {
                    echo '<div class="box error">❌ ALTER TABLE falhou: ' . $e2->getMessage() . '</div>';
                }
            }
        }
        
        // Verificação final
        $final_check = $db->query("SELECT EXISTS (
            SELECT 1 FROM information_schema.columns 
            WHERE table_name = 'mentor_chat_groups' 
            AND column_name = 'group_image'
        )")->fetchColumn();
        
        if ($final_check) {
            echo '<div class="box success">✅ SUCESSO! Coluna <code>group_image</code> está pronta para usar.</div>';
        } else {
            echo '<div class="box error">❌ FALHA! Coluna ainda não existe.</div>';
        }
        
        // Listar todas as colunas
        echo '<div class="box info"><strong>Colunas atuais:</strong><br>';
        $columns = $db->query("
            SELECT column_name FROM information_schema.columns 
            WHERE table_name = 'mentor_chat_groups' 
            ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_COLUMN);
        echo implode('<br>', $columns);
        echo '</div>';
        
    } catch (Exception $e) {
        echo '<div class="box error">❌ Erro: ' . $e->getMessage() . '</div>';
    }
    ?>
    
    <div class="box info">
        <strong>O que fazer a seguir:</strong><br>
        1. Se vir "✅ SUCESSO", recarregue a página de mensagens<br>
        2. Tente novamente trocar a imagem do grupo<br>
        3. Se ainda tiver erro, copie o erro e mostre-me
    </div>
</body>
</html>
