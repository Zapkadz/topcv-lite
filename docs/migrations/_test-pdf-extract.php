<?php

require_once __DIR__ . '/../../includes/services/PdfTextExtractor.php';
require_once __DIR__ . '/../../includes/cv_import_rules.php';
require_once __DIR__ . '/../../includes/cv_import_text_clean.php';

$path = $argv[1] ?? '';
if ($path === '') {
    echo "Usage: php " . basename(__FILE__) . " \"C:\\path\\to\\cv.pdf\"\n";
    exit(1);
}

$result = PdfTextExtractor::extract($path);
echo "ok=" . ($result['ok'] ? 'true' : 'false') . "\n";
if (!empty($result['message'])) {
    echo "message=" . $result['message'] . "\n";
}

if (!empty($result['text'])) {
    $raw = (string) $result['text'];
    $len = function_exists('mb_strlen') ? mb_strlen($raw) : strlen($raw);
    echo "raw_text_len=" . (string) $len . "\n";
    echo "raw_preview=" . substr($raw, 0, 600) . "\n";

    $clean = cv_import_clean_extracted_text($raw);
    echo "clean_text_len=" . (string) ($clean['clean_len'] ?? 0) . "\n";
    echo "noise_score=" . (string) ($clean['noise_score'] ?? 0) . "\n";
    echo "clean_steps=" . implode(',', (array) ($clean['steps'] ?? [])) . "\n";
    echo "clean_preview=" . substr((string) ($clean['text'] ?? ''), 0, 1200) . "\n";
}

