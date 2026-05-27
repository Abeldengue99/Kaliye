<?php
/**
 * debug_upload.php
 * Endpoint temporario para diagnosticar uploads de video.
 */
header('Content-Type: application/json; charset=utf-8');
session_start();
require_once __DIR__ . '/../../inclusoes/auth_check.php';
require_once __DIR__ . '/../../inclusoes/Security.php';

if (!isAdmin()) {
    http_response_code(403);
    echo json_encode(['success' => false, 'error' => 'Acesso negado.']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'error' => 'Metodo invalido, use POST.']);
    exit;
}

if (!isset($_FILES['project_video'])) {
    echo json_encode(['success' => false, 'error' => 'Nenhum ficheiro project_video encontrado em $_FILES.']);
    exit;
}

$file = $_FILES['project_video'];
$resp = [
    'success' => false,
    'received' => true,
    'name' => $file['name'] ?? '',
    'type' => $file['type'] ?? '',
    'size' => $file['size'] ?? 0,
    'error' => $file['error'] ?? UPLOAD_ERR_NO_FILE,
];

$stored = Security::storeUploadedFile(
    $file,
    __DIR__ . '/../../carregamentos/projects',
    'carregamentos/projects',
    [
        'video/mp4' => 'mp4',
        'video/webm' => 'webm',
        'video/ogg' => 'ogv',
        'video/quicktime' => 'mov',
        'video/x-msvideo' => 'avi',
        'video/mpeg' => 'mpeg',
    ],
    300 * 1024 * 1024,
    'debug_video'
);

if ($stored['ok']) {
    $resp['success'] = true;
    $resp['moved_to'] = $stored['path'];
    $resp['detected_mime'] = $stored['mime'];
} else {
    $resp['error'] = $stored['error'];
}

echo json_encode($resp);
exit;
