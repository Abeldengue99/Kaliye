<?php
// interface_programacao/system/delete_ad.php
// API para deletar anúncio

// Iniciar output buffering
ob_start();
ob_clean();

header('Content-Type: application/json; charset=utf-8');
session_start();

function respondJSON($success, $message, $redirect = null) {
    ob_end_clean();
    $response = ['success' => $success, 'message' => $message];
    if ($redirect) $response['redirect'] = $redirect;
    echo json_encode($response, JSON_UNESCAPED_UNICODE);
    exit;
}

require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('ads')) {
    respondJSON(false, 'Sem permissão para eliminar anúncios.');
}

$ad_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ad_id <= 0) {
    respondJSON(false, 'ID de anúncio inválido.');
}

try {
    $database = new Database();
    /** @var PDO $db */
    $db = $database->getConnection();

    // Buscar imagem para deletar
    $query = "SELECT image_url FROM ads WHERE ad_id = :ad_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':ad_id' => $ad_id]);
    $ad = $stmt->fetch();
    
    if ($ad) {
        // Deletar imagem se existir
        if ($ad['image_url'] && file_exists(__DIR__ . '/../../' . $ad['image_url'])) {
            @unlink(__DIR__ . '/../../' . $ad['image_url']);
        }
        
        // Deletar anúncio (métricas serão deletadas automaticamente por CASCADE)
        $delete_query = "DELETE FROM ads WHERE ad_id = :ad_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([':ad_id' => $ad_id]);
        
        respondJSON(true, 'Anúncio eliminado com sucesso!', '../../administracao/marketing/manage_ads.php?success=ad_deleted');
    } else {
        respondJSON(false, 'Anúncio não encontrado.');
    }
} catch (Throwable $e) {
    error_log("Erro ao deletar anúncio: " . $e->getMessage());
    respondJSON(false, 'Erro ao eliminar anúncio: ' . $e->getMessage());
}

