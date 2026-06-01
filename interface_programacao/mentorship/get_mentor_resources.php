<?php
// servicos/mentorship/get_mentor_resources.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/RetentionMaintenance.php';
require_once __DIR__ . '/../../inclusoes/free_mentorship_schema.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'User not authenticated']);
    exit;
}

function ensureReadableMentorshipResourceStorage(PDO $db): void {
    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_resources (
            resource_id SERIAL PRIMARY KEY,
            mentor_id INT NOT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT,
            resource_type VARCHAR(20) DEFAULT 'file' CHECK (resource_type IN ('file', 'link')),
            file_url VARCHAR(500),
            original_filename VARCHAR(255),
            file_type VARCHAR(120),
            file_size BIGINT DEFAULT 0,
            expires_at TIMESTAMP NULL,
            archived_at TIMESTAMP NULL,
            archive_reason VARCHAR(160) NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    $db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS original_filename VARCHAR(255)");
    $db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS file_type VARCHAR(120)");
    $db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS file_size BIGINT DEFAULT 0");
    $db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");
    $db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS archived_at TIMESTAMP NULL");
    $db->exec("ALTER TABLE mentorship_resources ADD COLUMN IF NOT EXISTS archive_reason VARCHAR(160) NULL");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_mentorship_resources_active ON mentorship_resources (mentor_id, archived_at, expires_at, created_at)");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_tasks (
            task_id SERIAL PRIMARY KEY,
            mentor_id INT NOT NULL,
            mentee_id INT NOT NULL,
            task_name VARCHAR(255) NOT NULL,
            description TEXT,
            deadline TIMESTAMP NULL,
            status VARCHAR(20) DEFAULT 'pending' CHECK (status IN ('pending', 'completed')),
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE,
            FOREIGN KEY (mentee_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_resource_visibility (
            id SERIAL PRIMARY KEY,
            resource_id INT NOT NULL,
            user_id INT NOT NULL,
            FOREIGN KEY (resource_id) REFERENCES mentorship_resources(resource_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )
    ");
    $db->exec("CREATE UNIQUE INDEX IF NOT EXISTS idx_mentorship_resource_visibility_unique ON mentorship_resource_visibility (resource_id, user_id)");
}

$user_id = (int)$_SESSION['user_id'];
$view = $_GET['view'] ?? 'mentee';
$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    ensureFreeMentorshipTables($db);
    ensureReadableMentorshipResourceStorage($db);
    (new RetentionMaintenance($db))->runIfDue(7);

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
        $stmt->execute([$user_id]);
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
              AND (
                  NOT EXISTS (
                      SELECT 1 FROM mentorship_resource_visibility mrv_all
                      WHERE mrv_all.resource_id = mr.resource_id
                  )
                  OR EXISTS (
                      SELECT 1 FROM mentorship_resource_visibility mrv_user
                      WHERE mrv_user.resource_id = mr.resource_id
                        AND mrv_user.user_id = ?
                  )
              )
            ORDER BY mr.created_at DESC
        ");
        $stmt->execute([$user_id, $user_id, $user_id, $user_id]);
    }

    $resources = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo json_encode(['success' => true, 'resources' => $resources]);
} catch (Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'Database Error: ' . $e->getMessage()]);
}
