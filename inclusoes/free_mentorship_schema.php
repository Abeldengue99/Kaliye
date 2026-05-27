<?php
/**
 * Ensures the free mentorship feature has its storage tables available.
 *
 * The project currently runs on PostgreSQL. These CREATE/ALTER statements are
 * intentionally conservative and avoid foreign keys so they can repair older
 * local databases that may already have partial versions of these tables.
 */
function ensureFreeMentorshipTables(PDO $db): void
{
    static $checked = false;
    if ($checked) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS free_mentorship_requests (
            request_id SERIAL PRIMARY KEY,
            student_id INT NOT NULL,
            doubt_id INT NULL,
            selected_mentor_id INT NULL,
            title VARCHAR(255) NOT NULL,
            description TEXT NOT NULL,
            category VARCHAR(120) NULL,
            difficulty_level VARCHAR(40) NOT NULL DEFAULT 'beginner',
            estimated_duration VARCHAR(80) NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'open',
            started_at TIMESTAMP NULL,
            completed_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $requestColumns = [
        "student_id INT",
        "doubt_id INT",
        "selected_mentor_id INT",
        "title VARCHAR(255)",
        "description TEXT",
        "category VARCHAR(120)",
        "difficulty_level VARCHAR(40) DEFAULT 'beginner'",
        "estimated_duration VARCHAR(80)",
        "status VARCHAR(40) DEFAULT 'open'",
        "started_at TIMESTAMP",
        "completed_at TIMESTAMP",
        "created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
        "updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP",
    ];

    foreach ($requestColumns as $definition) {
        $db->exec("ALTER TABLE free_mentorship_requests ADD COLUMN IF NOT EXISTS $definition");
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS free_mentorship_applications (
            application_id SERIAL PRIMARY KEY,
            request_id INT NOT NULL,
            mentor_id INT NOT NULL,
            message TEXT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'pending',
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            responded_at TIMESTAMP NULL
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS free_mentorship_sessions (
            session_id SERIAL PRIMARY KEY,
            request_id INT NOT NULL,
            mentor_id INT NOT NULL,
            student_id INT NOT NULL,
            mentorship_slot_id INT NULL,
            session_date TIMESTAMP NULL,
            duration_minutes INT NOT NULL DEFAULT 0,
            meeting_link TEXT NULL,
            student_feedback TEXT NULL,
            student_rating INT NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->exec("ALTER TABLE free_mentorship_sessions ADD COLUMN IF NOT EXISTS mentorship_slot_id INT");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorships (
            mentorship_id SERIAL PRIMARY KEY,
            mentor_id INT NOT NULL,
            mentee_id INT NOT NULL,
            status VARCHAR(40) NOT NULL DEFAULT 'active',
            started_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            ended_at TIMESTAMP NULL,
            created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->exec("
        CREATE UNIQUE INDEX IF NOT EXISTS idx_mentorships_active_pair
        ON mentorships (mentor_id, mentee_id)
        WHERE status = 'active'
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS mentorship_slots (
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
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS knowledge_areas (
            area_id SERIAL PRIMARY KEY,
            name VARCHAR(100) NOT NULL,
            icon VARCHAR(50),
            color VARCHAR(20),
            category VARCHAR(50),
            popularity_score INT DEFAULT 0,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS user_expertises (
            expertise_id SERIAL PRIMARY KEY,
            user_id INTEGER NOT NULL,
            area_id INTEGER NOT NULL,
            proficiency_level VARCHAR(20) DEFAULT 'beginner',
            is_primary BOOLEAN DEFAULT FALSE,
            years_experience INTEGER DEFAULT 0,
            can_mentor BOOLEAN DEFAULT FALSE,
            description TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE(user_id, area_id)
        )
    ");

    $db->exec("ALTER TABLE knowledge_areas ADD COLUMN IF NOT EXISTS popularity_score INT DEFAULT 0");
    $db->exec("ALTER TABLE user_expertises ADD COLUMN IF NOT EXISTS can_mentor BOOLEAN DEFAULT FALSE");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS mentorship_status VARCHAR(20) DEFAULT 'unsubmitted'");
    $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS specialization_tags TEXT");

    $db->exec("
        CREATE INDEX IF NOT EXISTS idx_free_mentorship_app_request_mentor
        ON free_mentorship_applications (request_id, mentor_id)
    ");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_app_request ON free_mentorship_applications (request_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_app_mentor ON free_mentorship_applications (mentor_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_req_student ON free_mentorship_requests (student_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_req_mentor ON free_mentorship_requests (selected_mentor_id)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_req_status ON free_mentorship_requests (status)");
    $db->exec("CREATE INDEX IF NOT EXISTS idx_free_mentorship_sessions_request ON free_mentorship_sessions (request_id)");

    $checked = true;
}

function normalizeFreeMentorshipText(string $value): string
{
    $value = strtolower(trim($value));
    if (function_exists('iconv')) {
        $converted = @iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
        if ($converted !== false) {
            $value = $converted;
        }
    }

    return preg_replace('/[^a-z0-9]+/', ' ', $value) ?: '';
}

function getFreeMentorshipKeywords(array $request): array
{
    $source = implode(' ', [
        $request['category'] ?? '',
        $request['title'] ?? '',
        $request['description'] ?? '',
    ]);
    $words = preg_split('/\s+/', normalizeFreeMentorshipText($source));
    $stopWords = [
        'com', 'para', 'por', 'uma', 'uns', 'das', 'dos', 'que', 'estou', 'preciso',
        'ajuda', 'ajudar', 'duvida', 'mentoria', 'sobre', 'como', 'meu', 'minha',
        'the', 'and', 'for', 'with', 'help',
    ];

    return array_values(array_unique(array_filter($words, function ($word) use ($stopWords) {
        return strlen($word) >= 3 && !in_array($word, $stopWords, true);
    })));
}

function buildFreeMentorshipMentorEligibilitySql(string $alias = 'u'): string
{
    return "($alias.user_type IN ('mentor', 'admin', 'superadmin') OR COALESCE($alias.mentorship_status, '') = 'approved')";
}

function getEligibleFreeMentorshipMentorIds(PDO $db, array $request, int $studentId, bool $fallbackToAllMentors = false): array
{
    $keywords = getFreeMentorshipKeywords($request);
    $category = normalizeFreeMentorshipText((string)($request['category'] ?? ''));
    $eligibility = buildFreeMentorshipMentorEligibilitySql('u');
    $params = ['student_id' => $studentId];

    $scoreParts = [];
    if ($category !== '') {
        $scoreParts[] = "(CASE WHEN lower(COALESCE(ka.category, '')) = :category OR lower(COALESCE(ka.name, '')) = :category THEN 10 ELSE 0 END)";
        $scoreParts[] = "(CASE WHEN lower(COALESCE(u.specialization_tags, '')) LIKE :category_like THEN 4 ELSE 0 END)";
        $params['category'] = $category;
        $params['category_like'] = '%' . $category . '%';
    }

    foreach (array_slice($keywords, 0, 8) as $index => $keyword) {
        $param = 'kw' . $index;
        $scoreParts[] = "(CASE WHEN lower(COALESCE(ka.name, '')) LIKE :$param OR lower(COALESCE(ue.description, '')) LIKE :$param OR lower(COALESCE(u.specialization_tags, '')) LIKE :$param THEN 2 ELSE 0 END)";
        $params[$param] = '%' . $keyword . '%';
    }

    $scoreSql = $scoreParts ? implode(' + ', $scoreParts) : '0';
    $query = "
        SELECT u.user_id, MAX($scoreSql) AS match_score
        FROM users u
        LEFT JOIN user_expertises ue ON ue.user_id = u.user_id AND COALESCE(ue.can_mentor, true) = true
        LEFT JOIN knowledge_areas ka ON ka.area_id = ue.area_id
        WHERE $eligibility AND u.user_id != :student_id
        GROUP BY u.user_id
        HAVING MAX($scoreSql) > 0
        ORDER BY match_score DESC, u.user_id ASC
    ";

    $stmt = $db->prepare($query);
    $stmt->execute($params);
    $ids = array_map('intval', $stmt->fetchAll(PDO::FETCH_COLUMN));

    if (!$ids && $fallbackToAllMentors) {
        $fallback = $db->prepare("SELECT u.user_id FROM users u WHERE $eligibility AND u.user_id != ? ORDER BY u.user_id ASC");
        $fallback->execute([$studentId]);
        $ids = array_map('intval', $fallback->fetchAll(PDO::FETCH_COLUMN));
    }

    return $ids;
}

function isEligibleForFreeMentorshipRequest(PDO $db, int $mentorId, array $request): bool
{
    if ((int)$request['student_id'] === $mentorId) {
        return false;
    }

    $ids = getEligibleFreeMentorshipMentorIds($db, $request, (int)$request['student_id'], false);

    return in_array($mentorId, $ids, true);
}
