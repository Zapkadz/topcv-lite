<?php

require_once __DIR__ . '/../../includes/services/PdfTextExtractor.php';

$pdf = $argv[1] ?? 'uploads/cv/cv_apply_6_8_1780123295.pdf';
$out = __DIR__ . '/cv-text-test.txt';

$root = realpath(__DIR__ . '/../..');
$pdfPath = is_file($pdf) ? $pdf : ($root . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pdf));

$result = PdfTextExtractor::extract($pdfPath);
if (!$result['ok']) {
    fwrite(STDERR, $result['message'] . "\n");
    exit(1);
}

file_put_contents($out, (string) $result['text']);
echo "Created: docs/migrations/cv-text-test.txt\n";
