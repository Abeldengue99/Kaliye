<?php
/**
 * interface_programacao/user/get_my_profile.php - Carregamento integral do perfil editavel.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessao expirada.']);
    exit();
}

require_once '../../configuracoes/base_dados.php';
$db = (new Database())->getConnection();

$id = (int)$_SESSION['user_id'];

function ensureReadableProfileColumns(PDO $db): void {
    $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
    $columns = [
        'profile_pic' => 'TEXT',
        'bio' => 'TEXT',
        'location' => 'VARCHAR(160)',
        'specialization_tags' => 'TEXT',
        'linkedin_url' => 'VARCHAR(255)',
        'website_url' => 'VARCHAR(255)',
        'gender' => 'VARCHAR(30)',
        'academic_level' => 'VARCHAR(160)',
        'phone' => 'VARCHAR(40)',
        'birth_date' => 'DATE',
        'institution' => 'VARCHAR(180)',
        'organization' => 'VARCHAR(180)',
        'focus_areas' => 'TEXT',
        'experience_summary' => 'TEXT'
    ];

    foreach ($columns as $column => $definition) {
        try {
            if ($driver === 'pgsql') {
                $db->exec("ALTER TABLE users ADD COLUMN IF NOT EXISTS {$column} {$definition}");
            } else {
                $db->exec("ALTER TABLE users ADD COLUMN {$column} {$definition}");
            }
        } catch (Throwable $e) {}
    }
}

function normalizeProfilePic(?string $profilePic, string $userType, string $mentorshipStatus): string {
    $profilePic = trim((string)$profilePic);
    if ($profilePic !== '' && $profilePic !== 'default_profile.png') {
        if (preg_match('#^(https?://|carregamentos/|recursos/)#', $profilePic)) {
            return $profilePic;
        }
        return 'carregamentos/profiles/' . $profilePic;
    }

    return getUserAvatarUrl($userType, $mentorshipStatus);
}

try {
    require_once '../../inclusoes/auth_check.php';
    ensureReadableProfileColumns($db);

    $sql = "SELECT full_name, profile_pic, bio, location, specialization_tags,
                   linkedin_url, website_url, email, phone, user_type, academic_level,
                   is_verified, verification_status, gender, mentorship_status, created_at,
                   birth_date, institution, organization, focus_areas, experience_summary
            FROM users WHERE user_id = ?";
    $stmt = $db->prepare($sql);
    $stmt->execute([$id]);
    $user = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$user) {
        echo json_encode(['success' => false, 'message' => 'Utilizador nao encontrado.']);
        exit();
    }

    $stmt_conn = $db->prepare("SELECT COUNT(*) FROM user_connections WHERE (user_id_1 = ? OR user_id_2 = ?) AND status = 'accepted'");
    $stmt_conn->execute([$id, $id]);
    $total_connections = $stmt_conn->fetchColumn();

    $stmt_proj = $db->prepare("SELECT COUNT(*) FROM projects WHERE owner_id = ?");
    $stmt_proj->execute([$id]);
    $total_projects = $stmt_proj->fetchColumn();

    $skills_str = $user['specialization_tags'] ?? '';
    $skills_list = !empty($skills_str) ? array_map('trim', explode(',', $skills_str)) : [];
    try {
        $stmt_skills = $db->prepare("SELECT s.name FROM skills s JOIN user_skills us ON s.skill_id = us.skill_id WHERE us.user_id = ? ORDER BY s.name");
        $stmt_skills->execute([$id]);
        $db_skills = $stmt_skills->fetchAll(PDO::FETCH_COLUMN);
        $skills_list = array_values(array_unique(array_filter(array_merge($skills_list, $db_skills))));
        $skills_str = implode(', ', $skills_list);
    } catch (Throwable $e) {}
    $total_skills = count($skills_list);

    $expertises = [];
    try {
        $stmt_exp = $db->prepare("
            SELECT ue.title, ue.description, ue.proficiency_level, ue.is_primary, ka.name AS area_name
            FROM user_expertises ue
            LEFT JOIN knowledge_areas ka ON ue.area_id = ka.area_id
            WHERE ue.user_id = ?
            ORDER BY ue.is_primary DESC, ue.proficiency_level DESC
        ");
        $stmt_exp->execute([$id]);
        $expertises = $stmt_exp->fetchAll(PDO::FETCH_ASSOC);
    } catch (Throwable $e) {}

    $focus_areas = trim((string)($user['focus_areas'] ?? ''));
    if ($focus_areas === '' && $expertises) {
        $focus_areas = implode(', ', array_values(array_filter(array_map(function ($exp) {
            return $exp['area_name'] ?? '';
        }, $expertises))));
    }

    $avg_rating = "0.00";
    try {
        $stmt_rate = $db->prepare("SELECT AVG(rating) FROM user_reviews WHERE mentor_id = ?");
        $stmt_rate->execute([$id]);
        $avg_rating = number_format($stmt_rate->fetchColumn() ?: 0, 2);
    } catch (Throwable $e) {}

    $userType = $user['user_type'] ?? 'student';
    $mentorshipStatus = $user['mentorship_status'] ?? 'unsubmitted';
    $profile_pic = normalizeProfilePic($user['profile_pic'] ?? '', $userType, $mentorshipStatus);

    echo json_encode([
        'success' => true,
        'data' => [
            'name' => $user['full_name'],
            'email' => $user['email'] ?? '',
            'phone' => $user['phone'] ?? '',
            'user_type' => $userType,
            'level' => $user['academic_level'] ?? '',
            'gender' => $user['gender'] ?? 'none',
            'birth_date' => $user['birth_date'] ?? '',
            'institution' => $user['institution'] ?? '',
            'organization' => $user['organization'] ?? '',
            'focus_areas' => $focus_areas,
            'experience_summary' => $user['experience_summary'] ?? '',
            'is_verified' => (($user['verification_status'] ?? 'unsubmitted') === 'verified'),
            'email_verified' => in_array($user['is_verified'] ?? false, [true, 1, '1', 't'], true),
            'verification_status' => $user['verification_status'] ?? 'unsubmitted',
            'mentorship_status' => $mentorshipStatus,
            'member_since' => $user['created_at'] ?? null,
            'avatar' => $profile_pic,
            'bio' => $user['bio'] ?? '',
            'location' => $user['location'] ?? '',
            'skills_str' => $skills_str,
            'skills_list' => $skills_list,
            'expertises' => $expertises,
            'linkedin' => $user['linkedin_url'] ?? '',
            'instagram' => $user['website_url'] ?? '',
            'stats' => [
                'connections' => (int)$total_connections,
                'projects' => (int)$total_projects,
                'skills' => (int)$total_skills,
                'rating' => $avg_rating
            ]
        ]
    ]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Erro interno SQL: ' . $e->getMessage()]);
}
?>
