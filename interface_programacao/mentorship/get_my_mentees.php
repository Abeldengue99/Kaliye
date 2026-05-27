<?php
// servicos/mentorship/get_my_mentees.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/free_mentorship_schema.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

$mentor_id = $_SESSION['user_id'];
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();
ensureFreeMentorshipTables($db);

try {
    // Determine if mentorship_tasks table exists
    $has_tasks_table = false;
    try {
        $db->query("SELECT 1 FROM mentorship_tasks LIMIT 1");
        $has_tasks_table = true;
    } catch (\Exception $e) {}

    // A mentee is anyone who has booked a slot or has been assigned a task or has an active mentorship record
    if ($has_tasks_table) {
        $query = "SELECT u.user_id, u.full_name, u.email, u.profile_pic, u.user_type
                  FROM users u
                  JOIN mentorship_slots ms ON u.user_id = ms.participant_id
                  WHERE ms.mentor_id = ? AND ms.participant_id IS NOT NULL
                  UNION
                  SELECT u.user_id, u.full_name, u.email, u.profile_pic, u.user_type
                  FROM users u
                  JOIN mentorship_tasks mt ON u.user_id = mt.mentee_id
                  WHERE mt.mentor_id = ?
                  UNION
                  SELECT u.user_id, u.full_name, u.email, u.profile_pic, u.user_type
                  FROM users u
                  JOIN mentorships m ON u.user_id = m.mentee_id
                  WHERE m.mentor_id = ? AND m.status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$mentor_id, $mentor_id, $mentor_id]);
    } else {
        $query = "SELECT DISTINCT u.user_id, u.full_name, u.email, u.profile_pic, u.user_type
                  FROM users u
                  JOIN mentorship_slots ms ON u.user_id = ms.participant_id
                  WHERE ms.mentor_id = ? AND ms.participant_id IS NOT NULL";
        $query .= " UNION
                  SELECT u.user_id, u.full_name, u.email, u.profile_pic, u.user_type
                  FROM users u
                  JOIN mentorships m ON u.user_id = m.mentee_id
                  WHERE m.mentor_id = ? AND m.status = 'active'";
        $stmt = $db->prepare($query);
        $stmt->execute([$mentor_id, $mentor_id]);
    }

    $mentees = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode(['success' => true, 'mentees' => $mentees]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}

