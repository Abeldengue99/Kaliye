<?php
/**
 * interface_programacao/admin/admin_distribute_capital.php
 * Persists the capital split configured in administracao/finance/finances.php.
 */
@session_start();

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

function finishDistribution(bool $success, string $message): void {
    $accept = $_SERVER['HTTP_ACCEPT'] ?? '';
    $isAjax = stripos($accept, 'application/json') !== false || strtolower($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'xmlhttprequest';

    if ($isAjax) {
        header('Content-Type: application/json; charset=utf-8');
        echo json_encode(['success' => $success, 'message' => $message]);
        exit;
    }

    $type = $success ? 'success' : 'error';
    header('Location: ../../administracao/finance/finances.php?' . http_build_query([$type => $message]));
    exit;
}

if (!isAdmin() || !hasPermission('finances')) {
    finishDistribution(false, 'Nao autorizado.');
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    finishDistribution(false, 'Metodo invalido.');
}

$investment_id = (int)($_POST['investment_id'] ?? 0);
$project_id = (int)($_POST['project_id'] ?? 0);
$owner_id = (int)($_POST['owner_id'] ?? 0);
$mentor_id = (int)($_POST['mentor_id'] ?? 0);
$mentor_amount = round((float)($_POST['mentor_amount'] ?? 0), 2);
$company_amount = round((float)($_POST['company_amount'] ?? 0), 2);
$tranche_desc = $_POST['tranche_desc'] ?? [];
$tranche_amount = $_POST['tranche_amount'] ?? [];
$tranche_status = $_POST['tranche_status'] ?? [];

if ($investment_id <= 0 || $project_id <= 0 || $owner_id <= 0 || $mentor_id <= 0) {
    finishDistribution(false, 'Dados principais em falta.');
}

if ($mentor_amount < 0 || $company_amount < 0) {
    finishDistribution(false, 'Valores negativos nao sao permitidos.');
}

try {
    $db = (new Database())->getConnection();
    $db->beginTransaction();

    $investmentStmt = $db->prepare("
        SELECT investment_id, project_id, amount, status
        FROM project_investments
        WHERE investment_id = ? AND project_id = ?
        FOR UPDATE
    ");
    $investmentStmt->execute([$investment_id, $project_id]);
    $investment = $investmentStmt->fetch(PDO::FETCH_ASSOC);

    if (!$investment) {
        throw new Exception('Investimento nao encontrado.');
    }

    if (!in_array($investment['status'], ['approved', 'paid'], true)) {
        throw new Exception('Apenas investimentos aprovados ou pagos podem ser distribuidos.');
    }

    $mentorStmt = $db->prepare("SELECT user_id FROM users WHERE user_id = ? AND (user_type = 'mentor' OR mentorship_status = 'approved')");
    $mentorStmt->execute([$mentor_id]);
    if (!$mentorStmt->fetchColumn()) {
        throw new Exception('Mentor invalido ou nao aprovado.');
    }

    $ownerStmt = $db->prepare('SELECT user_id FROM users WHERE user_id = ?');
    $ownerStmt->execute([$owner_id]);
    if (!$ownerStmt->fetchColumn()) {
        throw new Exception('Autor do projeto nao encontrado.');
    }

    $existingStmt = $db->prepare('SELECT COALESCE(SUM(amount), 0) FROM payouts WHERE investment_id = ?');
    $existingStmt->execute([$investment_id]);
    $alreadyDistributed = (float)$existingStmt->fetchColumn();

    if ($alreadyDistributed > 0) {
        throw new Exception('Este investimento ja possui distribuicao registada.');
    }

    $rows = [];
    if ($mentor_amount > 0) {
        $rows[] = ['recipient_id' => $mentor_id, 'amount' => $mentor_amount, 'description' => 'Honorarios Mentor', 'status' => 'pending', 'role' => 'mentor'];
    }
    if ($company_amount > 0) {
        $rows[] = ['recipient_id' => (int)($_SESSION['user_id'] ?? $owner_id), 'amount' => $company_amount, 'description' => 'Comissao Plataforma KALIYE', 'status' => 'pending', 'role' => 'company'];
    }

    foreach ($tranche_amount as $idx => $amountRaw) {
        $amount = round((float)$amountRaw, 2);
        if ($amount <= 0) {
            continue;
        }

        $status = in_array(($tranche_status[$idx] ?? 'pending'), ['pending', 'paid'], true) ? $tranche_status[$idx] : 'pending';
        $description = trim((string)($tranche_desc[$idx] ?? 'Tranche do estudante'));
        $rows[] = [
            'recipient_id' => $owner_id,
            'amount' => $amount,
            'description' => $description !== '' ? $description : 'Tranche do estudante',
            'status' => $status,
            'role' => 'student',
        ];
    }

    $total = array_sum(array_column($rows, 'amount'));
    $investmentAmount = (float)$investment['amount'];

    if ($total <= 0) {
        throw new Exception('Informe pelo menos um valor para distribuir.');
    }

    if ($total > $investmentAmount + 0.01) {
        throw new Exception('A distribuicao excede o montante do investimento.');
    }

    $insert = $db->prepare("
        INSERT INTO payouts (investment_id, project_id, recipient_id, amount, description, status, role, created_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW())
    ");

    foreach ($rows as $row) {
        $insert->execute([
            $investment_id,
            $project_id,
            $row['recipient_id'],
            $row['amount'],
            $row['description'],
            $row['status'],
            $row['role'],
        ]);
    }

    try {
        $db->prepare('UPDATE project_investments SET distribution_status = ?, updated_at = NOW() WHERE investment_id = ?')
            ->execute([$total >= $investmentAmount - 0.01 ? 'distributed' : 'partial', $investment_id]);
    } catch (Exception $ignored) {
        $db->prepare('UPDATE project_investments SET updated_at = NOW() WHERE investment_id = ?')
            ->execute([$investment_id]);
    }

    try {
        $notif = $db->prepare("INSERT INTO notifications (user_id, sender_id, title, content, type, link) VALUES (?, ?, ?, ?, 'investment', ?)");
        $notif->execute([
            $owner_id,
            $_SESSION['user_id'] ?? null,
            'Capital distribuido',
            'A administracao registou a distribuicao do capital do seu projeto.',
            'paginas/conta/wallet.php'
        ]);
    } catch (Exception $ignored) {}

    $db->commit();
    finishDistribution(true, 'Distribuicao registada com sucesso.');
} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    error_log('admin_distribute_capital error: ' . $e->getMessage());
    finishDistribution(false, $e->getMessage());
}
