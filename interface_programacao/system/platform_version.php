<?php
header('Content-Type: application/json');
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');

require_once __DIR__ . '/../../inclusoes/app_version.php';

echo json_encode([
    'success' => true,
    'version' => AKSANTI_PLATFORM_VERSION,
    'title' => AKSANTI_PLATFORM_RELEASE_TITLE,
    'note' => AKSANTI_PLATFORM_RELEASE_NOTE,
    'checked_at' => gmdate('c'),
]);
