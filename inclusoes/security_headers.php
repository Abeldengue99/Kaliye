<?php
// includes/security_headers.php

// 1. Prevent Clickjacking (X-Frame-Options)
header("X-Frame-Options: SAMEORIGIN");

// 2. Prevent MIME type sniffing (X-Content-Type-Options)
header("X-Content-Type-Options: nosniff");

// 3. Enable XSS protection in legacy browsers
header("X-XSS-Protection: 1; mode=block");

// 4. Content Security Policy (Basic)
// This helps prevent XSS and data injection
// Allow Tailwind CDN and data: for fonts where necessary to avoid blocking external fonts
header("Content-Security-Policy: default-src 'self' https://aksanti.xyz https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://fonts.gstatic.com https://unpkg.com; img-src 'self' data: https:; style-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://fonts.googleapis.com https://unpkg.com https://cdn.tailwindcss.com; script-src 'self' 'unsafe-inline' https://cdnjs.cloudflare.com https://cdn.jsdelivr.net https://unpkg.com https://cdn.tailwindcss.com; font-src 'self' data: https://cdnjs.cloudflare.com https://fonts.gstatic.com; frame-src 'none';");

// 5. Strict Transport Security (HSTS) - Only if using HTTPS
if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
    header("Strict-Transport-Security: max-age=31536000; includeSubDomains; preload");
}

// 6. Referrer Policy
header("Referrer-Policy: strict-origin-when-cross-origin");

// 7. Prevent caching of sensitive pages
function setNoCache() {
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
}

// 8. Session Cookie Security (Only if session hasn't started yet)
if (session_status() === PHP_SESSION_NONE) {
    ini_set('session.cookie_httponly', 1);
    if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on') {
        ini_set('session.cookie_secure', 1);
    }
    ini_set('session.use_only_cookies', 1);
    ini_set('session.cookie_samesite', 'Lax');
}
