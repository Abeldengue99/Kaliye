<?php
function aksantiAssetVersion(string $relativePath): string
{
    $fullPath = __DIR__ . '/../' . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, ltrim($relativePath, '/\\'));

    return file_exists($fullPath) ? (string)filemtime($fullPath) : '1';
}
