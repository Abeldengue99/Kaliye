<?php
/**
 * inclusoes/ProjectMediaHelper.php
 * Helper para capas de projectos, usando imagem real quando existe
 * e uma capa SVG contextual quando o projecto ainda não tem upload.
 */

class ProjectMediaHelper {
    public static function getCover($project, $base_url) {
        if (!empty($project['image_url'])) {
            return $base_url . $project['image_url'];
        }

        return self::generateDynamicCover($project['title'] ?? 'Projecto sem Titulo');
    }

    private static function generateDynamicCover($title) {
        $displayTitle = htmlspecialchars(self::truncateTitle($title, 28), ENT_QUOTES, 'UTF-8');
        $theme = self::resolveCoverTheme($title);

        $svg = '
        <svg width="800" height="600" viewBox="0 0 800 600" fill="none" xmlns="http://www.w3.org/2000/svg">
            <defs>
                <linearGradient id="bg" x1="0" y1="0" x2="800" y2="600" gradientUnits="userSpaceOnUse">
                    <stop offset="0" stop-color="'.$theme['bg1'].'"/>
                    <stop offset="0.52" stop-color="'.$theme['bg2'].'"/>
                    <stop offset="1" stop-color="'.$theme['bg3'].'"/>
                </linearGradient>
                <radialGradient id="glow" cx="50%" cy="50%" r="65%">
                    <stop offset="0" stop-color="'.$theme['accent'].'" stop-opacity="0.55"/>
                    <stop offset="1" stop-color="'.$theme['accent'].'" stop-opacity="0"/>
                </radialGradient>
                <filter id="softBlur"><feGaussianBlur stdDeviation="34"/></filter>
                <filter id="shadow" x="-20%" y="-20%" width="140%" height="140%">
                    <feDropShadow dx="0" dy="16" stdDeviation="18" flood-color="#000000" flood-opacity="0.38"/>
                </filter>
                <pattern id="dotGrid" width="34" height="34" patternUnits="userSpaceOnUse">
                    <circle cx="2" cy="2" r="2" fill="#ffffff" fill-opacity="0.12"/>
                </pattern>
            </defs>

            <rect width="800" height="600" rx="34" fill="url(#bg)"/>
            <rect width="800" height="600" fill="url(#dotGrid)" opacity="0.55"/>
            <circle cx="144" cy="112" r="190" fill="url(#glow)" filter="url(#softBlur)"/>
            <circle cx="690" cy="480" r="210" fill="'.$theme['warm'].'" fill-opacity="0.20" filter="url(#softBlur)"/>

            <path d="M-20 455 C130 385 195 515 340 448 C495 376 555 250 820 300 L820 640 L-20 640 Z" fill="#020617" fill-opacity="0.30"/>
            <path d="M540 70 L736 70 L736 266 L540 266 Z" stroke="#ffffff" stroke-opacity="0.12" stroke-width="2"/>
            <path d="M566 96 L710 240 M710 96 L566 240" stroke="#ffffff" stroke-opacity="0.08" stroke-width="2"/>

            '.$theme['art'].'

            <rect x="86" y="352" width="628" height="116" rx="26" fill="#020617" fill-opacity="0.34" stroke="#ffffff" stroke-opacity="0.10" filter="url(#shadow)"/>
            <text x="400" y="423" text-anchor="middle" fill="#ffffff" style="font-family: Arial, Helvetica, sans-serif; font-weight: 900; font-size: 40px; letter-spacing: 1px; text-transform: uppercase;">
                '.$displayTitle.'
            </text>

            <rect x="86" y="74" width="150" height="42" rx="21" fill="'.$theme['accent'].'" fill-opacity="0.92"/>
            <text x="161" y="101" text-anchor="middle" fill="#020617" style="font-family: Arial, Helvetica, sans-serif; font-weight: 900; font-size: 15px; letter-spacing: 1.5px;">
                KALIYE
            </text>
            <text x="400" y="532" text-anchor="middle" fill="#ffffff" fill-opacity="0.34" style="font-family: Arial, Helvetica, sans-serif; font-weight: 800; font-size: 13px; letter-spacing: 4px;">
                PROJECT SPOTLIGHT
            </text>
        </svg>
        ';

        return 'data:image/svg+xml;base64,' . base64_encode(trim($svg));
    }

