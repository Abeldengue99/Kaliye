<?php
/**
 * Exporta subscritores da newsletter em CSV ou PDF.
 */
session_start();
$base_url = '../../';

require_once $base_url . 'inclusoes/auth_check.php';
if (!isAdmin() || !hasPermission('ads')) {
    header('Location: ' . $base_url . 'index.php');
    exit();
}

require_once $base_url . 'configuracoes/base_dados.php';
$db = (new Database())->getConnection();

$format = strtolower((string)($_GET['format'] ?? 'csv'));
$stmt = $db->query("SELECT id, COALESCE(NULLIF(name, ''), 'N/A') AS name, email, subscribed_at FROM newsletter_subscribers ORDER BY subscribed_at DESC");
$subscribers = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];

if ($format === 'pdf') {
    outputNewsletterPdf($subscribers);
}

outputNewsletterCsv($subscribers);

function outputNewsletterCsv(array $subscribers): void
{
    $filename = 'kaliye_newsletter_subscritores_' . date('Y-m-d_H-i') . '.csv';

    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Pragma: no-cache');
    header('Expires: 0');

    echo "\xEF\xBB\xBF";
    $out = fopen('php://output', 'w');
    fputcsv($out, ['ID', 'Nome', 'E-mail', 'Data de Subscricao'], ';');

    foreach ($subscribers as $sub) {
        fputcsv($out, [
            $sub['id'],
            $sub['name'],
            $sub['email'],
            formatNewsletterDate($sub['subscribed_at'] ?? null),
        ], ';');
    }

    fclose($out);
    exit();
}

function outputNewsletterPdf(array $subscribers): void
{
    $filename = 'kaliye_newsletter_subscritores_' . date('Y-m-d_H-i') . '.pdf';
    $lines = [
        'KALIYE - Relatório de Subscritores da Newsletter',
        'Gerado em: ' . date('d/m/Y H:i'),
        'Total de subscritores: ' . count($subscribers),
        str_repeat('-', 88),
        padPdfText('ID', 6) . padPdfText('Nome', 28) . padPdfText('E-mail', 36) . 'Data',
        str_repeat('-', 88),
    ];

    foreach ($subscribers as $sub) {
        $lines[] = padPdfText((string)$sub['id'], 6)
            . padPdfText(trimPdfText((string)$sub['name'], 26), 28)
            . padPdfText(trimPdfText((string)$sub['email'], 34), 36)
            . formatNewsletterDate($sub['subscribed_at'] ?? null);
    }

    if (count($subscribers) === 0) {
        $lines[] = 'Ainda não existem subscritores registados.';
    }

    $pdf = buildSimplePdf($lines);

    header('Content-Type: application/pdf');
    header('Content-Disposition: attachment; filename=' . $filename);
    header('Content-Length: ' . strlen($pdf));
    echo $pdf;
    exit();
}

function formatNewsletterDate($value): string
{
    if (empty($value)) {
        return '-';
    }

    $timestamp = strtotime((string)$value);
    return $timestamp ? date('d/m/Y H:i', $timestamp) : '-';
}

function padPdfText(string $value, int $length): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return str_pad(substr($value, 0, $length - 1), $length, ' ');
}

function trimPdfText(string $value, int $length): string
{
    $value = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $value);
    return strlen($value) > $length ? substr($value, 0, $length - 3) . '...' : $value;
}

function escapePdfText(string $value): string
{
    return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $value);
}

function buildSimplePdf(array $lines): string
{
    $linesPerPage = 48;
    $pages = array_chunk($lines, $linesPerPage);
    $objects = [
        '<< /Type /Catalog /Pages 2 0 R >>',
        '',
        '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>',
    ];

    $pageObjectNumbers = [];
    foreach ($pages as $index => $pageLines) {
        $pageObjectNumber = count($objects) + 1;
        $contentObjectNumber = $pageObjectNumber + 1;
        $pageObjectNumbers[] = $pageObjectNumber . ' 0 R';

        $objects[] = "<< /Type /Page /Parent 2 0 R /MediaBox [0 0 595 842] /Resources << /Font << /F1 3 0 R >> >> /Contents {$contentObjectNumber} 0 R >>";
        $content = "BT\n/F1 9 Tf\n50 790 Td\n12 TL\n";
        foreach ($pageLines as $line) {
            $content .= '(' . escapePdfText($line) . ") Tj\nT*\n";
        }
        $content .= "ET\n";
        $objects[] = '<< /Length ' . strlen($content) . " >>\nstream\n" . $content . "endstream";
    }

    $objects[1] = '<< /Type /Pages /Kids [' . implode(' ', $pageObjectNumbers) . '] /Count ' . count($pageObjectNumbers) . ' >>';

    $pdf = "%PDF-1.4\n";
    $offsets = [0];
    foreach ($objects as $index => $object) {
        $offsets[] = strlen($pdf);
        $objectNumber = $index + 1;
        $pdf .= $objectNumber . " 0 obj\n" . $object . "\nendobj\n";
    }

    $xrefOffset = strlen($pdf);
    $pdf .= "xref\n0 " . (count($objects) + 1) . "\n";
    $pdf .= "0000000000 65535 f \n";
    for ($i = 1; $i <= count($objects); $i++) {
        $pdf .= sprintf("%010d 00000 n \n", $offsets[$i]);
    }

    $pdf .= "trailer\n<< /Size " . (count($objects) + 1) . " /Root 1 0 R >>\n";
    $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

    return $pdf;
}


