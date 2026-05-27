<?php
// servicos/user/get_user_expertise.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $query = "SELECT ue.*, ka.name as area_name, ka.icon, ka.color, ka.category
              FROM user_expertises ue
              JOIN knowledge_areas ka ON ue.area_id = ka.area_id
              WHERE ue.user_id = ?
              ORDER BY ue.is_primary DESC, ue.proficiency_level DESC";
    
    $stmt = $db->prepare($query);
    $stmt->execute([$user_id]);
    $expertises = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'expertises' => $expertises]);

} catch (PDOException $e) {
    // If table doesn't exist, return empty array instead of error
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
         echo json_encode(['success' => true, 'expertises' => []]);
    } else {
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

