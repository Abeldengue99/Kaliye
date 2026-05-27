<?php
// servicos/system/save_ad.php
// API para criar novo anúncio com todos os campos de monetização
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
    $title = trim($_POST['title'] ?? '');
    $description = trim($_POST['description'] ?? '');
    $type = trim($_POST['type'] ?? 'job');
    $link_url = trim($_POST['link_url'] ?? '');
    
    // Novos campos de cliente
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
    
    // Upload de imagem
    $image_url = null;
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

        $image_url = $stored['path'];
    }
    
    try {
        $query = "INSERT INTO ads (
                    title, description, type, link_url, image_url,
                    client_name, client_email, client_phone,
                    start_date, end_date, budget, payment_status, is_active, notes
                  ) VALUES (
                    :title, :description, :type, :link_url, :image_url,
                    :client_name, :client_email, :client_phone,
                    :start_date, :end_date, :budget, :payment_status, :is_active, :notes
                  )";
        
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
            ':notes' => $notes
        ]);
        
        header("Location: ../../administracao/marketing/manage_ads.php?success=ad_created");
    } catch (Exception $e) {
        error_log("Erro ao criar anúncio: " . $e->getMessage());
        header("Location: ../../administracao/marketing/manage_ads.php?error=database_error");
    }
} else {
    header("Location: ../../administracao/manage_ads.php");
}

