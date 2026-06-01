<?php
/**
 * test_mentor_vip_rooms.php
 * Script de teste para funcionalidade de Salas VIP de Mentoria
 */
session_start();
require_once __DIR__ . '/configuracoes/base_dados.php';

$base_url = './';
$errors = [];
$warnings = [];
$success = [];

try {
    $db = (new Database())->getConnection();
    
    // 1. Verificar se as tabelas existem
    echo "<h2>✓ Teste 1: Tabelas de Banco de Dados</h2>";
    
    $tables_to_check = [
        'mentor_chat_groups',
        'mentor_group_members',
        'mentor_group_messages',
        'mentor_group_meetings',
        'mentor_group_resources'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $db->query("SELECT 1 FROM $table LIMIT 1");
            $success[] = "Tabela <code>$table</code> existe ✓";
        } catch (Exception $e) {
            $errors[] = "Tabela <code>$table</code> não existe ou inacessível!";
        }
    }
    
    // 2. Verificar colunas obrigatórias
    echo "<h2>✓ Teste 2: Estrutura de Colunas</h2>";
    
    $columns_check = [
        'mentor_chat_groups' => ['id', 'mentor_id', 'name', 'created_at'],
        'mentor_group_members' => ['id', 'group_id', 'user_id', 'role'],
        'mentor_group_messages' => ['id', 'group_id', 'sender_id', 'message', 'message_type'],
    ];
    
    foreach ($columns_check as $table => $columns) {
        foreach ($columns as $col) {
            try {
                $stmt = $db->query("SELECT $col FROM $table LIMIT 1");
                $success[] = "Coluna <code>$table.$col</code> existe ✓";
            } catch (Exception $e) {
                $errors[] = "Coluna <code>$table.$col</code> não existe!";
            }
        }
    }
    
    // 3. Verificar se os arquivos PHP existem
    echo "<h2>✓ Teste 3: Arquivos da API</h2>";
    
    $files_to_check = [
        'interface_programacao/social/create_mentor_group.php',
        'interface_programacao/social/send_mentor_group_message.php',
        'interface_programacao/social/get_mentor_group_messages.php',
        'interface_programacao/social/add_member_to_group.php',
        'interface_programacao/social/send_mentor_group_resource.php',
        'interface_programacao/social/get_mentor_group_members.php',
    ];
    
    foreach ($files_to_check as $file) {
        if (file_exists(__DIR__ . '/' . $file)) {
            $success[] = "Arquivo <code>$file</code> existe ✓";
        } else {
            $errors[] = "Arquivo <code>$file</code> não encontrado!";
        }
    }
    
    // 4. Teste de permissões de escrita
    echo "<h2>✓ Teste 4: Permissões de Escrita</h2>";
    
    $upload_dir = __DIR__ . '/carregamentos/mentorship_resources/';
    if (!is_dir($upload_dir)) {
        mkdir($upload_dir, 0755, true);
        $success[] = "Diretório de resources criado ✓";
    }
    
    if (is_writable($upload_dir)) {
        $success[] = "Diretório de resources é gravável ✓";
    } else {
        $warnings[] = "Diretório de resources pode ter problemas de escrita";
    }
    
    // 5. Listar algumas estatísticas
    echo "<h2>✓ Teste 5: Estatísticas</h2>";
    
    try {
        $groups_count = $db->query("SELECT COUNT(*) as cnt FROM mentor_chat_groups")->fetch(PDO::FETCH_ASSOC)['cnt'];
        $messages_count = $db->query("SELECT COUNT(*) as cnt FROM mentor_group_messages")->fetch(PDO::FETCH_ASSOC)['cnt'];
        $members_count = $db->query("SELECT COUNT(*) as cnt FROM mentor_group_members")->fetch(PDO::FETCH_ASSOC)['cnt'];
        
        $success[] = "Existem <strong>$groups_count</strong> grupos de mentoria VIP";
        $success[] = "Existem <strong>$messages_count</strong> mensagens de grupo";
        $success[] = "Existem <strong>$members_count</strong> membros de grupos";
    } catch (Exception $e) {
        $warnings[] = "Não foi possível contar dados: " . $e->getMessage();
    }
    
} catch (Exception $e) {
    $errors[] = "Erro de conexão com banco de dados: " . $e->getMessage();
}

// Exibir resultados
echo "<!DOCTYPE html>
<html>
<head>
    <meta charset='UTF-8'>
    <meta name='viewport' content='width=device-width, initial-scale=1.0'>
    <title>Teste - Salas VIP de Mentoria</title>
    <style>
        body { font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif; margin: 2rem; background: #0f172a; color: #fff; }
        .container { max-width: 900px; margin: 0 auto; }
        h1 { color: #f7941d; border-bottom: 2px solid #f7941d; padding-bottom: 1rem; }
        h2 { color: #10b981; margin-top: 2rem; }
        .success { background: rgba(16,185,129,0.1); border-left: 4px solid #10b981; padding: 1rem; margin: 1rem 0; border-radius: 8px; }
        .error { background: rgba(239,68,68,0.1); border-left: 4px solid #ef4444; padding: 1rem; margin: 1rem 0; border-radius: 8px; color: #fca5a5; }
        .warning { background: rgba(251,191,36,0.1); border-left: 4px solid #fbbf24; padding: 1rem; margin: 1rem 0; border-radius: 8px; color: #fcd34d; }
        code { background: rgba(0,0,0,0.3); padding: 2px 6px; border-radius: 4px; }
        .status { margin-top: 2rem; padding: 1.5rem; background: rgba(0,0,0,0.3); border-radius: 12px; }
    </style>
</head>
<body>
    <div class='container'>
        <h1>✓ Teste de Funcionalidade - Salas VIP de Mentoria</h1>
        
        <div class='status'>
            <p><strong>Status Geral:</strong> " . (empty($errors) ? "<span style='color: #10b981;'>✓ TUDO OK</span>" : "<span style='color: #ef4444;'>✗ COM ERROS</span>") . "</p>
        </div>";

if (!empty($success)) {
    echo "<h2>Sucessos (" . count($success) . ")</h2>";
    foreach ($success as $msg) {
        echo "<div class='success'>$msg</div>";
    }
}

if (!empty($warnings)) {
    echo "<h2>Avisos (" . count($warnings) . ")</h2>";
    foreach ($warnings as $msg) {
        echo "<div class='warning'>$msg</div>";
    }
}

if (!empty($errors)) {
    echo "<h2>Erros (" . count($errors) . ")</h2>";
    foreach ($errors as $msg) {
        echo "<div class='error'>$msg</div>";
    }
}

echo "
        <div style='margin-top: 3rem; padding-top: 2rem; border-top: 1px solid rgba(255,255,255,0.1);'>
            <p><small>Se todos os testes passaram, o sistema está pronto para usar!</small></p>
        </div>
    </div>
</body>
</html>";
?>
