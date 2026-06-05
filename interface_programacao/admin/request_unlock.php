<?php
ob_start();
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit();
}

$email = filter_var($_POST['email'] ?? '', FILTER_SANITIZE_EMAIL);
$name = htmlspecialchars($_POST['name'] ?? '');
$reason = htmlspecialchars($_POST['reason'] ?? '');
$ref_code = htmlspecialchars($_POST['ref_code'] ?? '');

if (empty($email) || empty($name)) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'O nome e o email são obrigatórios.']);
    exit();
}

try {
    $database = new Database();
    $db = $database->getConnection();

    // Tentar encontrar o user_id baseado no email
    $stmt = $db->prepare("SELECT user_id FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user_id = $stmt->fetchColumn();

    // Podemos guardar o name e reason na mensagem ou alterar a tabela
    // Como criámos a tabela unlock_requests só com email e ref_code, vamos colocar no email ou numa coluna nova.
    // É mais fácil recriar a tabela ou apenas adicionar as colunas, mas para evitar erros de SQL, vamos tentar adicionar as colunas se não existirem, ou apenas gravar no status/ref_code ou criar um log.
    // Wait, eu criei a tabela unlock_requests. Posso apenas fazer um log detalhado ou atualizar a tabela.
    
    // Melhor: adicionar colunas name e reason dinamicamente
    try {
        $db->exec("ALTER TABLE unlock_requests ADD COLUMN name VARCHAR(255)");
        $db->exec("ALTER TABLE unlock_requests ADD COLUMN reason TEXT");
    } catch (Exception $e) {} // Ignora se já existir

    $ins = $db->prepare("INSERT INTO unlock_requests (email, ip_address, ref_code, status, created_at, name, reason) VALUES (?, ?, ?, 'pending', CURRENT_TIMESTAMP, ?, ?)");
    $ins->execute([$email, $_SERVER['REMOTE_ADDR'], $ref_code, $name, $reason]);

    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => true, 'message' => 'Pedido enviado com sucesso para a equipa administrativa.']);
} catch (PDOException $e) {
    ob_clean();
    header('Content-Type: application/json');
    echo json_encode(['success' => false, 'message' => 'Erro interno ao processar o pedido: ' . $e->getMessage()]);
}
