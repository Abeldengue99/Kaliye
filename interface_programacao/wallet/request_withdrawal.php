<?php
/**
 * request_withdrawal.php - Process withdrawal requests from wallet
 * Com Rate Limiting progressivo integrado (proteção financeira crítica).
 */
session_start();
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/RateLimiter.php';

// Check global payments feature flag
$payments_config = require __DIR__ . '/../../configuracoes/pagamentos.php';
if (isset($payments_config['payments_enabled']) && $payments_config['payments_enabled'] === false) {
    echo json_encode(['success' => false, 'message' => 'Funcionalidade financeira desativada nesta versão.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$amount = $_POST['amount'] ?? 0;
$bank_details = $_POST['bank_details'] ?? '';

// ─── RATE LIMITING PROGRESSIVO (financeiro) ────────────────────────────────
$real_ip = RateLimiter::getRealIP();
$rl = RateLimiter::check($db, 'withdrawal', 'user_' . $user_id, $real_ip);

if (!$rl['allowed']) {
    if ($rl['phase'] === 'hard_lock') {
        http_response_code(429);
        echo json_encode(['success' => false, 'rate_limited' => true, 'hard_lock' => true,
            'message' => 'A tua conta foi bloqueada para operações financeiras por segurança. Contacta a equipa Aksanti imediatamente.']);
    } else {
        http_response_code(429);
        echo json_encode(['success' => false, 'rate_limited' => true,
            'retry_after' => $rl['retry_after'],
            'message' => 'Limite de levantamentos atingido. Aguarda ' . ceil($rl['retry_after'] / 3600) . ' hora(s).']);
    }
    exit();
}
// ────────────────────────────────────────────────────────────────────────

if ($amount < 1000) {
    echo json_encode(['success' => false, 'message' => 'O valor mínimo para levantamento é de 1.000 AKZ.']);
    exit();
}

if (empty($bank_details)) {
    echo json_encode(['success' => false, 'message' => 'Por favor, insira os seus dados bancários.']);
    exit();
}

try {
    $db->beginTransaction();

    // 1. Check current balance and IBAN status
    $stmt = $db->prepare("SELECT wallet_balance, bank_iban FROM users WHERE user_id = ? FOR UPDATE");
    $stmt->execute([$user_id]);
    $user_row = $stmt->fetch(PDO::FETCH_ASSOC);
    $current_balance = $user_row['wallet_balance'] ?? 0;
    $existing_iban   = $user_row['bank_iban'] ?? '';

    if ($current_balance < $amount) {
        $db->rollBack();
        echo json_encode(['success' => false, 'message' => 'Saldo insuficiente para este levantamento.']);
        exit();
    }

    // 2. Persistência de IBAN (Se for a primeira vez)
    // Se o utilizador já tem IBAN, usamos o que está na DB (Safety Lock)
    // Caso contrário, gravamos o novo para sempre.
    $final_iban = $existing_iban;
    if (empty($existing_iban)) {
        $update_iban = $db->prepare("UPDATE users SET bank_iban = ? WHERE user_id = ?");
        $update_iban->execute([$bank_details, $user_id]);
        $final_iban = $bank_details;
    }

    // 3. Deduct from balance (Locking the funds)
    $new_balance = $current_balance - $amount;
    $update_balance = $db->prepare("UPDATE users SET wallet_balance = ? WHERE user_id = ?");
    $update_balance->execute([$new_balance, $user_id]);

    // 4. Create Transaction Record
    $trans_stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $trans_stmt->execute([
        $user_id,
        'withdraw',
        -$amount,
        $current_balance,
        $new_balance,
        'pending_approval',
        "Levantamento solicitado para IBAN: " . $final_iban
    ]);

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Solicitação enviada com sucesso. O valor foi cativado e será processado após revisão.']);

} catch (Exception $e) {
    $db->rollBack();
    echo json_encode(['success' => false, 'message' => 'Erro ao processar solicitação: ' . $e->getMessage()]);
}
