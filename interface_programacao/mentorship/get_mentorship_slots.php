<?php
// servicos/mentorship/get_mentorship_slots.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$requested_mentor_id = $_GET['mentor_id'] ?? null;
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $view = $_GET['view'] ?? 'mentee';

    // 2. Query Slots
    if ($requested_mentor_id) {
        // Public profile view: Fetch slots for a specific mentor
        $query = "SELECT ms.*, 
                         m.full_name as mentor_name, 
                         p.full_name as participant_name,
                         ms.slot_id as booking_id 
                  FROM mentorship_slots ms
                  JOIN users m ON ms.mentor_id = m.user_id
                  LEFT JOIN users p ON ms.participant_id = p.user_id
                  WHERE ms.mentor_id = ? 
                  ORDER BY ms.start_time ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$requested_mentor_id]);
    } elseif ($view === 'mentor') {
        // Mentor Dashboard: Show MY created slots
        $query = "SELECT ms.*, 
                         m.full_name as mentor_name, 
                         p.full_name as participant_name,
                         ms.slot_id as booking_id 
                  FROM mentorship_slots ms
                  JOIN users m ON ms.mentor_id = m.user_id
                  LEFT JOIN users p ON ms.participant_id = p.user_id
                  WHERE ms.mentor_id = ? 
                  ORDER BY ms.start_time ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    } else {
        // Mentee Dashboard: Show MY bookings + Available slots 
        // Showing all available slots might be too much, restricting to MY bookings for now to avoid clutter
        // Or show my bookings AND confirmed slots
        $query = "SELECT ms.*, 
                         m.full_name as mentor_name, 
                         p.full_name as participant_name,
                         ms.slot_id as booking_id 
                  FROM mentorship_slots ms
                  JOIN users m ON ms.mentor_id = m.user_id
                  LEFT JOIN users p ON ms.participant_id = p.user_id
                  WHERE ms.participant_id = ? 
                  ORDER BY ms.start_time ASC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    }

    $slots = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'slots' => $slots]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>

