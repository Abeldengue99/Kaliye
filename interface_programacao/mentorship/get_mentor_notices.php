<?php
// servicos/mentorship/get_mentor_notices.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

$user_id = $_SESSION['user_id'];
$view = $_GET['view'] ?? 'mentee';
$requested_mentor_id = $_GET['mentor_id'] ?? null;
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    // 1. Ensure Table
    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_notices (
        notice_id SERIAL PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        importance VARCHAR(10) DEFAULT 'normal' CHECK (importance IN ('normal', 'high')),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");

    if ($view === 'mentor') {
        $query = "SELECT n.*, u.full_name as author_name 
                  FROM mentorship_notices n 
                  JOIN users u ON n.mentor_id = u.user_id
                  WHERE n.mentor_id = ? 
                  ORDER BY n.created_at DESC";
        $stmt = $db->prepare($query);
        $stmt->execute([$user_id]);
    } else {
        // Find if visibility table exists
        $has_vis_table = false;
        try {
            $db->query("SELECT 1 FROM mentorship_notice_visibility LIMIT 1");
            $has_vis_table = true;
        } catch (Exception $e) {}

        if ($has_vis_table) {
            $query = "SELECT n.*, u.full_name as author_name 
                      FROM mentorship_notices n 
                      JOIN users u ON n.mentor_id = u.user_id
                      LEFT JOIN mentorship_notice_visibility mnv ON n.notice_id = mnv.notice_id
                      WHERE n.mentor_id IN (
                          SELECT mentor_id FROM mentorship_slots WHERE participant_id = ? UNION
                          SELECT mentor_id FROM mentorship_tasks WHERE mentee_id = ?
                      )
                      AND (mnv.user_id = ? OR mnv.id IS NULL)
                      ORDER BY n.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $user_id, $user_id]);
        } else {
            // Fallback for mentees without visibility table yet
            $query = "SELECT n.*, u.full_name as author_name 
                      FROM mentorship_notices n 
                      JOIN users u ON n.mentor_id = u.user_id
                      WHERE n.mentor_id IN (
                          SELECT mentor_id FROM mentorship_slots WHERE participant_id = ? UNION
                          SELECT mentor_id FROM mentorship_tasks WHERE mentee_id = ?
                      ) 
                      ORDER BY n.created_at DESC";
            $stmt = $db->prepare($query);
            $stmt->execute([$user_id, $user_id]);
        }
    }

    $notices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'notices' => $notices]);

} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
?>

