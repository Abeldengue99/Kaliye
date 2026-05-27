<?php
// servicos/social/get_new_messages.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/ChatSecurity.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$current_user_id = $_SESSION['user_id'];
$other_user_id = $_GET['conversation_id'] ?? null;
$last_id = (int)($_GET['last_id'] ?? 0);

if (!$other_user_id) {
    echo json_encode(['success' => false, 'error' => 'Conversa não identificada']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
ChatSecurity::touchPresence($db, (int)$current_user_id);

$policy = ChatSecurity::canDirectMessage($db, (int)$current_user_id, (int)$other_user_id);
if (!$policy['allowed']) {
    echo json_encode(['success' => false, 'error' => $policy['reason'], 'messages' => []]);
    exit();
}

try {
    // Buscar apenas mensagens novas após o last_id
    $query = "SELECT m.*, u.profile_pic, u.full_name 
              FROM messages m 
              JOIN users u ON m.sender_id = u.user_id 
              WHERE ((m.sender_id = :cuid AND m.receiver_id = :ouid) 
              OR (m.sender_id = :ouid AND m.receiver_id = :cuid))
              AND m.message_id > :last_id
              ORDER BY m.sent_at ASC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([':cuid' => $current_user_id, ':ouid' => $other_user_id, ':last_id' => $last_id]);
    $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Marcar lidas
    if (count($messages) > 0) {
        $update = $db->prepare("UPDATE messages SET is_read = '1' WHERE sender_id = ? AND receiver_id = ? AND CAST(is_read AS INTEGER) = 0");
        $update->execute([$other_user_id, $current_user_id]);
    }

    foreach ($messages as &$msg) {
        $msg['content'] = ChatSecurity::revealContent($msg['content'] ?? '');
        $msg['is_sent'] = ($msg['sender_id'] == $current_user_id);
        $msg['status'] = ($msg['is_read'] === true || $msg['is_read'] === 1 || $msg['is_read'] === 't' || $msg['is_read'] == '1') ? 'read' : 'delivered';
        if (!empty($msg['profile_pic']) && $msg['profile_pic'] !== 'default_profile.png') {
            if (strpos($msg['profile_pic'], 'http') === 0 || strpos($msg['profile_pic'], 'carregamentos/') === 0) {
                // já está em formato URL/relative correto
            } else {
                $msg['profile_pic'] = 'carregamentos/profiles/' . $msg['profile_pic'];
            }
        } else {
            $msg['profile_pic'] = 'recursos/images/default_profile.png';
        }
    }

    // Recibos de leitura (opcional, para atualizar ícones no frontend)
    $stmt_receipts = $db->prepare("SELECT MAX(message_id) FROM messages WHERE sender_id = ? AND receiver_id = ? AND CAST(is_read AS INTEGER) = 1");
    $stmt_receipts->execute([$current_user_id, $other_user_id]);
    $max_read = $stmt_receipts->fetchColumn();

    $stmt_delivered = $db->prepare("SELECT MAX(message_id) FROM messages WHERE sender_id = ? AND receiver_id = ?");
    $stmt_delivered->execute([$current_user_id, $other_user_id]);
    $max_delivered = $stmt_delivered->fetchColumn();

    echo json_encode([
        'success' => true,
        'messages' => $messages,
        'receipts' => [
            'max_read' => (int)$max_read,
            'max_delivered' => (int)$max_delivered
        ]
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}

