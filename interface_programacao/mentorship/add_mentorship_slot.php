<?php
// servicos/mentorship/add_mentorship_slot.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem criar horarios de mentoria.']);
    exit;
}

$user_id = $_SESSION['user_id'];
$start_time = $_POST['start_time'] ?? null;
$end_time = $_POST['end_time'] ?? null;
$participant_id = $_POST['target_user_id'] ?? null;
$title = $_POST['title'] ?? 'Sessão de Mentoria';
$description = $_POST['description'] ?? '';
$category = $_POST['category'] ?? 'Outro';
$duration = $_POST['duration'] ?? 60;

if (!$start_time || !$end_time) {
    echo json_encode(['success' => false, 'error' => 'Missing start/end time']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_slots (
        slot_id SERIAL PRIMARY KEY,
        mentor_id INTEGER NOT NULL,
        participant_id INTEGER NULL,
        start_time TIMESTAMP NOT NULL,
        end_time TIMESTAMP NOT NULL,
        status VARCHAR(20) DEFAULT 'available',
        meeting_link VARCHAR(255),
        meeting_room VARCHAR(100),
        platform VARCHAR(50) DEFAULT 'jitsi',
        title VARCHAR(255),
        description TEXT,
        category VARCHAR(100),
        duration INTEGER DEFAULT 60,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    $status = $participant_id ? 'booked' : 'available';
    
    // Automatic Room Generation for Jitsi
    $room_name = "Aksanti_" . substr(md5($user_id . time() . rand()), 0, 12);
    
    $query = "INSERT INTO mentorship_slots 
              (mentor_id, start_time, end_time, status, participant_id, meeting_room, platform, title, description, category, duration) 
              VALUES (?, ?, ?, ?, ?, ?, 'jitsi', ?, ?, ?, ?)";
    $stmt = $db->prepare($query);
    $stmt->execute([
        $user_id, 
        $start_time, 
        $end_time, 
        $status, 
        $participant_id ?: null,
        $room_name,
        $title,
        $description,
        $category,
        $duration
    ]);

    echo json_encode(['success' => true, 'message' => 'Slot criado com sucesso', 'room' => $room_name]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>

