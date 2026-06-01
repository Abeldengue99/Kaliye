<?php
// servicos/mentorship/add_mentor_resource.php
session_start();
require_once __DIR__ . '/../../configuracoes/base_dados.php';
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/Security.php';
require_once __DIR__ . '/../../inclusoes/RetentionMaintenance.php';
require_once __DIR__ . '/../../inclusoes/free_mentorship_schema.php';

header('Content-Type: application/json');

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'error' => 'User not authenticated']);
    exit;
}

if (!canActAsMentor()) {
    echo json_encode(['success' => false, 'error' => 'Apenas mentores aprovados podem enviar materiais.']);
    exit;
}

function ensureMentorshipResourceStorage(PDO $db): void {
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

function mentorshipTableExists(PDO $db, string $table): bool {
    $stmt = $db->prepare("SELECT EXISTS (SELECT 1 FROM information_schema.tables WHERE table_schema = 'public' AND table_name = ?)");
    $stmt->execute([$table]);
    return (bool)$stmt->fetchColumn();
}

function getActiveMenteeIdsForMentor(PDO $db, int $mentorId): array {
    $parts = [
        "SELECT DISTINCT ms.participant_id AS user_id
         FROM mentorship_slots ms
         WHERE ms.mentor_id = ? AND ms.participant_id IS NOT NULL",
        "SELECT DISTINCT m.mentee_id AS user_id
         FROM mentorships m
         WHERE m.mentor_id = ? AND m.status = 'active'",
    ];
    $params = [$mentorId, $mentorId];

    if (mentorshipTableExists($db, 'mentorship_tasks')) {
        $parts[] = "SELECT DISTINCT mt.mentee_id AS user_id FROM mentorship_tasks mt WHERE mt.mentor_id = ?";
        $params[] = $mentorId;
    }

    $stmt = $db->prepare(implode(' UNION ', $parts));
    $stmt->execute($params);

    return array_values(array_unique(array_map('intval', array_column($stmt->fetchAll(PDO::FETCH_ASSOC), 'user_id'))));
}

$mentor_id = (int)$_SESSION['user_id'];
$title = trim((string)($_POST['title'] ?? ''));
$description = trim((string)($_POST['description'] ?? ''));
$type = $_POST['resource_type'] ?? 'file';
$type = in_array($type, ['file', 'link'], true) ? $type : 'file';

if ($title === '') {
    echo json_encode(['success' => false, 'error' => 'Informe o titulo do material.']);
    exit;
}

$file_url = trim((string)($_POST['link_url'] ?? ''));
$original_filename = null;
$file_type = $type === 'link' ? 'link' : null;
$file_size = 0;
$stored_path = null;

if ($type === 'link' && !filter_var($file_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'error' => 'Informe um link valido para o material.']);
    exit;
}

if ($type === 'file') {
    if (!isset($_FILES['file']) || ($_FILES['file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        echo json_encode(['success' => false, 'error' => 'Selecione um ficheiro valido para subir.']);
        exit;
    }

    $stored = Security::storeUploadedFile(
        $_FILES['file'],
        __DIR__ . '/../../carregamentos/resources/' . $mentor_id,
        'carregamentos/resources/' . $mentor_id,
        [
            'application/pdf' => 'pdf',
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/webp' => 'webp',
            'text/plain' => 'txt',
            'text/csv' => 'csv',
            'application/csv' => 'csv',
            'application/vnd.ms-excel' => 'xls',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet' => 'xlsx',
            'application/vnd.ms-powerpoint' => 'ppt',
            'application/vnd.openxmlformats-officedocument.presentationml.presentation' => 'pptx',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document' => 'docx',
            'application/msword' => 'doc',
        ],
        20 * 1024 * 1024,
        'resource'
    );

    if (!$stored['ok']) {
        echo json_encode(['success' => false, 'error' => $stored['error']]);
        exit;
    }

    $file_url = $stored['path'];
    $stored_path = __DIR__ . '/../../' . $stored['path'];
    $original_filename = basename((string)($_FILES['file']['name'] ?? $stored['filename']));
    $file_type = $stored['mime'];
    $file_size = (int)$stored['size'];
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    ensureFreeMentorshipTables($db);
    ensureMentorshipResourceStorage($db);
    (new RetentionMaintenance($db))->runIfDue(7);

    $db->beginTransaction();

    $stmt = $db->prepare("
        INSERT INTO mentorship_resources
            (mentor_id, title, description, resource_type, file_url, original_filename, file_type, file_size, expires_at)
        VALUES
            (?, ?, ?, ?, ?, ?, ?, ?, NOW() + (7 * INTERVAL '1 day'))
        RETURNING resource_id
    ");
    $stmt->execute([
        $mentor_id,
        mb_substr($title, 0, 255),
        $description,
        $type,
        $file_url,
        $original_filename,
        $file_type,
        $file_size,
    ]);
    $resource_id = (int)$stmt->fetchColumn();

    $active_mentee_ids = getActiveMenteeIdsForMentor($db, $mentor_id);
    $mentee_ids = array_values(array_filter(array_map('intval', (array)($_POST['mentee_ids'] ?? []))));

    if (!empty($mentee_ids)) {
        $mentee_ids = array_values(array_intersect($mentee_ids, $active_mentee_ids));
        $dist_stmt = $db->prepare("
            INSERT INTO mentorship_resource_visibility (resource_id, user_id)
            VALUES (?, ?)
            ON CONFLICT (resource_id, user_id) DO NOTHING
        ");
        foreach ($mentee_ids as $sid) {
            $dist_stmt->execute([$resource_id, $sid]);
        }
    } else {
        $mentee_ids = $active_mentee_ids;
    }

    if (!empty($mentee_ids)) {
        $mentor_name_stmt = $db->prepare("SELECT full_name FROM users WHERE user_id = ?");
        $mentor_name_stmt->execute([$mentor_id]);
        $mentor_name = $mentor_name_stmt->fetchColumn() ?: 'O teu mentor';

        $notif_stmt = $db->prepare("
            INSERT INTO notifications (user_id, sender_id, title, content, type, link)
            VALUES (?, ?, 'Novo Material Partilhado', ?, 'mentorship_resource', 'paginas/mentoria/mentorship.php?view=mentee&tab=resources')
        ");
        $notif_content = $mentor_name . ' partilhou um novo material contigo: "' . $title . '". Clica para aceder.';
        foreach ($mentee_ids as $sid) {
            $notif_stmt->execute([$sid, $mentor_id, $notif_content]);
        }
    }

    $db->commit();
    echo json_encode(['success' => true, 'message' => 'Material guardado e partilhado com sucesso!']);
} catch (Throwable $e) {
    if ($db->inTransaction()) {
        $db->rollBack();
    }
    if ($stored_path && is_file($stored_path)) {
        @unlink($stored_path);
    }
    echo json_encode(['success' => false, 'error' => 'Database Error: ' . $e->getMessage()]);
}
