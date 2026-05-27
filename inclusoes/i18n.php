<?php
// inclusoes/i18n.php
// Sistema simples de internacionalizacao para a plataforma.

function __($key) {
    global $translations;
    return $translations[$key] ?? $key;
}

$supported_languages = ['pt', 'en', 'fr', 'es'];
$lang = $_SESSION['lang'] ?? ($_COOKIE['kaliye_lang'] ?? 'pt');
$lang = in_array($lang, $supported_languages, true) ? $lang : 'pt';

if (isset($_GET['lang'])) {
    $requested_lang = (string)$_GET['lang'];
    $lang = in_array($requested_lang, $supported_languages, true) ? $requested_lang : 'pt';
    $_SESSION['lang'] = $lang;
    setcookie('kaliye_lang', $lang, [
        'expires' => time() + 60 * 60 * 24 * 365,
        'path' => '/',
        'secure' => !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off',
        'httponly' => false,
        'samesite' => 'Lax',
    ]);
}

$json_path = __DIR__ . "/../idiomas/{$lang}.json";

if (file_exists($json_path)) {
    $translations = json_decode(file_get_contents($json_path), true) ?: [];
} else {
    $translations = [];
}
