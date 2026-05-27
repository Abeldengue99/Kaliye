<?php
// servicos/mentorship/add_expertise.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$area_id = $_POST['area_id'] ?? null;
$new_area_name = $_POST['new_area_name'] ?? null;
$level = $_POST['proficiency_level'] ?? 'intermediate';
$years = $_POST['years_experience'] ?? 0;
$can_mentor = isset($_POST['can_mentor']) ? 1 : 0;
$is_primary = isset($_POST['is_primary']) ? 1 : 0;

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // 1. If new area, create it
    if ($new_area_name && !$area_id) {
        $check = $db->prepare("SELECT area_id FROM knowledge_areas WHERE name = ?");
        $check->execute([$new_area_name]);
        if ($id = $check->fetchColumn()) {
            $area_id = $id;
        } else {
            $ins = $db->prepare("INSERT INTO knowledge_areas (name, category, created_at) VALUES (?, 'other', NOW())");
            $ins->execute([$new_area_name]);
            $area_id = $db->lastInsertId();
        }
    }

    if (!$area_id) {
        throw new Exception("Area invalid");
    }

    // 2. Insert or Update User Expertise
    // Check if exists
    $check_exp = $db->prepare("SELECT expertise_id FROM user_expertises WHERE user_id = ? AND area_id = ?");
    $check_exp->execute([$user_id, $area_id]);

    if ($check_exp->fetch()) {
        $upd = $db->prepare("UPDATE user_expertises SET proficiency_level = ?, years_experience = ?, can_mentor = ?, is_primary = ? WHERE user_id = ? AND area_id = ?");
        $upd->execute([$level, $years, $can_mentor, $is_primary, $user_id, $area_id]);
    } else {
        $ins = $db->prepare("INSERT INTO user_expertises (user_id, area_id, proficiency_level, years_experience, can_mentor, is_primary, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $ins->execute([$user_id, $area_id, $level, $years, $can_mentor, $is_primary]);
    }

    echo json_encode(['success' => true]);

} catch (Exception $e) {
    if (strpos($e->getMessage(), "doesn't exist") !== false) {
         // Create tables if missing (for demo)
         // Not doing here to keep it clean, but could add migration logic
         echo json_encode(['success' => false, 'message' => 'Table missing: ' . $e->getMessage()]);
    } else {
         echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
}

