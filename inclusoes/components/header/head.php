<?php
/**
 * inclusoes/components/header/head.php
 * Head section and global JS variables (Versão Restaurada).
 */
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars((string)($lang ?? 'pt')); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? $page_title . " | KALIYE" : "KALIYE"; ?></title>
    
    <!-- Favicons -->
    <?php
    require_once dirname(__DIR__) . '/favicon.php';
    renderKaliyeFavicons($base_url);
    ?>

    <!-- PWA -->
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">

    <!-- Performance: Preconnect & Prefetch -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link rel="dns-prefetch" href="https://cdnjs.cloudflare.com">
    <link rel="dns-prefetch" href="https://unpkg.com">
    <link rel="dns-prefetch" href="https://cdn.jsdelivr.net">

    <!-- Fonts: Optimized Loading -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet" media="print" onload="this.media='all'">
    
    <!-- Core External CSS -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="https://fonts.googleapis.com/css2?family=Material+Symbols+Outlined:opsz,wght,FILL,GRAD@48,400,0,0" />
    <link rel="stylesheet" href="https://unpkg.com/aos@next/dist/aos.css" />
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.css" />

    
    <!-- Bibliotecas de Estilo (Frameworks & Design System) -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            corePlugins: { preflight: false }
        }
    </script>
    
    <!-- Design System (Versão Controlada para Cache) -->
    <?php
    $asset_version = static function (string $path): string {
        $full_path = dirname(__DIR__, 3) . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $path);
        return file_exists($full_path) ? (string)filemtime($full_path) : (string)time();
    };
    ?>
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/critical.min.css?v=<?php echo $asset_version('recursos/css/critical.min.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/aksanti-design-system.css?v=<?php echo $asset_version('recursos/css/aksanti-design-system.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/style.css?v=<?php echo $asset_version('recursos/css/style.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/animations-2026.css?v=<?php echo $asset_version('recursos/css/animations-2026.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/dashboard-aksanti-novo.css?v=<?php echo $asset_version('recursos/css/dashboard-aksanti-novo.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/dashboard-aksanti-elite.css?v=<?php echo $asset_version('recursos/css/dashboard-aksanti-elite.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/dashboard-premium.css?v=<?php echo $asset_version('recursos/css/dashboard-premium.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/platform-core.css?v=<?php echo $asset_version('recursos/css/platform-core.css'); ?>">
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/user-preferences.css?v=<?php echo $asset_version('recursos/css/user-preferences.css'); ?>">
    <!-- Folha de Estilos de Projectos Essencial (Feed 3 Colunas) -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/pages/projects.css?v=<?php echo $asset_version('recursos/css/pages/projects.css'); ?>">
    <!-- Layout Premium do Feed Index (pós-login) — tickers de anúncios, grid 3col, cards -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/pages/index_feed.css?v=<?php echo $asset_version('recursos/css/pages/index_feed.css'); ?>">
    
    <!-- Otimização Mobile Elite (Bottom Nav & Responsividade) -->
    <link rel="stylesheet" href="<?php echo $base_url; ?>recursos/css/mobile-elite.css?v=<?php echo $asset_version('recursos/css/mobile-elite.css'); ?>">
    
    <!-- Bibliotecas de Scripts Essenciais -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script src="https://cdn.jsdelivr.net/npm/swiper@11/swiper-bundle.min.js" defer></script>
    <script src="<?php echo $base_url; ?>recursos/js/user-preferences.js?v=<?php echo $asset_version('recursos/js/user-preferences.js'); ?>" defer></script>

    <!-- Ponto Central de Variáveis JS -->
    <script>
        // Esta lógica resolve a URL absoluta de forma infalível
        window.BASE_URL = (function() {
            const link = document.createElement('a');
            link.href = '<?php echo $base_url; ?>'; // O PHP passa '../../' ou './'
            const resolved = link.href.endsWith('/') ? link.href : link.href + '/';
            return resolved;
        })();
        var BASE_URL = window.BASE_URL;
        window.CSRF_TOKEN = '<?php echo htmlspecialchars(generateCSRFToken(), ENT_QUOTES, 'UTF-8'); ?>';

        (function installCsrfFetchGuard() {
            if (!window.fetch || window.__aksantiCsrfFetchGuardInstalled) return;
            window.__aksantiCsrfFetchGuardInstalled = true;
            const nativeFetch = window.fetch.bind(window);
            window.fetch = function(resource, options) {
                options = options || {};
                const method = String(options.method || (resource && resource.method) || 'GET').toUpperCase();
                if (!['GET', 'HEAD', 'OPTIONS'].includes(method) && window.CSRF_TOKEN) {
                    const url = typeof resource === 'string' ? resource : (resource && resource.url) || '';
                    const target = new URL(url, window.location.href);
                    if (target.origin === window.location.origin) {
                        const headers = new Headers(options.headers || {});
                        if (!headers.has('X-CSRF-Token')) headers.set('X-CSRF-Token', window.CSRF_TOKEN);
                        options.headers = headers;
                    }
                }
                return nativeFetch(resource, options);
            };
        })();

        // Garantir que a verificação de KYC é um booleano JS
        window.IS_KEY_VERIFIED = <?php echo (isset($is_verified) && $is_verified) ? 'true' : 'false'; ?>;
        window.IS_MENTOR = <?php echo (isMentor() || (isset($_SESSION['user_type']) && $_SESSION['user_type'] === 'mentor')) ? 'true' : 'false'; ?>;
        
        // Função Global de Restrição
        window.enforceKYC = function(e) {
            const isVerified = window.IS_KEY_VERIFIED === true || 
                               window.IS_KEY_VERIFIED === 'true';

            if (isVerified === false) {
                 if(e && e.preventDefault) e.preventDefault();
                 Swal.fire({
                    title: 'Verificação Necessária',
                    text: 'A tua conta ainda não foi verificada para publicar projectos ou investir.',
                    icon: 'lock',
                    confirmButtonText: 'Verificar Agora',
                    background: '#0d1628',
                    color: '#fff',
                    customClass: { popup: 'glass' }
                }).then((result) => {
                    if (result.isConfirmed) {
                        if (typeof openKYCModal === 'function') {
                            openKYCModal();
                        } else {
                            window.location.href = BASE_URL + 'paginas/social/profile.php?verify_required=1';
                        }
                    }
                });
                return false;
            }
            return true;
        };
    </script>
    <style>
        body {
            margin: 0;
            background: var(--bg-0, #050a15);
            color: var(--text-primary, #fff);
            font-family: 'Inter', sans-serif;
            overflow-x: hidden;
            padding-top: var(--nav-height, 80px); /* Compensa o Navbar Fixo */
        }
    </style>
</head>
