<?php
/**
 * accept_project_nda.php
 * Endpoint para os mentores e investidores aceitarem o Termo de Confidencialidade
 * antes de poderem visualizar os detalhes do dossier.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

require_once '../../configuracoes/base_dados.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit;
}

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit;
}

$user_id = (int)$_SESSION['user_id'];
$project_id = isset($_POST['project_id']) ? (int)$_POST['project_id'] : 0;

if (!$project_id) {
    echo json_encode(['success' => false, 'message' => 'ID de projeto inválido.']);
    exit;
}

try {
    $db = (new Database())->getConnection();
    
    // Verificar se já assinou
    $stmt = $db->prepare("SELECT 1 FROM project_nda_logs WHERE project_id = ? AND user_id = ?");
    $stmt->execute([$project_id, $user_id]);
    if ($stmt->fetchColumn()) {
        echo json_encode(['success' => true, 'message' => 'NDA já estava assinado.']);
        exit;
    }

    $ip_address = $_SERVER['REMOTE_ADDR'] ?? 'unknown';

    // Inserir log do NDA
    $insert = $db->prepare("INSERT INTO project_nda_logs (project_id, user_id, ip_address) VALUES (?, ?, ?)");
    $insert->execute([$project_id, $user_id, $ip_address]);

    echo json_encode(['success' => true, 'message' => 'Termo de Sigilo aceite com sucesso. O dossier foi desbloqueado.']);

} catch (PDOException $e) {
    error_log("Erro ao assinar NDA: " . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro interno ao assinar o termo.']);
}
?>
