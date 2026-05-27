<?php
// servicos/projects/get_project_media.php
session_start();
require_once '../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_GET['project_id'])) {
    echo json_encode([]);
    exit();
}

$project_id = $_GET['project_id'];

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $stmt = $db->prepare("SELECT media_id, media_url, media_type FROM project_media WHERE project_id = ?");
    $stmt->execute([$project_id]);
    $media = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Adjust paths if necessary - usually stored as 'carregamentos/...'
    // Frontend handles adding '../' or base_url
    
    echo json_encode($media);
} catch (PDOException $e) {
    echo json_encode(['error' => $e->getMessage()]);
}
?>