    private static function resolveCoverTheme($title) {
        $text = mb_strtolower((string)$title, 'UTF-8');

        if (preg_match('/bio|agri|fertiliz|terra|campo|plant|eco|verde|organ/u', $text)) {
            return [
                'bg1' => '#123d1f',
                'bg2' => '#1f6b2b',
                'bg3' => '#082616',
                'accent' => '#7ee787',
                'warm' => '#f7c948',
                'art' => '<path d="M292 270 C228 220 242 138 322 108 C422 70 520 142 512 238 C506 318 390 344 292 270 Z" fill="#7ee787" fill-opacity="0.20"/>
                          <path d="M386 300 C394 224 426 172 494 128" stroke="#d9f99d" stroke-opacity="0.70" stroke-width="12" stroke-linecap="round"/>
                          <path d="M392 244 C338 220 302 180 286 132" stroke="#d9f99d" stroke-opacity="0.45" stroke-width="10" stroke-linecap="round"/>
                          <path d="M424 210 C470 212 512 196 558 164" stroke="#d9f99d" stroke-opacity="0.45" stroke-width="10" stroke-linecap="round"/>'
            ];
        }

        if (preg_match('/saude|saúde|med|clin|telemed|consulta|kikuia|farm/u', $text)) {
            return [
                'bg1' => '#123b7a',
                'bg2' => '#23306f',
                'bg3' => '#32164f',
                'accent' => '#38bdf8',
                'warm' => '#a78bfa',
                'art' => '<circle cx="400" cy="205" r="92" fill="#38bdf8" fill-opacity="0.18"/>
                          <rect x="374" y="124" width="52" height="162" rx="20" fill="#ffffff" fill-opacity="0.72"/>
                          <rect x="319" y="179" width="162" height="52" rx="20" fill="#ffffff" fill-opacity="0.72"/>
                          <path d="M250 298 C310 262 350 304 400 286 C470 260 516 220 590 252" stroke="#38bdf8" stroke-opacity="0.68" stroke-width="9" stroke-linecap="round"/>'
            ];
        }

        if (preg_match('/recicl|plast|lixo|ambiente|sustent|circular/u', $text)) {
            return [
                'bg1' => '#5c2b10',
                'bg2' => '#6a4d00',
                'bg3' => '#1f3d17',
                'accent' => '#22c55e',
                'warm' => '#f59e0b',
                'art' => '<path d="M356 122 L430 122 L402 78" stroke="#bbf7d0" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0.72"/>
                          <path d="M430 122 C506 154 528 234 482 292" stroke="#bbf7d0" stroke-width="16" stroke-linecap="round" fill="none" opacity="0.72"/>
                          <path d="M480 292 L438 292 L460 332" stroke="#bbf7d0" stroke-width="16" stroke-linecap="round" stroke-linejoin="round" fill="none" opacity="0.72"/>
                          <path d="M438 292 C350 326 280 270 296 190" stroke="#bbf7d0" stroke-width="16" stroke-linecap="round" fill="none" opacity="0.72"/>
                          <circle cx="400" cy="210" r="120" fill="#22c55e" fill-opacity="0.10"/>'
            ];
        }

        if (preg_match('/app|tech|digital|software|web|ia|ai|dados|data/u', $text)) {
            return [
                'bg1' => '#14213d',
                'bg2' => '#1d2671',
                'bg3' => '#35155d',
                'accent' => '#60a5fa',
                'warm' => '#f7941d',
                'art' => '<rect x="282" y="110" width="236" height="160" rx="30" fill="#ffffff" fill-opacity="0.13" stroke="#93c5fd" stroke-opacity="0.45"/>
                          <circle cx="340" cy="166" r="18" fill="#60a5fa" fill-opacity="0.72"/>
                          <circle cx="400" cy="166" r="18" fill="#a78bfa" fill-opacity="0.72"/>
                          <circle cx="460" cy="166" r="18" fill="#f7941d" fill-opacity="0.72"/>
                          <path d="M320 228 H480" stroke="#ffffff" stroke-opacity="0.45" stroke-width="12" stroke-linecap="round"/>
                          <path d="M270 310 H530" stroke="#60a5fa" stroke-opacity="0.55" stroke-width="8" stroke-linecap="round" stroke-dasharray="18 18"/>'
            ];
        }

        return [
            'bg1' => '#172033',
            'bg2' => '#243b55',
            'bg3' => '#111827',
            'accent' => '#f7941d',
            'warm' => '#38bdf8',
            'art' => '<path d="M288 284 L400 94 L512 284 H458 L400 184 L342 284 H288 Z" fill="#f7941d" fill-opacity="0.26"/>
                      <circle cx="400" cy="210" r="118" stroke="#ffffff" stroke-opacity="0.18" stroke-width="14"/>
                      <path d="M256 302 H544" stroke="#ffffff" stroke-opacity="0.24" stroke-width="12" stroke-linecap="round"/>'
        ];
    }

    private static function truncateTitle($text, $limit) {
        $text = trim((string)$text);
        if (mb_strlen($text, 'UTF-8') > $limit) {
            return mb_substr($text, 0, $limit, 'UTF-8') . '...';
        }
        return $text;
    }
}
