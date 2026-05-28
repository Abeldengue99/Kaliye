<?php
/**
 * fix_mojibake.php - Corrige dupla codificacao UTF-8 (mojibake)
 * Usa bytes hexadecimais para evitar problemas de encoding no próprio script
 */

$directory = new RecursiveDirectoryIterator('.', RecursiveDirectoryIterator::SKIP_DOTS);
$iterator = new RecursiveIteratorIterator($directory);
$regex = new RegexIterator($iterator, '/^.+\.(php|js|json|md|html)$/i', RecursiveRegexIterator::GET_MATCH);

// Mojibake patterns: hex bytes da sequencia errada => caractere correcto em hex
// Quando UTF-8 e lido como Latin-1 e recodificado, os 2 bytes de cada char viram 4 bytes
$mojibake_map = [
    "\xC3\x83\xC2\xA3" => "\xC3\xA3", // a-til -> ã
    "\xC3\x83\xC2\xA1" => "\xC3\xA1", // a-agudo -> á
    "\xC3\x83\xC2\xA0" => "\xC3\xA0", // a-grave -> à
    "\xC3\x83\xC2\xA2" => "\xC3\xA2", // a-circunflexo -> â
    "\xC3\x83\xC2\xA7" => "\xC3\xA7", // c-cedilha -> ç
    "\xC3\x83\xC2\xA9" => "\xC3\xA9", // e-agudo -> é
    "\xC3\x83\xC2\xAA" => "\xC3\xAA", // e-circunflexo -> ê
    "\xC3\x83\xC2\xA8" => "\xC3\xA8", // e-grave -> è
    "\xC3\x83\xC2\xAD" => "\xC3\xAD", // i-agudo -> í
    "\xC3\x83\xC2\xB3" => "\xC3\xB3", // o-agudo -> ó
    "\xC3\x83\xC2\xB4" => "\xC3\xB4", // o-circunflexo -> ô
    "\xC3\x83\xC2\xB5" => "\xC3\xB5", // o-til -> õ
    "\xC3\x83\xC2\xBA" => "\xC3\xBA", // u-agudo -> ú
    "\xC3\x83\xC2\xBC" => "\xC3\xBC", // u-trema -> ü
    "\xC3\x83\xC2\xB1" => "\xC3\xB1", // n-til -> ñ
];

// Também precisamos do mapa visual para os que ja aparecem como texto Mojibake no ficheiro
// (quando o próprio ficheiro PHP ja esta em UTF-8)
$text_map = [];
$text_map[chr(0xC3).chr(0x83).chr(0xC2).chr(0xA3)] = chr(0xC3).chr(0xA3);

$totalFixed = 0;

foreach ($regex as $file) {
    $filePath = $file[0];
    
    if (strpos($filePath, 'node_modules') !== false || 
        strpos($filePath, '.git') !== false ||
        strpos($filePath, 'fix_mojibake') !== false ||
        strpos($filePath, 'fix_everything') !== false ||
        strpos($filePath, 'fix_all_safely') !== false) {
        continue;
    }

    $content = file_get_contents($filePath);
    if ($content === false) continue;

    $original = $content;

    // Metodo 1: Tentar converter de UTF-8 duplo para UTF-8 simples
    // Detectar se ha sequencias de 4 bytes que são mojibake
    foreach ($mojibake_map as $wrong => $correct) {
        $content = str_replace($wrong, $correct, $content);
    }

    if ($original !== $content) {
        file_put_contents($filePath, $content);
        $totalFixed++;
        echo "Corrigido: $filePath\n";
    }
}

echo "\n=== Total de ficheiros corrigidos (mojibake hex): $totalFixed ===\n";
?>
