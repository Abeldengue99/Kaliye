<?php
/**
 * interface_programacao/user/update_profile.php - Gravacao integral do dossier do utilizador.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
requireValidCSRFTokenJson();
$db = (new Database())->getConnection();

$current_user_id = (int)$_SESSION['user_id'];

function ensureProfileColumns(PDO $db): void {
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
        } catch (Throwable $e) {
            // Existing column or unavailable DDL permission. The update below will still report real failures.
        }
    }
}

function cleanProfileText(string $value, int $maxLength): string {
    $value = trim(preg_replace('/\s+/', ' ', $value));
    return mb_substr($value, 0, $maxLength);
}

function nullableText(?string $value): ?string {
    $value = trim((string)$value);
    return $value === '' ? null : $value;
}

function storeProfileAvatar(int $userId): ?string {
    if (empty($_FILES['profile_pic']) || ($_FILES['profile_pic']['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }

    if ($_FILES['profile_pic']['error'] !== UPLOAD_ERR_OK) {
        throw new RuntimeException('Não foi possível carregar o avatar.');
    }

    if ($_FILES['profile_pic']['size'] > 4 * 1024 * 1024) {
        throw new RuntimeException('O avatar deve ter no maximo 4MB.');
    }

    $tmp = $_FILES['profile_pic']['tmp_name'];
    $info = @getimagesize($tmp);
    $allowed = [
        IMAGETYPE_JPEG => 'jpg',
        IMAGETYPE_PNG => 'png',
        IMAGETYPE_WEBP => 'webp'
    ];

    if (!$info || !isset($allowed[$info[2]])) {
        throw new RuntimeException('Use uma imagem JPG, PNG ou WEBP para o avatar.');
    }

    $targetDir = __DIR__ . '/../../carregamentos/profiles';
    if (!is_dir($targetDir) && !mkdir($targetDir, 0775, true)) {
        throw new RuntimeException('Não foi possível preparar a pasta de avatars.');
    }

    $fileName = 'profile_' . $userId . '_' . time() . '.' . $allowed[$info[2]];
    $targetPath = $targetDir . DIRECTORY_SEPARATOR . $fileName;

    if (!move_uploaded_file($tmp, $targetPath)) {
        throw new RuntimeException('Não foi possível guardar o avatar.');
    }

    return 'carregamentos/profiles/' . $fileName;
}

function syncUserSkills(PDO $db, int $userId, string $skills): void {
    $items = array_values(array_unique(array_filter(array_map('trim', explode(',', $skills)))));
    if (!$items) {
        return;
    }

    try {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $db->prepare("DELETE FROM user_skills WHERE user_id = ?")->execute([$userId]);

        foreach ($items as $skillName) {
            $skillName = mb_substr($skillName, 0, 80);
            $stmt = $db->prepare("SELECT skill_id FROM skills WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt->execute([$skillName]);
            $skillId = $stmt->fetchColumn();

            if (!$skillId) {
                if ($driver === 'pgsql') {
                    $insert = $db->prepare("INSERT INTO skills (name) VALUES (?) RETURNING skill_id");
                    $insert->execute([$skillName]);
                    $skillId = $insert->fetchColumn();
                } else {
                    $insert = $db->prepare("INSERT INTO skills (name) VALUES (?)");
                    $insert->execute([$skillName]);
                    $skillId = $db->lastInsertId();
                }
            }

            $db->prepare("INSERT INTO user_skills (user_id, skill_id, type) VALUES (?, ?, 'profile')")->execute([$userId, $skillId]);
        }
    } catch (Throwable $e) {
        // specialization_tags remains the source of truth if normalized tables are unavailable.
    }
}

function syncUserFocusAreas(PDO $db, int $userId, string $focusAreas, string $experienceSummary): void {
    $items = array_values(array_unique(array_filter(array_map('trim', explode(',', $focusAreas)))));
    if (!$items) {
        return;
    }

    try {
        $driver = $db->getAttribute(PDO::ATTR_DRIVER_NAME);
        $db->prepare("DELETE FROM user_expertises WHERE user_id = ?")->execute([$userId]);

        foreach ($items as $index => $areaName) {
            $areaName = mb_substr($areaName, 0, 100);
            $stmt = $db->prepare("SELECT area_id FROM knowledge_areas WHERE LOWER(name) = LOWER(?) LIMIT 1");
            $stmt->execute([$areaName]);
            $areaId = $stmt->fetchColumn();

            if (!$areaId) {
                if ($driver === 'pgsql') {
                    $insert = $db->prepare("INSERT INTO knowledge_areas (name, category, created_at) VALUES (?, 'profile', NOW()) RETURNING area_id");
                    $insert->execute([$areaName]);
                    $areaId = $insert->fetchColumn();
                } else {
                    $insert = $db->prepare("INSERT INTO knowledge_areas (name, category, created_at) VALUES (?, 'profile', NOW())");
                    $insert->execute([$areaName]);
                    $areaId = $db->lastInsertId();
                }
            }

            $title = $index === 0 ? 'Area principal' : 'Area de foco';
            try {
                $db->prepare("
                    INSERT INTO user_expertises (user_id, area_id, title, description, proficiency_level, is_primary, years_experience)
                    VALUES (?, ?, ?, ?, ?, ?, 0)
                ")->execute([$userId, $areaId, $title, $experienceSummary ?: $areaName, 'intermediate', $index === 0]);
            } catch (Throwable $e) {
                $db->prepare("
                    INSERT INTO user_expertises (user_id, area_id, description, proficiency_level, is_primary, years_experience)
                    VALUES (?, ?, ?, ?, ?, 0)
                ")->execute([$userId, $areaId, $experienceSummary ?: $areaName, 'intermediate', $index === 0]);
            }
        }
    } catch (Throwable $e) {
        // focus_areas keeps the visible profile data even if normalized tables differ.
    }
}

ensureProfileColumns($db);

$full_name = cleanProfileText($_POST['full_name'] ?? '', 180);
$academic_level = cleanProfileText($_POST['level'] ?? '', 160);
$bio = trim($_POST['bio'] ?? '');
$location = cleanProfileText($_POST['location'] ?? '', 160);
$skills = cleanProfileText($_POST['skills'] ?? '', 2000);
$linkedin = nullableText($_POST['linkedin'] ?? '');
$instagram = nullableText($_POST['instagram'] ?? '');
$gender = trim($_POST['gender'] ?? 'none');
$phone = nullableText(cleanProfileText($_POST['phone'] ?? '', 40));
$birth_date = nullableText($_POST['birth_date'] ?? '');
$institution = cleanProfileText($_POST['institution'] ?? '', 180);
$organization = cleanProfileText($_POST['organization'] ?? '', 180);
$focus_areas = cleanProfileText($_POST['focus_areas'] ?? '', 2000);
$experience_summary = trim($_POST['experience_summary'] ?? '');

if ($full_name === '' || mb_strlen($full_name) < 3) {
    echo json_encode(['success' => false, 'message' => 'Informe o nome completo.']);
    exit();
}

if ($academic_level === '' || $location === '' || $skills === '' || $bio === '' || $focus_areas === '') {
    echo json_encode(['success' => false, 'message' => 'Preencha nome, nivel/cargo, localizacao, skills, areas de foco e biografia.']);
    exit();
}

if (mb_strlen($bio) < 40) {
    echo json_encode(['success' => false, 'message' => 'A biografia deve ter pelo menos 40 caracteres.']);
    exit();
}

if ($birth_date !== null && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $birth_date)) {
    echo json_encode(['success' => false, 'message' => 'Data de nascimento invalida.']);
    exit();
}

foreach (['LinkedIn' => $linkedin, 'Website/Instagram' => $instagram] as $label => $url) {
    if ($url !== null && !filter_var($url, FILTER_VALIDATE_URL)) {
        echo json_encode(['success' => false, 'message' => "O campo {$label} deve conter uma URL valida."]);
        exit();
    }
}

if (!in_array($gender, ['none', 'masculino', 'feminino', 'outro'], true)) {
    $gender = 'none';
}

$bio = mb_substr($bio, 0, 3000);
$experience_summary = mb_substr($experience_summary, 0, 3000);

try {
    $profile_pic_path = storeProfileAvatar($current_user_id);
    $profile_pic_query_part = $profile_pic_path ? ", profile_pic = :profile_pic" : "";
    $photo_status = $profile_pic_path ? "Avatar atualizado." : "Avatar mantido.";

    $sql = "UPDATE users SET
                full_name = :full_name,
                academic_level = :academic_level,
                bio = :bio,
                location = :location,
                specialization_tags = :skills,
                linkedin_url = :linkedin,
                website_url = :instagram,
                gender = :gender,
                phone = :phone,
                birth_date = :birth_date,
                institution = :institution,
                organization = :organization,
                focus_areas = :focus_areas,
                experience_summary = :experience_summary
                $profile_pic_query_part
            WHERE user_id = :user_id";

    $params = [
        'full_name' => $full_name,
        'academic_level' => $academic_level,
        'bio' => $bio,
        'location' => $location,
        'skills' => $skills,
        'linkedin' => $linkedin,
        'instagram' => $instagram,
        'gender' => $gender,
        'phone' => $phone,
        'birth_date' => $birth_date,
        'institution' => nullableText($institution),
        'organization' => nullableText($organization),
        'focus_areas' => $focus_areas,
        'experience_summary' => nullableText($experience_summary),
        'user_id' => $current_user_id
    ];

    if ($profile_pic_path) {
        $params['profile_pic'] = $profile_pic_path;
    }

    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    syncUserSkills($db, $current_user_id, $skills);
    syncUserFocusAreas($db, $current_user_id, $focus_areas, $experience_summary);

    $_SESSION['full_name'] = $full_name;
    if ($profile_pic_path) {
        $_SESSION['profile_pic'] = $profile_pic_path;
    }

    echo json_encode(['success' => true, 'message' => 'Dossier KALIYE atualizado com sucesso. ' . $photo_status]);
} catch (RuntimeException $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
} catch (Exception $e) {
    error_log('update_profile error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao salvar dossier. Tente novamente.']);
}
?>
