<?php
// servicos/mentorship/add_mentor_notice.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem enviar avisos.']);
    exit;
}

$mentor_id = $_SESSION['user_id'];
$title = $_POST['title'] ?? null;
$content = $_POST['message'] ?? '';
$importance = $_POST['importance'] ?? 'normal';

if (!$title) {
    echo json_encode(['success' => false, 'error' => 'Title is required']);
    exit;
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $db->beginTransaction();

    $db->exec("CREATE TABLE IF NOT EXISTS mentorship_notices (
        notice_id SERIAL PRIMARY KEY,
        mentor_id INT NOT NULL,
        title VARCHAR(255) NOT NULL,
        content TEXT,
        importance VARCHAR(10) DEFAULT 'normal' CHECK (importance IN ('normal', 'high')),
        expires_at TIMESTAMP NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (mentor_id) REFERENCES users(user_id) ON DELETE CASCADE
    )");
    
    // Add expires_at column if it doesn't exist
    $db->exec("ALTER TABLE mentorship_notices ADD COLUMN IF NOT EXISTS expires_at TIMESTAMP NULL");

    $query = "INSERT INTO mentorship_notices (mentor_id, title, content, importance, expires_at) VALUES (?, ?, ?, ?, NOW() + (7 * INTERVAL '1 day'))";
    $stmt = $db->prepare($query);
    $stmt->execute([$mentor_id, $title, $content, $importance]);
    $notice_id = $db->lastInsertId();

    // Handle selected students (Visibility)
    $mentee_ids = $_POST['mentee_ids'] ?? [];

    // Normalise: ensure all IDs are integers and non-zero
    $mentee_ids = array_filter(array_map('intval', (array)$mentee_ids));

    if (!empty($mentee_ids)) {
        $db->exec("CREATE TABLE IF NOT EXISTS mentorship_notice_visibility (
            id SERIAL PRIMARY KEY,
            notice_id INT NOT NULL,
            user_id INT NOT NULL,
            FOREIGN KEY (notice_id) REFERENCES mentorship_notices(notice_id) ON DELETE CASCADE,
            FOREIGN KEY (user_id) REFERENCES users(user_id) ON DELETE CASCADE
        )");

        $dist_stmt = $db->prepare("INSERT INTO mentorship_notice_visibility (notice_id, user_id) VALUES (?, ?)");
        foreach ($mentee_ids as $sid) {
            $dist_stmt->execute([$notice_id, $sid]);
        }
    } else {
        // No specific mentees selected: notify ALL active mentees of this mentor
        $all_stmt = $db->prepare("
            SELECT DISTINCT ms.participant_id AS user_id
            FROM mentorship_slots ms
            WHERE ms.mentor_id = ? AND ms.participant_id IS NOT NULL
            UNION
            SELECT DISTINCT mentee_id AS user_id FROM mentorship_tasks WHERE mentor_id = ?
            UNION
            SELECT DISTINCT mentee_id AS user_id FROM mentorships WHERE mentor_id = ? AND status = 'active'
        ");
        $all_stmt->execute([$mentor_id, $mentor_id, $mentor_id]);
        $mentee_ids = array_column($all_stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id');
    }

    // CORRECÇÃO BUG 2: Notificar cada mentorando sobre o novo aviso
    if (!empty($mentee_ids)) {
        $mentor_name_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $mentor_name_stmt->execute([$mentor_id]);
        $mentor_name = $mentor_name_stmt->fetchColumn() ?: 'O teu mentor';

        $notif_icon = ($importance === 'high') ? '🔴 ' : '📢 ';
        $notif_title = ($importance === 'high') ? 'Aviso Urgente do Mentor' : 'Novo Aviso do Mentor';

        $notif_stmt = $db->prepare("
            INSERT INTO notifications (user_id, sender_id, title, content, type, link)
            VALUES (?, ?, ?, ?, 'mentorship_notice', 'paginas/mentoria/mentorship.php?view=mentee&tab=notices')
        ");
        $notif_content = $notif_icon . $mentor_name . ' publicou um aviso: "' . $title . '". Clica para ler.';
        foreach ($mentee_ids as $sid) {
            $notif_stmt->execute([$sid, $mentor_id, $notif_title, $notif_content]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Notice posted successfully']);

} catch (PDOException $e) {
    if ($db->inTransaction()) $db->rollBack();
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
?>
