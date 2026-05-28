<?php
// interface_programacao/admin/admin_send_birthday_wish.php
@session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('users')) {
    echo json_encode(['success' => false, 'message' => 'Não autorizado']);
    exit;
}

$user_id = isset($_POST['user_id']) ? (int)$_POST['user_id'] : 0;
if ($user_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Utilizador invalido']);
    exit;
}

try {
    $db = (new Database())->getConnection();

    $stmt = $db->prepare('SELECT user_id, full_name FROM users WHERE user_id = ?');
    $stmt->execute([$user_id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilizador não encontrado']);
        exit;
    }

    $notif = $db->prepare("
        INSERT INTO notifications (user_id, sender_id, title, content, type, link, created_at)
        VALUES (?, ?, ?, ?, 'system', ?, NOW())
    ");
    $notif->execute([
        $user_id,
        $_SESSION['user_id'] ?? null,
        'Feliz aniversario!',
        'A equipa KALIYE deseja-te um excelente aniversario e muito sucesso no teu percurso.',
        'index.php'
    ]);

    echo json_encode(['success' => true, 'message' => 'Mensagem de aniversario enviada.']);
} catch (Exception $e) {
    error_log('admin_send_birthday_wish error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao enviar mensagem: ' . $e->getMessage()]);
}
