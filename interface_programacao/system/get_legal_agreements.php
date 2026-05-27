<?php
session_start();
header('Content-Type: application/json; charset=utf-8');

require_once __DIR__ . '/../../configuracoes/base_dados.php';

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Nao autenticado.', 'agreements' => []]);
    exit;
}

try {
    $db = (new Database())->getConnection();
    $db->exec("CREATE TABLE IF NOT EXISTS legal_agreements (
        agreement_id SERIAL PRIMARY KEY,
        user_id INT NOT NULL,
        project_id INT NULL,
        admin_id INT NULL,
        agreement_type VARCHAR(80) NOT NULL,
        contract_terms TEXT,
        admin_signed_file VARCHAR(255),
        user_signed_file VARCHAR(255),
        status VARCHAR(30) DEFAULT 'pending',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        signed_at TIMESTAMP NULL
    )");
    $stmt = $db->prepare("SELECT * FROM legal_agreements WHERE user_id = ? ORDER BY created_at DESC");
    $stmt->execute([(int)$_SESSION['user_id']]);
    echo json_encode(['success' => true, 'agreements' => $stmt->fetchAll(PDO::FETCH_ASSOC)]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage(), 'agreements' => []]);
}
