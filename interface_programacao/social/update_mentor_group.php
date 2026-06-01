<?php
/**
 * update_mentor_group.php
 * Atualiza nome e imagem do grupo de mentoria (apenas o proprietário)
 */
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    
    // AUTO-MIGRATION: Garantir que coluna group_image existe (abordagem robusta com PL/pgSQL)
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
    } catch (Exception $migration_error) {
        // Log pero continua de todos os modos
        error_log("Migration warning in update_mentor_group: " . $migration_error->getMessage());
    }
    
    $user_id = (int)$_SESSION['user_id'];
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $group_name = isset($_POST['group_name']) ? trim($_POST['group_name']) : '';
    
    if ($group_id <= 0) {
        throw new Exception('Grupo não especificado.');
    }
    
    // Verificar se o usuário é o proprietário
    $owner_check = $db->prepare("SELECT mentor_id FROM mentor_chat_groups WHERE id = ?");
    $owner_check->execute([$group_id]);
    $group = $owner_check->fetch(PDO::FETCH_ASSOC);
    
    if (!$group || $group['mentor_id'] != $user_id) {
        throw new Exception('Sem permissão para editar este grupo.');
    }
    
    $updates = [];
    $params = [];
    
    // Atualizar nome se fornecido
    if (!empty($group_name)) {
        $updates[] = "name = ?";
        $params[] = $group_name;
    }
    
    // Atualizar imagem se enviada
    if (isset($_FILES['group_image']) && $_FILES['group_image']['size'] > 0) {
        $file = $_FILES['group_image'];
        
        // Validar tipo de arquivo
        $allowed_types = ['image/jpeg', 'image/png', 'image/gif', 'image/webp'];
        if (!in_array($file['type'], $allowed_types)) {
            throw new Exception('Tipo de arquivo inválido. Use JPG, PNG, GIF ou WebP.');
        }
        
        // Validar tamanho (máx 2MB)
        if ($file['size'] > 2 * 1024 * 1024) {
            throw new Exception('Arquivo muito grande. Máximo 2MB.');
        }
        
        // Criar pasta se não existir
        $upload_dir = __DIR__ . '/../../carregamentos/group_images/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Gerar nome único
        $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
        $filename = 'group_' . $group_id . '_' . time() . '.' . $ext;
        $filepath = $upload_dir . $filename;
        
        // Fazer upload
        if (!move_uploaded_file($file['tmp_name'], $filepath)) {
            throw new Exception('Erro ao fazer upload da imagem.');
        }
        
        // Atualizar banco com caminho relativo
        $image_url = '/carregamentos/group_images/' . $filename;
        $updates[] = "group_image = ?";
        $params[] = $image_url;
    }
    
    if (empty($updates)) {
        throw new Exception('Nada para atualizar.');
    }
    
    // Adicionar group_id aos parâmetros
    $params[] = $group_id;
    
    // Executar atualização
    $query = "UPDATE mentor_chat_groups SET " . implode(", ", $updates) . " WHERE id = ?";
    $stmt = $db->prepare($query);
    $stmt->execute($params);
    
    echo json_encode([
        'success' => true,
        'message' => 'Grupo atualizado com sucesso!'
    ]);
    
} catch (Exception $e) {
    $error_msg = $e->getMessage();
    
    // Traduzir erros técnicos para mensagens amigáveis
    if (strpos($error_msg, 'group_image') !== false || strpos($error_msg, 'coluna') !== false) {
        $error_msg = '⚠️ Sistema está a sincronizar. Tente novamente em alguns segundos.';
    } elseif (strpos($error_msg, 'Sem permissão') !== false) {
        $error_msg = '🔒 Apenas o criador da sala pode fazer alterações.';
    } elseif (strpos($error_msg, 'Nada para atualizar') !== false) {
        $error_msg = 'ℹ️ Selecione um nome ou imagem para atualizar.';
    } elseif (strpos($error_msg, 'Arquivo muito grande') !== false) {
        $error_msg = '📦 Imagem muito grande. Máximo 2MB.';
    } elseif (strpos($error_msg, 'Tipo de arquivo') !== false) {
        $error_msg = '🖼️ Apenas JPG, PNG, GIF ou WebP são aceitos.';
    }
    
    error_log("update_mentor_group error: " . $e->getMessage());
    
    echo json_encode([
        'success' => false, 
        'error' => $error_msg
    ]);
}

?>
