<?php
/**
 * send_message.php
 * Envia uma nova mensagem numa sala VIP. Suporta envio de ficheiros.
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/ChatSecurity.php';
header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $user_id = (int)$_SESSION['user_id'];
    
    // Tratamento híbrido (pode vir como form-data se tiver ficheiro, ou json raw)
    if (!empty($_POST)) {
        $chat_id = (int)($_POST['chat_id'] ?? 0);
        $message = ChatSecurity::normalizeText($_POST['message'] ?? '');
    } else {
        $data = json_decode(file_get_contents("php://input"), true) ?? [];
        $chat_id = (int)($data['chat_id'] ?? 0);
        $message = ChatSecurity::normalizeText($data['message'] ?? '');
    }

    $has_file = isset($_FILES['file']) && $_FILES['file']['error'] === UPLOAD_ERR_OK;

    if (!$chat_id || (empty($message) && !$has_file)) {
        echo json_encode(['success' => false, 'error' => 'Mensagem ou ficheiro inválido.']);
        exit;
    }

    // Verifica se o user pertence à sala
    $stmt_check = $db->prepare("SELECT 1 FROM vip_chat_participants WHERE chat_id = ? AND user_id = ?");
    $stmt_check->execute([$chat_id, $user_id]);
    if (!$stmt_check->fetchColumn()) {
        echo json_encode(['success' => false, 'error' => 'Não tens permissão nesta sala.']);
        exit;
    }

    $file_path = null;
    $file_name = null;
    $file_type = null;

    if ($has_file) {
        $upload_dir = '../../uploads/vip_chats/';
        if (!is_dir($upload_dir)) {
            mkdir($upload_dir, 0755, true);
        }
        
        $file_info = pathinfo($_FILES['file']['name']);
        $ext = strtolower($file_info['extension'] ?? '');
        $allowed_exts = ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'png', 'jpg', 'jpeg', 'gif', 'zip', 'rar', 'txt'];
        
        if (!in_array($ext, $allowed_exts)) {
            echo json_encode(['success' => false, 'error' => 'Tipo de ficheiro não permitido.']);
            exit;
        }

        $new_name = uniqid('vip_file_') . '.' . $ext;
        $dest = $upload_dir . $new_name;
        
        if (move_uploaded_file($_FILES['file']['tmp_name'], $dest)) {
            $file_path = 'uploads/vip_chats/' . $new_name;
            $file_name = $_FILES['file']['name'];
            $file_type = $ext;
        } else {
            echo json_encode(['success' => false, 'error' => 'Falha ao gravar o ficheiro.']);
            exit;
        }
    }

    $stmt = $db->prepare("INSERT INTO vip_chat_messages (chat_id, sender_id, message_text, file_path, file_name, file_type) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([$chat_id, $user_id, $message, $file_path, $file_name, $file_type]);

    // Incrementar a badge de mensagens não lidas para todos os outros participantes da sala
    $stmt_unread = $db->prepare("UPDATE vip_chat_participants SET unread_count = unread_count + 1 WHERE chat_id = ? AND user_id != ?");
    $stmt_unread->execute([$chat_id, $user_id]);

    echo json_encode(['success' => true]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => 'Erro interno ao enviar.']);
}
?>
