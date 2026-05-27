<?php
// servicos/user/add_skill.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Método inválido.']);
    exit();
}

$user_id = $_SESSION['user_id'];
$skill_name = trim($_POST['skill_name'] ?? '');
$type = trim($_POST['type'] ?? 'learner'); // learner or expert

if (empty($skill_name)) {
    echo json_encode(['success' => false, 'message' => 'O nome da habilidade é obrigatório.']);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // 1. Check if skill exists in the master list, if not add it
    $stmt = $db->prepare("SELECT skill_id FROM skills WHERE LOWER(name) = LOWER(?)");
    $stmt->execute([$skill_name]);
    $skill = $stmt->fetch();

    if ($skill) {
        $skill_id = $skill['skill_id'];
    } else {
        $stmt = $db->prepare("INSERT INTO skills (name) VALUES (?)");
        $stmt->execute([$skill_name]);
        $skill_id = $db->lastInsertId();
    }

    // 2. Check if user already has this skill
    $stmt = $db->prepare("SELECT 1 FROM user_skills WHERE user_id = ? AND skill_id = ?");
    $stmt->execute([$user_id, $skill_id]);
    
    if ($stmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Já adicionou esta habilidade ao seu perfil.']);
        exit();
    }

    // 3. Add to user_skills
    $stmt = $db->prepare("INSERT INTO user_skills (user_id, skill_id, type) VALUES (?, ?, ?)");
    $stmt->execute([$user_id, $skill_id, $type]);

    echo json_encode(['success' => true, 'message' => 'Habilidade adicionada com sucesso!']);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Erro na base de dados: ' . $e->getMessage()]);
}

