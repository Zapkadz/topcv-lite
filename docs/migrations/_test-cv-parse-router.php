<?php

/**
 * DEV ONLY — Test router parse mode (F1). Không gọi AI.
 *
 * Usage:
 *   php docs/migrations/_test-cv-parse-router.php "uploads\cv\file.pdf"
 *   php docs/migrations/_test-cv-parse-router.php "uploads\cv\file.pdf" vision
 */

require_once __DIR__ . '/../../includes/services/PdfTextExtractor.php';
require_once __DIR__ . '/../../includes/cv_import_text_clean.php';
require_once __DIR__ . '/../../includes/cv_import_pdf_quality.php';
require_once __DIR__ . '/../../includes/ai_config.php';

$inputPath = $argv[1] ?? '';
$requestedMode = $argv[2] ?? 'auto';

if ($inputPath === '') {
    echo "Usage: php " . basename(__FILE__) . " \"path\\to.pdf\" [auto|text|vision]\n";
    exit(1);
}

$projectRoot = realpath(__DIR__ . '/../..');
$candidates = [$inputPath];
if ($projectRoot !== false) {
    $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $inputPath);
}

$resolved = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $resolved = $candidate;
        break;
    }
}

if ($resolved === null) {
    echo "File not found: {$inputPath}\n";
    exit(1);
}

$extract = PdfTextExtractor::extract($resolved);
if (!$extract['ok']) {
    echo 'extract_ok=false' . "\n";
    echo 'message=' . (string) ($extract['message'] ?? '') . "\n";
    exit(1);
}

$rawText = (string) ($extract['text'] ?? '');
$cleanResult = cv_import_clean_extracted_text($rawText);
$quality = cv_import_analyze_pdf_quality($rawText, $cleanResult);
$route = cv_import_resolve_parse_mode($requestedMode, $quality, ai_openai_ready());

echo 'file=' . $resolved . "\n";
echo 'requested=' . cv_import_normalize_parse_mode_request($requestedMode) . "\n";
echo 'ai_openai_ready=' . (ai_openai_ready() ? 'true' : 'false') . "\n";
echo 'raw_len=' . (int) ($quality['raw_len'] ?? 0) . "\n";
echo 'clean_len=' . (int) ($quality['clean_len'] ?? 0) . "\n";
echo 'noise_score=' . (string) ($quality['noise_score'] ?? '') . "\n";
echo 'ratio_alnum=' . (string) ($quality['ratio_alnum'] ?? '') . "\n";
echo 'text_quality=' . (string) ($quality['text_quality'] ?? '') . "\n";
echo 'likely_scan=' . (!empty($quality['likely_scan']) ? 'true' : 'false') . "\n";
echo 'likely_noisy_layout=' . (!empty($quality['likely_noisy_layout']) ? 'true' : 'false') . "\n";
echo 'parse_mode=' . (string) ($route['mode'] ?? '') . "\n";
echo 'parse_mode_reason=' . (string) ($route['reason'] ?? '') . "\n";
echo 'parse_mode_label=' . cv_import_parse_mode_label((string) ($route['mode'] ?? '')) . "\n";
