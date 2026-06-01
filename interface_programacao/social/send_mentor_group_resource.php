<?php
/**
 * send_mentor_group_resource.php
 * Envia materiais/recursos para a sala VIP
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/ChatSecurity.php';
require_once '../../inclusoes/Security.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit();
}

try {
    $db = (new Database())->getConnection();
    $mentor_id = (int)$_SESSION['user_id'];
    
    $group_id = isset($_POST['group_id']) ? (int)$_POST['group_id'] : 0;
    $title = ChatSecurity::normalizeText($_POST['title'] ?? '');
    $description = ChatSecurity::normalizeText($_POST['description'] ?? '');
    
    if ($group_id <= 0) {
        throw new Exception('Grupo não especificado.');
    }
    
    if (empty($title)) {
        throw new Exception('Título do material é obrigatório.');
    }
    
    // Verificar permissão
    $group_stmt = $db->prepare("SELECT mentor_id FROM mentor_chat_groups WHERE id = ? LIMIT 1");
    $group_stmt->execute([$group_id]);
    $group = $group_stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$group || $group['mentor_id'] != $mentor_id) {
        throw new Exception('Sem permissão para adicionar recursos a este grupo.');
    }
    
    // Processar arquivo se enviado
    $file_path = null;
    $file_type = null;
    $file_size = 0;
    
    if (!empty($_FILES['resource_file'])) {
        $file = $_FILES['resource_file'];
        
        if ($file['error'] !== UPLOAD_ERR_OK) {
            throw new Exception('Erro ao fazer upload do arquivo.');
        }
        
        // Validar tamanho (máx 50MB)
        if ($file['size'] > 50 * 1024 * 1024) {
            throw new Exception('Arquivo muito grande. Máximo: 50MB');
        }
        
        // Tipos permitidos
        $allowed_types = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'ppt', 'pptx', 'txt', 'zip', 'rar', 'jpg', 'jpeg', 'png', 'gif', 'mp4', 'mp3', 'webm'];
        $file_ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        
        if (!in_array($file_ext, $allowed_types)) {
            throw new Exception('Tipo de arquivo não permitido.');
        }
        
        // Diretório de upload
        $upload_dir = __DIR__ . '/../../carregamentos/mentorship_resources/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        // Nome seguro
        $safe_filename = 'res_' . $group_id . '_' . time() . '_' . Security::generateSecureToken(8) . '.' . $file_ext;
        $file_path = 'carregamentos/mentorship_resources/' . $safe_filename;
        $full_path = $upload_dir . $safe_filename;
        
        if (!move_uploaded_file($file['tmp_name'], $full_path)) {
            throw new Exception('Falha ao salvar o arquivo.');
        }
        
        $file_type = $file_ext;
        $file_size = filesize($full_path);
    }
    
    // Inserir recurso na BD
    $insert_stmt = $db->prepare("INSERT INTO mentor_group_resources (group_id, mentor_id, title, description, file_path, file_type, file_size) 
                                VALUES (?, ?, ?, ?, ?, ?, ?)");
    $insert_stmt->execute([$group_id, $mentor_id, $title, $description, $file_path, $file_type, $file_size]);
    
    $resource_id = $db->lastInsertId();
    
    // Criar mensagem de notificação no chat
    $msg_content = json_encode([
        'title' => $title,
        'type' => 'resource',
        'file' => $file_path,
        'file_type' => $file_type
    ]);
    
    $msg_stmt = $db->prepare("INSERT INTO mentor_group_messages (group_id, sender_id, message, message_type, file_url) 
                             VALUES (?, ?, ?, 'resource', ?)");
    $msg_stmt->execute([$group_id, $mentor_id, $msg_content, $file_path]);
    
    // Notificar membros
    $members_stmt = $db->prepare("SELECT user_id FROM mentor_group_members WHERE group_id = ? AND user_id != ?");
    $members_stmt->execute([$group_id, $mentor_id]);
    $members = $members_stmt->fetchAll(PDO::FETCH_ASSOC);
    
    foreach ($members as $member) {
        $notif_stmt = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) 
                                    VALUES (?, ?, 'Novo Material', ?, 'resource_added', ?)");
        $notif_stmt->execute([
            $member['user_id'],
            $mentor_id,
            "Novo material: {$title}",
            "paginas/social/messages.php?group={$group_id}"
        ]);
    }
    
    echo json_encode([
        'success' => true,
        'message' => 'Material enviado com sucesso!',
        'resource_id' => $resource_id,
        'file_path' => $file_path,
        'file_type' => $file_type
    ]);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

?>
