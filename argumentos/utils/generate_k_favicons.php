<?php
declare(strict_types=1);

$root = dirname(__DIR__, 2);
$brand_dir = $root . DIRECTORY_SEPARATOR . 'recursos' . DIRECTORY_SEPARATOR . 'images' . DIRECTORY_SEPARATOR . 'marca';

if (!is_dir($brand_dir)) {
    mkdir($brand_dir, 0775, true);
}

function make_k_icon(int $size, string $path): void
{
    $image = imagecreatetruecolor($size, $size);
    imagealphablending($image, true);
    imagesavealpha($image, true);

    $bg = imagecolorallocate($image, 9, 14, 27);
    $accent = imagecolorallocate($image, 0, 214, 198);
    $gold = imagecolorallocate($image, 255, 171, 35);
    $white = imagecolorallocate($image, 255, 255, 255);

    imagefilledrectangle($image, 0, 0, $size, $size, $bg);

    $radius = max(2, (int)round($size * 0.16));
    $border = max(1, (int)round($size * 0.055));
    for ($i = 0; $i < $border; $i++) {
        imagerectangle($image, $i, $i, $size - 1 - $i, $size - 1 - $i, $accent);
    }

    imagefilledellipse($image, (int)round($size * 0.82), (int)round($size * 0.18), $radius, $radius, $gold);

    $font_candidates = [
        'C:\\Windows\\Fonts\\arialbd.ttf',
        'C:\\Windows\\Fonts\\segoeuib.ttf',
        'C:\\Windows\\Fonts\\calibrib.ttf',
    ];
    $font = null;
    foreach ($font_candidates as $candidate) {
        if (is_file($candidate)) {
            $font = $candidate;
            break;
        }
    }

    if ($font !== null) {
        $font_size = (int)round($size * 0.68);
        $box = imagettfbbox($font_size, 0, $font, 'K');
        $text_width = $box[2] - $box[0];
        $text_height = $box[1] - $box[7];
        $x = (int)round(($size - $text_width) / 2 - $box[0]);
        $y = (int)round(($size - $text_height) / 2 + $text_height - ($size * 0.02));
        imagettftext($image, $font_size, 0, $x, $y, $white, $font, 'K');
    } else {
        imagestring($image, 5, (int)round($size * 0.34), (int)round($size * 0.28), 'K', $white);
    }

    imagepng($image, $path);
    imagedestroy($image);
}

function png_to_ico(string $png_path, string $ico_path): void
{
    $png_data = file_get_contents($png_path);
    if ($png_data === false) {
        throw new RuntimeException('Could not read PNG for ICO generation.');
    }

    $width = 32;
    $height = 32;
    $header = pack('vvv', 0, 1, 1);
    $directory = pack('CCCCvvVV', $width, $height, 0, 0, 1, 32, strlen($png_data), 6 + 16);
    file_put_contents($ico_path, $header . $directory . $png_data);
}

$sizes = [
    16 => $brand_dir . DIRECTORY_SEPARATOR . 'favicon-k-16x16.png',
    32 => $brand_dir . DIRECTORY_SEPARATOR . 'favicon-k-32x32.png',
    180 => $brand_dir . DIRECTORY_SEPARATOR . 'apple-touch-icon-k.png',
    192 => $brand_dir . DIRECTORY_SEPARATOR . 'favicon-k-192x192.png',
    512 => $brand_dir . DIRECTORY_SEPARATOR . 'favicon-k-512x512.png',
];

foreach ($sizes as $size => $path) {
    make_k_icon($size, $path);
}

png_to_ico($brand_dir . DIRECTORY_SEPARATOR . 'favicon-k-32x32.png', $root . DIRECTORY_SEPARATOR . 'favicon-k.ico');

echo "K favicons generated.\n";
