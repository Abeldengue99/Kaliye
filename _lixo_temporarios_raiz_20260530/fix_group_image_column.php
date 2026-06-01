<?php
/**
 * fix_group_image_column.php
 * Script para FORÇAR a criação da coluna group_image
 * Execute via: php fix_group_image_column.php
 */

require_once 'configuracoes/base_dados.php';

echo "🔧 Iniciando Fix de group_image...\n";
echo "================================\n\n";

try {
    $db = (new Database())->getConnection();
    echo "✅ Conexão à BD OK\n";
    
    // Verificar se tabela existe
    $table_check = $db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.tables 
        WHERE table_name = 'mentor_chat_groups'
    )")->fetchColumn();
    
    if (!$table_check) {
        die("❌ ERRO: Tabela mentor_chat_groups não existe!\n");
    }
    echo "✅ Tabela mentor_chat_groups existe\n";
    
    // Verificar se coluna existe
    $column_check = $db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'mentor_chat_groups' 
        AND column_name = 'group_image'
    )")->fetchColumn();
    
    if ($column_check) {
        echo "✅ Coluna group_image JÁ EXISTS\n";
        echo "\n✅ TUDO OK! Nada a fazer.\n";
        exit(0);
    }
    
    echo "❌ Coluna group_image NÃO existe\n";
    echo "\nTentando criar...\n";
    
    // Método 1: DO statement (PL/pgSQL)
    try {
        echo "  → Método 1 (DO statement)...";
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
        echo " OK\n";
    } catch (Exception $e1) {
        echo " FALHOU\n";
        echo "    Erro: " . $e1->getMessage() . "\n";
        
        // Método 2: ALTER simples
        try {
            echo "  → Método 2 (ALTER simples)...";
            $db->exec("ALTER TABLE mentor_chat_groups ADD COLUMN group_image VARCHAR(500)");
            echo " OK\n";
        } catch (Exception $e2) {
            echo " FALHOU\n";
            echo "    Erro: " . $e2->getMessage() . "\n";
            throw new Exception("Nenhum método funcionou!");
        }
    }
    
    // Verificação final
    $final_check = $db->query("SELECT EXISTS (
        SELECT 1 FROM information_schema.columns 
        WHERE table_name = 'mentor_chat_groups' 
        AND column_name = 'group_image'
    )")->fetchColumn();
    
    if ($final_check) {
        echo "\n✅ SUCESSO! Coluna group_image foi criada.\n";
        
        // Listar todas as colunas
        echo "\nColunas atuais:\n";
        $columns = $db->query("
            SELECT column_name FROM information_schema.columns 
            WHERE table_name = 'mentor_chat_groups' 
            ORDER BY ordinal_position
        ")->fetchAll(PDO::FETCH_COLUMN);
        foreach ($columns as $col) {
            echo "  • $col\n";
        }
        
        echo "\n✅ Pronto! Agora pode trocar a imagem do grupo sem erros.\n";
        exit(0);
    } else {
        die("\n❌ FALHA! Coluna ainda não existe.\n");
    }
    
} catch (Exception $e) {
    die("\n❌ ERRO: " . $e->getMessage() . "\n");
}
?>
