<?php
/**
 * update_project.php — MOTOR DE ATUALIZAÇÃO SINC (v2.1)
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/Security.php';

function project_upload_error_message($error_code) {
    $messages = [
        UPLOAD_ERR_INI_SIZE => 'O vídeo excede o limite configurado no servidor.',
        UPLOAD_ERR_FORM_SIZE => 'O vídeo excede o limite permitido pelo formulário.',
        UPLOAD_ERR_PARTIAL => 'O upload do vídeo ficou incompleto. Tente novamente.',
        UPLOAD_ERR_NO_FILE => 'Nenhum vídeo foi recebido.',
        UPLOAD_ERR_NO_TMP_DIR => 'A pasta temporária do servidor não está disponível.',
        UPLOAD_ERR_CANT_WRITE => 'O servidor não conseguiu gravar o vídeo.',
        UPLOAD_ERR_EXTENSION => 'Uma extensão do servidor bloqueou o upload do vídeo.'
    ];

    return $messages[$error_code] ?? 'Erro desconhecido ao receber o vídeo.';
}

function ensure_project_upload_dir(&$error_message) {
    $upload_dir = __DIR__ . '/../../carregamentos/projects/';

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0775, true) && !is_dir($upload_dir)) {
        $error_message = 'Não foi possível criar a pasta de uploads de projectos.';
        return null;
    }

    @chmod($upload_dir, 0775);
    clearstatcache(true, $upload_dir);

    $upload_dir = rtrim($upload_dir, "/\\") . DIRECTORY_SEPARATOR;
    $test_path = $upload_dir . '.write_test_' . uniqid('', true);
    $handle = @fopen($test_path, 'wb');
    if ($handle === false) {
        $error_message = 'A pasta de uploads de projectos não tem permissão de escrita.';
        return null;
    }

    fclose($handle);
    @unlink($test_path);

    return $upload_dir;
}

function save_project_video_upload($file, &$error_message) {
    if (!isset($file) || !is_array($file)) {
        $error_message = 'Nenhum vídeo foi recebido.';
        return null;
    }

    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
        $error_message = project_upload_error_message($file['error'] ?? UPLOAD_ERR_NO_FILE);
        return null;
    }

    $max_bytes = 300 * 1024 * 1024;
    if (($file['size'] ?? 0) <= 0 || $file['size'] > $max_bytes) {
        $error_message = 'O vídeo deve ter até 300 MB.';
        return null;
    }

    $client_mime = strtolower($file['type'] ?? '');
    $detected_mime = '';
    if (function_exists('finfo_open')) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        if ($finfo) {
            $detected_mime = strtolower(finfo_file($finfo, $file['tmp_name']) ?: '');
            finfo_close($finfo);
        }
    }

    $allowed_mimes = [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
        'video/mpeg' => 'mpeg',
    ];
    $mime = $detected_mime ?: $client_mime;
    if (!isset($allowed_mimes[$mime])) {
        $error_message = 'O ficheiro enviado precisa ser um vídeo.';
        return null;
    }

    $upload_dir = ensure_project_upload_dir($error_message);
    if ($upload_dir === null) {
        return null;
    }

    if (!is_dir($upload_dir) && !mkdir($upload_dir, 0755, true)) {
        $error_message = 'Não foi possível criar a pasta de uploads de projectos.';
        return null;
    }

    if (false && !is_writable($upload_dir)) {
        $error_message = 'A pasta de uploads de projectos não tem permissão de escrita.';
        return null;
    }

    $vid_name = time() . '_' . bin2hex(random_bytes(8)) . '.' . $allowed_mimes[$mime];
    $vid_path = $upload_dir . $vid_name;

    if (!is_uploaded_file($file['tmp_name']) || !move_uploaded_file($file['tmp_name'], $vid_path)) {
        $error_message = 'Falha ao guardar o vídeo no servidor.';
        return null;
    }

    return $vid_name;
}

function save_project_image_upload($file, &$error_message, $prefix = 'project_image') {
    $stored = Security::storeUploadedFile(
        $file,
        __DIR__ . '/../../carregamentos/projects',
        'carregamentos/projects',
        [
            'image/jpeg' => 'jpg',
            'image/png' => 'png',
            'image/gif' => 'gif',
            'image/webp' => 'webp',
        ],
        10 * 1024 * 1024,
        $prefix
    );

    if (!$stored['ok']) {
        $error_message = $stored['error'];
        return null;
    }

    return $stored['path'];
}

// Resposta Padrão JSON se solicitado
$is_json = isset($_POST['json']);

if ($_SERVER["REQUEST_METHOD"] != "POST") {
    if ($is_json) { echo json_encode(['success' => false, 'error' => 'Método inválido.']); exit(); }
    header("Location: ../../index.php"); exit();
}

// 1. Verificação de Sessão (Middleware)
if (!isset($_SESSION['user_id'])) {
    if ($is_json) { echo json_encode(['success' => false, 'error' => 'Sessão expirada. Por favor, faça login novamente.']); exit(); }
    header("Location: ../../autenticacao/entrar.php"); exit();
}

$project_id = $_POST['project_id'] ?? null;
$owner_id = $_SESSION['user_id'];

if (!$project_id) {
    if ($is_json) { echo json_encode(['success' => false, 'error' => 'ID do projecto não especificado.']); exit(); }
    header("Location: ../../index.php"); exit();
}

// 2. Coleta e Sanitização
$title           = filter_var($_POST['title'] ?? '', FILTER_SANITIZE_STRING);
$description     = filter_var($_POST['description'] ?? '', FILTER_SANITIZE_STRING);
$category        = filter_var($_POST['category'] ?? '', FILTER_SANITIZE_STRING);
$budget          = filter_var($_POST['budget'] ?? 0, FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
$execution_time  = filter_var($_POST['execution_time'] ?? '', FILTER_SANITIZE_STRING);
$team_size       = filter_var($_POST['team_size'] ?? 1, FILTER_SANITIZE_NUMBER_INT) ?: 1;
$project_stage   = filter_var($_POST['project_stage'] ?? 'Projecto', FILTER_SANITIZE_STRING) ?: 'Projecto';
$target_audience = filter_var($_POST['target_audience'] ?? '', FILTER_SANITIZE_STRING);
$needs_to_advance= filter_var($_POST['needs_to_advance'] ?? '', FILTER_SANITIZE_STRING);
$idea_origin     = filter_var($_POST['idea_origin'] ?? '', FILTER_SANITIZE_STRING);
$motivation      = filter_var($_POST['motivation'] ?? '', FILTER_SANITIZE_STRING);
$project_url     = filter_var($_POST['project_url'] ?? '', FILTER_SANITIZE_URL);
$funding_goal    = !empty($_POST['funding_goal']) ? filter_var($_POST['funding_goal'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 0;
$min_investment  = !empty($_POST['minimum_investment']) ? filter_var($_POST['minimum_investment'], FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) : 1000;
$campaign_end    = !empty($_POST['campaign_end_date']) ? $_POST['campaign_end_date'] : null;

$database = new Database();
$db = $database->getConnection();

try {
    // 3. Verificação de Propriedade
    $check = $db->prepare("SELECT owner_id, image_url, video_url FROM projects WHERE project_id = ?");
    $check->execute([$project_id]);
    $project = $check->fetch(PDO::FETCH_ASSOC);

    if (!$project || $project['owner_id'] != $owner_id) {
        throw new Exception("Não tens permissão para editar este projecto.");
    }

    $db->beginTransaction();

    // 4. Gestão de Ficheiros
    $image_url = $project['image_url'];
    if (isset($_FILES['project_image']) && $_FILES['project_image']['error'] == 0) {
        $image_upload_error = null;
        $project_upload_dir = ensure_project_upload_dir($image_upload_error);
        if ($project_upload_dir === null) {
            throw new Exception($image_upload_error);
        }
        $uploaded_image = save_project_image_upload($_FILES['project_image'], $image_upload_error, 'project_cover');
        if ($uploaded_image === null) {
            throw new Exception($image_upload_error);
        }
        $image_url = $uploaded_image;
    }

    $video_url = $project['video_url'];
    if (isset($_FILES['project_video']) && $_FILES['project_video']['error'] !== UPLOAD_ERR_NO_FILE) {
        $upload_error = null;
        $uploaded_video = save_project_video_upload($_FILES['project_video'], $upload_error);
        if ($uploaded_video === null) {
            throw new Exception($upload_error);
        }
        $video_url = $uploaded_video;
    }

    // 5. Update SQL (Reset Approval)
    $sql = "UPDATE projects SET 
            title = :title, description = :description, category = :category, budget_needed = :budget, 
            image_url = :image, video_url = :video, pitch_video_url = :video,
            execution_time = :execution_time, team_size = :team_size, project_stage = :project_stage,
            target_audience = :target_audience, needs_to_advance = :needs_to_advance, 
            idea_origin = :idea_origin, motivation = :motivation, project_url = :project_url,
            funding_goal = :funding_goal, minimum_investment = :min_inv, campaign_end_date = :camp_end,
            approval_status = 'pending', is_public = false
            WHERE project_id = :id AND owner_id = :owner_id";
    
    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':title' => $title, ':description' => $description, ':category' => $category, ':budget' => $budget,
        ':image' => $image_url, ':video' => $video_url,
        ':execution_time' => $execution_time, ':team_size' => $team_size, ':project_stage' => $project_stage,
        ':target_audience' => $target_audience, ':needs_to_advance' => $needs_to_advance,
        ':idea_origin' => $idea_origin, ':motivation' => $motivation, ':project_url' => $project_url,
        ':funding_goal' => $funding_goal, ':min_inv' => $min_investment, ':camp_end' => $campaign_end,
        ':id' => $project_id, ':owner_id' => $owner_id
    ]);

    // 6. Tags Sync
    if (isset($_POST['tags'])) {
        $db->prepare("DELETE FROM project_tags WHERE project_id = ?")->execute([$project_id]);
        $tags = explode(',', $_POST['tags']);
        foreach ($tags as $tag) {
            $tag = trim($tag);
            if (empty($tag)) continue;
            
            $s_stmt = $db->prepare("SELECT skill_id FROM skills WHERE name = ?");
            $s_stmt->execute([$tag]);
            $skill_id = $s_stmt->fetchColumn();
            
            if (!$skill_id) {
                $s_ins = $db->prepare("INSERT INTO skills (name) VALUES (?) RETURNING skill_id");
                $s_ins->execute([$tag]);
                $skill_id = $s_ins->fetchColumn();
            }
            $db->prepare("INSERT INTO project_tags (project_id, skill_id) VALUES (?, ?)")->execute([$project_id, $skill_id]);
        }
    }

    $db->commit();
    
    /**
     * RE-CÁLCULO DE MARKET READINESS
     * Atualiza a pontuação sempre que o projeto é editado.
     */
    require_once 'calculate_readiness.php';
    updateProjectScore($db, $project_id);
    
    if ($is_json) {
        echo json_encode(['success' => true, 'message' => 'Inovação actualizada! Passará por nova curadoria.']);
        exit();
    }
    header("Location: ../../paginas/explorar/my_projects.php?success=1");

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) $db->rollBack();
    if ($is_json) { echo json_encode(['success' => false, 'error' => $e->getMessage()]); exit(); }
    header("Location: ../../paginas/explorar/my_projects.php?error=" . urlencode($e->getMessage()));
}
