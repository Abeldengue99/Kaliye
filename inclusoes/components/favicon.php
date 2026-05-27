<?php
/**
 * Shared KALIYE favicon/meta block.
 *
 * @param string $base_url Relative base URL for the current page.
 */
if (!function_exists('renderKaliyeFavicons')) {
    function renderKaliyeFavicons(string $base_url = './'): void
    {
        $base = rtrim($base_url, '/') . '/';
        $root = dirname(__DIR__, 2);
        $icon_version = '1';
        $favicon_file = $root . DIRECTORY_SEPARATOR . 'favicon-k.ico';

        if (is_file($favicon_file)) {
            $icon_version = (string)filemtime($favicon_file);
        }
        ?>
    <link rel="shortcut icon" href="<?php echo $base; ?>favicon-k.ico?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/x-icon" href="<?php echo $base; ?>favicon-k.ico?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/png" sizes="32x32" href="<?php echo $base; ?>recursos/images/marca/favicon-k-32x32.png?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/png" sizes="16x16" href="<?php echo $base; ?>recursos/images/marca/favicon-k-16x16.png?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/png" sizes="192x192" href="<?php echo $base; ?>recursos/images/marca/favicon-k-192x192.png?v=<?php echo $icon_version; ?>">
    <link rel="icon" type="image/png" sizes="512x512" href="<?php echo $base; ?>recursos/images/marca/favicon-k-512x512.png?v=<?php echo $icon_version; ?>">
    <link rel="apple-touch-icon" sizes="180x180" href="<?php echo $base; ?>recursos/images/marca/apple-touch-icon-k.png?v=<?php echo $icon_version; ?>">
    <link rel="manifest" href="<?php echo $base; ?>manifest.json?v=<?php echo $icon_version; ?>">
    <script>
        (function () {
            var iconHref = '<?php echo $base; ?>favicon-k.ico?v=<?php echo $icon_version; ?>';
            var pngHref = '<?php echo $base; ?>recursos/images/marca/favicon-k-32x32.png?v=<?php echo $icon_version; ?>';
            document.querySelectorAll('link[rel*="icon"]').forEach(function (link) {
                link.parentNode.removeChild(link);
            });
            [
                { rel: 'icon', type: 'image/x-icon', href: iconHref },
                { rel: 'shortcut icon', type: 'image/x-icon', href: iconHref },
                { rel: 'icon', type: 'image/png', sizes: '32x32', href: pngHref }
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
