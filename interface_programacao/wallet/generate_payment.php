<?php
/**
 * generate_payment.php - Create a new payment reference for wallet deposit
 * Com Rate Limiting progressivo integrado.
 */
session_start();
header('Content-Type: application/json');
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/RateLimiter.php';
// Check global payments feature flag
$payments_config = require __DIR__ . '/../../configuracoes/pagamentos.php';
if (isset($payments_config['payments_enabled']) && $payments_config['payments_enabled'] === false) {
    echo json_encode(['success' => false, 'message' => 'Funcionalidade de pagamentos desativada nesta versão.']);
    exit();
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

$db = (new Database())->getConnection();
$user_id = $_SESSION['user_id'];
$amount = $_POST['amount'] ?? 0;

// ─── RATE LIMITING PROGRESSIVO ──────────────────────────────────────────────────
$real_ip = RateLimiter::getRealIP();
$rl = RateLimiter::check($db, 'payment', 'user_' . $user_id, $real_ip);

if (!$rl['allowed']) {
    http_response_code(429);
    echo json_encode(['success' => false, 'rate_limited' => true,
        'retry_after' => $rl['retry_after'],
        'message' => $rl['phase'] === 'hard_lock'
            ? 'Operações financeiras bloqueadas. Contacta a equipa Aksanti.'
            : 'Demasiados pedidos de pagamento. Aguarda ' . ceil($rl['retry_after'] / 60) . ' minuto(s).'    ]);
    exit();
}
// ────────────────────────────────────────────────────────────────────────

// If payments are enabled, still enforce minimum constraints
if ($amount < 500) {
    echo json_encode(['success' => false, 'message' => 'O montante mínimo para depósito é de 500 AKZ.']);
    exit();
}

try {
    // 1. Generate a unique reference
    $reference = sprintf("%09d", mt_rand(1, 999999999));
    $entity = "00991"; // Default entity for Aksanti (example)
    
    // 2. Clear previous pending payments for this user to avoid clutter (Optional policy)
    // $db->prepare("DELETE FROM payments WHERE user_id = ? AND status = 'pending'")->execute([$user_id]);

    // 3. Create Payment Record
    $stmt = $db->prepare("INSERT INTO payments (user_id, reference, entity, amount, status, description) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->execute([
        $user_id,
        $reference,
        $entity,
        $amount,
        'pending',
        'Depósito via Multicaixa/Referência'
    ]);

    // 4. Create a shadow transaction entry as pending
    // Fetch current balance
    $bal_stmt = $db->prepare("SELECT wallet_balance FROM users WHERE user_id = ?");
    $bal_stmt->execute([$user_id]);
    $current_bal = $bal_stmt->fetchColumn() ?: 0;

    $trans_stmt = $db->prepare("INSERT INTO transactions (user_id, type, amount, balance_before, balance_after, status, description) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $trans_stmt->execute([
        $user_id,
        'deposit',
        $amount,
        $current_bal,
        $current_bal, // Balance only changes after processing
        'pending',
        "Depósito iniciado (Ref: $reference)"
    ]);

    echo json_encode([
        'success' => true,
        'reference' => $reference,
        'entity' => $entity,
        'amount' => number_format($amount, 0, ',', '.'),
        'message' => 'Referência gerada com sucesso.'
    ]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno ao gerar pagamento: ' . $e->getMessage()]);
}
