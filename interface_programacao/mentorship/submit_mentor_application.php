<?php
// servicos/mentorship/submit_mentor_application.php
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/Security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'Metodo invalido.']);
    exit();
}

requireValidCSRFTokenJson();

$user_id = (int)$_SESSION['user_id'];
$specialty = trim($_POST['specialty'] ?? '');
$experience_years = (int)($_POST['experience_years'] ?? 0);
$linkedin_url = trim($_POST['linkedin_url'] ?? '');

if ($specialty === '' || $linkedin_url === '') {
    echo json_encode(['success' => false, 'message' => 'Todos os campos são obrigatorios.']);
    exit();
}

if (!filter_var($linkedin_url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Informe um link valido para o LinkedIn.']);
    exit();
}

if (!isset($_FILES['cv_file']) || ($_FILES['cv_file']['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => 'Erro no upload do CV.']);
    exit();
}

$stored_cv = Security::storeUploadedFile(
    $_FILES['cv_file'],
    __DIR__ . '/../../carregamentos/cvs/' . $user_id,
    'carregamentos/cvs/' . $user_id,
    ['application/pdf' => 'pdf'],
    12 * 1024 * 1024,
    'cv'
);

if (!$stored_cv['ok']) {
    echo json_encode(['success' => false, 'message' => $stored_cv['error']]);
    exit();
}

$database = new Database();
/** @var PDO $db */
$db = $database->getConnection();

try {
    $check = $db->prepare("SELECT mentorship_status FROM users WHERE user_id = ?");
    $check->execute([$user_id]);
    $status = $check->fetchColumn();

    if ($status === 'approved') {
        echo json_encode(['success' => false, 'message' => 'Ja e um mentor aprovado.']);
        exit();
    }

    $query = "UPDATE users SET
                mentorship_status = 'pending',
                specialization_tags = :specialty,
                years_of_experience = :exp,
                linkedin_url = :linkedin,
                cv_path = :cv
              WHERE user_id = :user_id";

    $stmt = $db->prepare($query);
    $stmt->execute([
        ':specialty' => $specialty,
        ':exp' => $experience_years,
        ':linkedin' => $linkedin_url,
        ':cv' => $stored_cv['path'],
        ':user_id' => $user_id
    ]);

    $_SESSION['mentorship_status'] = 'pending';

    echo json_encode(['success' => true, 'message' => 'Candidatura submetida com sucesso!']);
} catch (Exception $e) {
    error_log('submit_mentor_application error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar candidatura. Tente novamente.']);
}
