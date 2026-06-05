<?php

require_once __DIR__ . '/../../includes/cv_import_text_clean.php';

$raw = $argv[1] ?? '';
if ($raw !== '' && is_file($raw)) {
    $raw = (string) file_get_contents($raw);
} elseif ($raw === '' && is_file(__DIR__ . '/cv-text-noisy-sample.txt')) {
    $raw = (string) file_get_contents(__DIR__ . '/cv-text-noisy-sample.txt');
}
if ($raw === '') {
    echo "Usage: php " . basename(__FILE__) . " \"raw text...\"\n";
    echo "   or: place sample in docs/migrations/cv-text-noisy-sample.txt\n";
    exit(1);
}

$clean = cv_import_clean_extracted_text($raw);
echo 'raw_len=' . (string) ($clean['raw_len'] ?? 0) . "\n";
echo 'clean_len=' . (string) ($clean['clean_len'] ?? 0) . "\n";
echo 'noise_score=' . (string) ($clean['noise_score'] ?? 0) . "\n";
echo 'steps=' . implode(',', (array) ($clean['steps'] ?? [])) . "\n";
echo "clean_preview=\n" . substr((string) ($clean['text'] ?? ''), 0, 1500) . "\n";
