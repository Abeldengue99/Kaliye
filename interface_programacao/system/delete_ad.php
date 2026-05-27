<?php
// servicos/system/delete_ad.php
// API para deletar anúncio
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

if (!isAdmin() || !hasPermission('ads')) {
    header("Location: ../../administracao/manage_ads.php?error=permission_denied");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

$ad_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($ad_id <= 0) {
    header("Location: ../../administracao/manage_ads.php?error=invalid_id");
    exit();
}

try {
    // Buscar imagem para deletar
    $query = "SELECT image_url FROM ads WHERE ad_id = :ad_id";
    $stmt = $db->prepare($query);
    $stmt->execute([':ad_id' => $ad_id]);
    $ad = $stmt->fetch();
    
    if ($ad) {
        // Deletar imagem se existir
        if ($ad['image_url'] && file_exists('../../' . $ad['image_url'])) {
            unlink('../../' . $ad['image_url']);
        }
        
        // Deletar anúncio (métricas serão deletadas automaticamente por CASCADE)
        $delete_query = "DELETE FROM ads WHERE ad_id = :ad_id";
        $delete_stmt = $db->prepare($delete_query);
        $delete_stmt->execute([':ad_id' => $ad_id]);
        
        header("Location: ../../administracao/manage_ads.php?success=ad_deleted");
    } else {
        header("Location: ../../administracao/manage_ads.php?error=ad_not_found");
    }
} catch (Exception $e) {
    error_log("Erro ao deletar anúncio: " . $e->getMessage());
    header("Location: ../../administracao/manage_ads.php?error=database_error");
}

