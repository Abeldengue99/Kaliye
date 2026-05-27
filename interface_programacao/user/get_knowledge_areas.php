<?php
// servicos/user/get_knowledge_areas.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $query = "SELECT * FROM knowledge_areas ORDER BY popularity_score DESC, name ASC";
    $stmt = $db->query($query);
    $areas = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'areas' => $areas]);

} catch (PDOException $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
         echo json_encode(['success' => true, 'areas' => []]);
    } else {
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

