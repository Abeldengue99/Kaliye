<?php
/**
 * Shared KALIYE favicon/meta block.
 *
 * Auto-detects the correct base_url relative to the project root
 * if no explicit value is provided (or if './' fallback is used).
 *
 * @param string $base_url Relative base URL for the current page.
 */
if (!function_exists('renderKaliyeFavicons')) {
    function renderKaliyeFavicons(string $base_url = './'): void
    {
        $root = dirname(__DIR__, 2);

        // Auto-detect base_url when caller passes './' (default/fallback)
        // or when the passed value clearly doesn't resolve to the project root.
        if ($base_url === './' || $base_url === './/' || $base_url === '') {
            // Find the actual executing script (the page requested)
            $caller_file = $_SERVER['SCRIPT_FILENAME'] ?? __FILE__;
            $caller_dir = str_replace('\\', '/', dirname($caller_file));
            $root_norm = str_replace('\\', '/', $root);

            // Calculate how many levels deep the caller is from root
            $relative = ltrim(str_replace($root_norm, '', $caller_dir), '/');
            if ($relative === '' || $relative === $caller_dir) {
                // Caller is at root or outside project (unlikely)
                $base_url = './';
            } else {
                $depth = substr_count($relative, '/') + 1;
                $base_url = str_repeat('../', $depth);
            }
        }

        $base = rtrim($base_url, '/') . '/';
        $icon_version = '2';
        $favicon_file = $root . DIRECTORY_SEPARATOR . 'recursos' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'marca' . DIRECTORY_SEPARATOR . 'favicon-16x16.ico';

        if (is_file($favicon_file)) {
            $icon_version = (string)filemtime($favicon_file);
        }
        ?>
    <link rel="shortcut icon" href="<?php echo $base; ?>recursos/images/marca/favicon-16x16.ico?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo $base; ?>recursos/images/marca/favicon-16x16.ico?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $base; ?>recursos/images/marca/favicon-192x192.png?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $base; ?>recursos/images/marca/favicon-512x512.png?v=<?php echo $icon_version; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base; ?>recursos/images/marca/apple-touch-icon.png?v=<?php echo $icon_version; ?>">
    <link rel="manifest" href="<?php echo $base; ?>manifest.json?v=<?php echo $icon_version; ?>">
    <script>
        (function () {
            var iconHref = '<?php echo $base; ?>recursos/images/marca/favicon-16x16.ico?v=<?php echo $icon_version; ?>';
            var pngHref = '<?php echo $base; ?>recursos/images/marca/favicon-192x192.png?v=<?php echo $icon_version; ?>';
            document.querySelectorAll('link[rel*="icon"]').forEach(function (link) {
                link.parentNode.removeChild(link);
            });
            [
                { rel: 'icon', type: 'image/x-icon', href: iconHref },
                { rel: 'shortcut icon', type: 'image/x-icon', href: iconHref },
                { rel: 'icon', type: 'image/png', sizes: '192x192', href: pngHref }
            ].forEach(function (attrs) {
                var link = document.createElement('link');
                Object.keys(attrs).forEach(function (key) {
                    link.setAttribute(key, attrs[key]);
                });
                document.head.appendChild(link);
            });
        })();
    </script>
    <meta name="application-name" content="KALIYE">
    <meta name="apple-mobile-web-app-title" content="KALIYE">
    <meta name="theme-color" content="#f7941d">
        <?php
    }
}
