<?php
// servicos/user/connection_action.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'Não autenticado']);
    exit();
}

$user_id = (int)$_SESSION['user_id'];
requireValidCSRFTokenJson();

$target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : 0;
$action = $_POST['action'] ?? '';
$notification_id = isset($_POST['notification_id']) ? (int)$_POST['notification_id'] : 0;
$allowed_actions = ['request', 'accept', 'reject', 'cancel', 'remove'];

if ($target_id <= 0 || !in_array($action, $allowed_actions, true)) {
    echo json_encode(['success' => false, 'error' => 'Dados incompletos']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();

    if ($user_id === $target_id) {
        throw new Exception('Não podes conectar contigo mesmo.');
    }

    $targetCheck = $db->prepare('SELECT user_id FROM users WHERE user_id = ?');
    $targetCheck->execute([$target_id]);
    if (!$targetCheck->fetchColumn()) {
        throw new Exception('Utilizador não encontrado.');
    }

    $u1 = min($user_id, $target_id);
    $u2 = max($user_id, $target_id);

    if ($action === 'request') {
        $check = $db->prepare('SELECT status, requester_id FROM user_connections WHERE user_id_1 = ? AND user_id_2 = ?');
        $check->execute([$u1, $u2]);
        $existing = $check->fetch();

        if ($existing) {
            if ($existing['status'] === 'pending') {
                $message = ((int)$existing['requester_id'] === $user_id)
                    ? 'O teu pedido ainda esta pendente.'
                    : 'Este utilizador ja te enviou um pedido. Aceita ou recusa o pedido recebido.';
                throw new Exception($message);
            }

            throw new Exception('Voces ja estao conectados.');
        }

        $stmt = $db->prepare("INSERT INTO user_connections (user_id_1, user_id_2, status, requester_id) VALUES (?, ?, 'pending', ?)");
        $stmt->execute([$u1, $u2, $user_id]);

        $stmt_user = $db->prepare('SELECT full_name, user_type FROM users WHERE user_id = ?');
        $stmt_user->execute([$user_id]);
        $u_sender = $stmt_user->fetch();

        $actor_name = (($u_sender['user_type'] ?? '') === 'investor') ? 'Um Investidor' : ($u_sender['full_name'] ?? 'Um membro');
        $notif_query = "INSERT INTO notifications (user_id, sender_id, title, content, type, link)
                        VALUES (?, ?, ?, 'Verifique o perfil deste membro para aceitar ou recusar a sinergia na rede KALIYE.', 'connection_request', ?)";
        $notif_stmt = $db->prepare($notif_query);
        $link = 'paginas/social/profile.php?id=' . $user_id;
        $notif_stmt->execute([$target_id, $user_id, $actor_name . ' quer conectar-se consigo', $link]);

        $msg = 'Pedido de conexao enviado!';
        $newStatus = 'pending';
    } elseif ($action === 'accept') {
        $query = "UPDATE user_connections
                  SET status = 'accepted', updated_at = CURRENT_TIMESTAMP
                  WHERE user_id_1 = :u1 AND user_id_2 = :u2
                    AND status = 'pending'
                    AND requester_id = :target_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':u1' => $u1, ':u2' => $u2, ':target_id' => $target_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Pedido não encontrado, ja processado ou sem permissão para aceitar.');
        }

        $stmt_u = $db->prepare('SELECT full_name, user_type FROM users WHERE user_id = ?');
        $stmt_u->execute([$user_id]);
        $u_acc = $stmt_u->fetch();
        $actor_name_acc = (($u_acc['user_type'] ?? '') === 'investor') ? 'Um Investidor' : ($u_acc['full_name'] ?? 'Um membro');

        $notif_query = "INSERT INTO notifications (user_id, sender_id, title, content, type, link)
                        VALUES (?, ?, ?, 'A sua rede foi expandida com sucesso! Agora podem canalizar novos projectos.', 'connection_accepted', ?)";
        $notif_stmt = $db->prepare($notif_query);
        $link = 'paginas/social/profile.php?id=' . $user_id;
        $notif_stmt->execute([$target_id, $user_id, $actor_name_acc . ' aceitou a sua conexao!', $link]);

        $msg = 'Você aceitou o pedido de conexao.';
        $newStatus = 'accepted';
    } elseif ($action === 'reject') {
        $query = "DELETE FROM user_connections
                  WHERE user_id_1 = :u1 AND user_id_2 = :u2
                    AND status = 'pending'
                    AND requester_id = :target_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':u1' => $u1, ':u2' => $u2, ':target_id' => $target_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Pedido não encontrado ou sem permissão para recusar.');
        }

        $msg = 'Pedido de conexao recusado.';
        $newStatus = 'none';
    } elseif ($action === 'cancel') {
        $query = "DELETE FROM user_connections
                  WHERE user_id_1 = :u1 AND user_id_2 = :u2
                    AND status = 'pending'
                    AND requester_id = :user_id";
        $stmt = $db->prepare($query);
        $stmt->execute([':u1' => $u1, ':u2' => $u2, ':user_id' => $user_id]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Pedido não encontrado ou ja processado.');
        }

        $msg = 'Pedido de conexao cancelado.';
        $newStatus = 'none';
    } else {
        $query = "DELETE FROM user_connections
                  WHERE user_id_1 = :u1 AND user_id_2 = :u2
                    AND status = 'accepted'";
        $stmt = $db->prepare($query);
        $stmt->execute([':u1' => $u1, ':u2' => $u2]);

        if ($stmt->rowCount() === 0) {
            throw new Exception('Conexao não encontrada.');
        }

        $msg = 'Conexao removida.';
        $newStatus = 'none';
    }

    if ($notification_id > 0) {
        $read_stmt = $db->prepare("UPDATE notifications SET is_read = '1' WHERE notification_id = ? AND user_id = ?");
        $read_stmt->execute([$notification_id, $user_id]);
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => $msg, 'new_status' => $newStatus]);
} catch (Exception $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'message' => $e->getMessage()]);
}
