<?php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json; charset=utf-8');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Sessao expirada.']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'message' => 'Apenas mentores aprovados podem remover horarios.']);
    exit;
}

$slotId = isset($_GET['id']) ? (int)$_GET['id'] : (int)($_POST['id'] ?? 0);
if ($slotId <= 0) {
    echo json_encode(['success' => false, 'message' => 'Horario invalido.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $stmt = $db->prepare("
        DELETE FROM mentorship_slots
        WHERE slot_id = ?
          AND mentor_id = ?
          AND status IN ('available', 'booked')
    ");
    $stmt->execute([$slotId, $_SESSION['user_id']]);

    if ($stmt->rowCount() < 1) {
        echo json_encode(['success' => false, 'message' => 'Horario nao encontrado ou ja confirmado.']);
        exit;
    }

    echo json_encode(['success' => true, 'message' => 'Horario removido com sucesso.']);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Falha ao remover horario.']);
}
