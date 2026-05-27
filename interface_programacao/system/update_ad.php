<?php
// servicos/system/update_ad.php
// API para atualizar anúncio existente
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/Security.php';

if (!isAdmin() || !hasPermission('ads')) {
    header("Location: ../../administracao/marketing/manage_ads.php");
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad_id = (int)($_POST['ad_id'] ?? 0);
    
    if ($ad_id <= 0) {
        header("Location: ../../administracao/marketing/manage_ads.php?error=invalid_id");
        exit();
    }
    
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'job');
    $link_url = trim($_POST['link_url'] ?? '');
    
    // Campos de cliente
    $client_name = trim($_POST['client_name'] ?? '');
    $client_email = trim($_POST['client_email'] ?? '');
    $client_phone = trim($_POST['client_phone'] ?? '');
    
    // Campos de campanha
    $start_date = !empty($_POST['start_date']) ? $_POST['start_date'] : null;
    $end_date = !empty($_POST['end_date']) ? $_POST['end_date'] : null;
    $budget = !empty($_POST['budget']) ? floatval($_POST['budget']) : 0;
    $payment_status = $_POST['payment_status'] ?? 'pending';
    $is_active = isset($_POST['is_active']) ? 1 : 0;
    $notes = trim($_POST['notes'] ?? '');
    
    if (empty($title) || empty($description)) {
        header("Location: ../../administracao/marketing/manage_ads.php?error=missing_fields");
        exit();
    }
    
    // Buscar imagem atual
    $current_query = "SELECT image_url FROM ads WHERE ad_id = :ad_id";
    $current_stmt = $db->prepare($current_query);
    $current_stmt->execute([':ad_id' => $ad_id]);
    $current_ad = $current_stmt->fetch();
    
    $image_url = $current_ad['image_url'] ?? null;
    
    // Upload de nova imagem (se fornecida)
    if (isset($_FILES['image']) && $_FILES['image']['error'] === UPLOAD_ERR_OK) {
        $stored = Security::storeUploadedFile(
            $_FILES['image'],
            __DIR__ . '/../../carregamentos/ads',
            'carregamentos/ads',
            [
                'image/jpeg' => 'jpg',
                'image/png' => 'png',
                'image/gif' => 'gif',
                'image/webp' => 'webp',
            ],
            8 * 1024 * 1024,
            'ad'
        );

        if (!$stored['ok']) {
            header("Location: ../../administracao/marketing/manage_ads.php?error=invalid_image");
            exit();
        }

        // Deletar imagem antiga
        if ($image_url && strpos($image_url, 'carregamentos/ads/') === 0 && file_exists(__DIR__ . '/../../' . $image_url)) {
            unlink(__DIR__ . '/../../' . $image_url);
        }

        $image_url = $stored['path'];
    }
    
    try {
        $query = "UPDATE ads SET 
                    title = :title,
                    description = :description,
                    type = :type,
                    link_url = :link_url,
                    image_url = :image_url,
                    client_name = :client_name,
                    client_email = :client_email,
                    client_phone = :client_phone,
                    start_date = :start_date,
                    end_date = :end_date,
                    budget = :budget,
                    payment_status = :payment_status,
                    is_active = :is_active,
                    notes = :notes
                  WHERE ad_id = :ad_id";
        
        $stmt = $db->prepare($query);
        $stmt->execute([
            ':title' => $title,
            ':description' => $description,
            ':type' => $type,
            ':link_url' => $link_url,
            ':image_url' => $image_url,
            ':client_name' => $client_name,
            ':client_email' => $client_email,
            ':client_phone' => $client_phone,
            ':start_date' => $start_date,
            ':end_date' => $end_date,
            ':budget' => $budget,
            ':payment_status' => $payment_status,
            ':is_active' => $is_active,
            ':notes' => $notes,
            ':ad_id' => $ad_id
        ]);
        
        header("Location: ../../administracao/marketing/manage_ads.php?success=ad_updated");
    } catch (Exception $e) {
        error_log("Erro ao atualizar anúncio: " . $e->getMessage());
        header("Location: ../../administracao/marketing/manage_ads.php?error=database_error");
    }
} else {
    header("Location: ../../administracao/marketing/manage_ads.php");
}

