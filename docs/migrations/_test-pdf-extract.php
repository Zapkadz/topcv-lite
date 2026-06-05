<?php

require_once __DIR__ . '/../../includes/services/PdfTextExtractor.php';
require_once __DIR__ . '/../../includes/cv_import_rules.php';

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
    $len = function_exists('mb_strlen') ? mb_strlen($result['text']) : strlen($result['text']);
    echo "text_len=" . (string) $len . "\n";
    echo "preview=" . substr((string) $result['text'], 0, 200) . "\n";
}

