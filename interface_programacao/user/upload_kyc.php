<?php
/**
 * interface_programacao/user/upload_kyc.php
 * Endpoint Atómico para Receção de Identidade e Dados de Perfil (Mentor/Investidor).
 */
session_start();
require_once '../../configuracoes/base_dados.php';
require_once '../../inclusoes/auth_check.php';
require_once '../../inclusoes/Security.php';

header('Content-Type: application/json');

if (!isLoggedIn()) {
    echo json_encode(['success' => false, 'message' => 'Sessão expirada.']);
    exit();
}

if (!Security::requireFreshAuth(900)) {
    echo json_encode(['success' => false, 'message' => 'Por seguranca, inicia sessao novamente antes de enviar documentos sensiveis.']);
    exit();
}
requireValidCSRFTokenJson();

$user_id = $_SESSION['user_id'];
$utype   = $_SESSION['user_type'] ?? 'student';
$database = new Database();
$db = $database->getConnection();

// 1. Validar Status Atual
$check = $db->prepare("SELECT verification_status FROM users WHERE user_id = ?");
$check->execute([$user_id]);
if ($check->fetchColumn() === 'verified') {
    echo json_encode(['success' => false, 'message' => 'A sua conta já está totalmente verificada.']);
    exit();
}

// 2. Coleta de Dados Base (Identidade)
$id_number = $_POST['id_number'] ?? '';
if (empty($id_number)) {
    echo json_encode(['success' => false, 'message' => 'O número do BI/Passaporte é obrigatório.']);
    exit();
}

$upload_dir = __DIR__ . '/../../carregamentos/kyc/';
$upload_rel = 'carregamentos/kyc';
$document_mimes = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'application/pdf' => 'pdf',
];

$required_files = ['bi_front', 'bi_back', 'selfie'];

// Se for Mentor, Curriculum é obrigatório
if ($utype === 'mentor') $required_files[] = 'cv_file';

// Se for Estudante Universitário com MENTORIA, Histórico e CV são obrigatórios
$is_peer_candidate = false;
if ($utype === 'univ_student') {
    $pCheck = $db->prepare("SELECT is_peer_mentor FROM users WHERE user_id = ?");
    $pCheck->execute([$user_id]);
    $is_peer_candidate = $pCheck->fetchColumn();
    if ($is_peer_candidate) {
        $required_files[] = 'transcript_file';
        $required_files[] = 'cv_file';
    }
}

// Se for Investidor, Comprovativo é opcional mas recomendado
if ($utype === 'investor' && isset($_FILES['income_proof'])) $required_files[] = 'income_proof';

$paths = [];
foreach ($required_files as $f_key) {
    if (!isset($_FILES[$f_key]) || $_FILES[$f_key]['error'] !== UPLOAD_ERR_OK) {
        if ($f_key === 'income_proof') continue; // Opcional
        echo json_encode(['success' => false, 'message' => "Ficheiro obrigatório em falta: $f_key"]);
        exit();
    }

    $stored = Security::storeUploadedFile(
        $_FILES[$f_key],
        $upload_dir,
        $upload_rel,
        $document_mimes,
        12 * 1024 * 1024,
        $user_id . '_' . $f_key
    );

    if (!$stored['ok']) {
        echo json_encode(['success' => false, 'message' => $stored['error']]);
        exit();
    }

    $paths[$f_key] = $stored['path'];
}

// 3. Preparação da Query Atómica (Update Users)
try {
    $db->beginTransaction();

    $sql = "UPDATE users SET 
                id_number = :idn, 
                bi_front_path = :bif, 
                bi_back_path = :bib, 
                selfie_path = :slf, 
                verification_status = 'pending',
                submitted_at = NOW()";

    $params = [
        ':idn' => $id_number,
        ':bif' => $paths['bi_front'],
        ':bib' => $paths['bi_back'],
        ':slf' => $paths['selfie'],
        ':user_id' => $user_id
    ];

    // Lógica Específica de Mentor Profissional
    if ($utype === 'mentor') {
        $sql .= ", specialty = :spec, experience_years = :exp, linkedin_url = :link, cv_path = :cv, mentorship_status = 'pending'";
        $params[':spec'] = $_POST['specialty'] ?? '';
        $params[':exp']  = $_POST['experience_years'] ?? 0;
        $params[':link'] = $_POST['linkedin_url'] ?? '';
        $params[':cv']   = $paths['cv_file'] ?? null;
    }

    // Lógica Específica de Peer Mentor (Estudante)
    if ($utype === 'univ_student' && $is_peer_candidate) {
        $sql .= ", academic_transcript_path = :trans, cv_path = :cv, mentorship_status = 'pending', linkedin_url = :link, specialty = :spec";
        $params[':trans'] = $paths['transcript_file'] ?? null;
        $params[':cv']    = $paths['cv_file'] ?? null;
        $params[':link']  = $_POST['linkedin_url'] ?? '';
        $params[':spec']  = $_POST['specialty'] ?? '';
    }

    // Lógica Específica de Investidor
    if ($utype === 'investor') {
        $sql .= ", annual_income = :inc, source_of_funds = :sof, investor_status = 'pending'";
        $params[':inc'] = $_POST['annual_income'] ?? '';
        $params[':sof'] = $_POST['source_of_funds'] ?? '';
        
        // Se enviou prova, podemos salvar em algum lugar (ex: kyc_steps ou notas admin por agora)
        if (isset($paths['income_proof'])) {
            $sql .= ", admin_notes = CONCAT(COALESCE(admin_notes,''), '\n[PROVA RENDA]: ', :proof)";
            $params[':proof'] = $paths['income_proof'];
        }
    }

    $sql .= " WHERE user_id = :user_id";
    $stmt = $db->prepare($sql);
    $stmt->execute($params);

    $db->commit();
    Security::logActivity($db, (int)$user_id, 'kyc_submitted', 'Dossier KYC enviado para revisao.', 'info');

    // Atualizar a sessão para refletir imediatamente o estado na interface
    $_SESSION['verification_status'] = 'pending';
    $_SESSION['is_verified'] = false;
    if ($utype === 'mentor' || ($utype === 'univ_student' && $is_peer_candidate)) {
        $_SESSION['mentorship_status'] = 'pending';
    }
    if ($utype === 'investor') {
        $_SESSION['investor_status'] = 'pending';
    }

    echo json_encode(['success' => true, 'message' => 'Dossiê enviado com sucesso! Aguarde a revisão da equipa Aksanti.']);

} catch (Exception $e) {
    $db->rollBack();
    error_log('upload_kyc error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Erro ao processar candidatura. Tente novamente.']);
}
