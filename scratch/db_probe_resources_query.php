<?php
require_once __DIR__ . '/../configuracoes/base_dados.php';
require_once __DIR__ . '/../inclusoes/RetentionMaintenance.php';
require_once __DIR__ . '/../inclusoes/free_mentorship_schema.php';

header('Content-Type: application/json');

$db = (new Database())->getConnection();
ensureFreeMentorshipTables($db);
(new RetentionMaintenance($db))->runIfDue(7);

$userId = (int)($_GET['user_id'] ?? 15);
$view = $_GET['view'] ?? 'mentor';
$activeClause = "mr.archived_at IS NULL AND (mr.expires_at IS NULL OR mr.expires_at > NOW())";

if ($view === 'mentor') {
    $stmt = $db->prepare("
        SELECT mr.*, u.full_name AS author_name
        FROM mentorship_resources mr
        JOIN users u ON mr.mentor_id = u.user_id
        WHERE mr.mentor_id = ?
          AND $activeClause
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$userId]);
} else {
    $stmt = $db->prepare("
        SELECT mr.*, u.full_name AS author_name
        FROM mentorship_resources mr
        JOIN users u ON mr.mentor_id = u.user_id
        WHERE $activeClause
          AND mr.mentor_id IN (
              SELECT DISTINCT mentor_id FROM mentorship_tasks WHERE mentee_id = ?
              UNION
              SELECT DISTINCT mentor_id FROM mentorship_slots WHERE participant_id = ?
              UNION
              SELECT DISTINCT mentor_id FROM mentorships WHERE mentee_id = ? AND status = 'active'
          )
        ORDER BY mr.created_at DESC
    ");
    $stmt->execute([$userId, $userId, $userId]);
}

echo json_encode(['success' => true, 'resources' => $stmt->fetchAll(PDO::FETCH_ASSOC)], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE);
