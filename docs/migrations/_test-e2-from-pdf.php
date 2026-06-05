<?php

/**
 * DEV ONLY — Test E2: PDF → extract → AI. Không expose qua web.
 * @see docs/setup-cv-import.md
 * Usage: php _test-e2-from-pdf.php "uploads\cv\file.pdf"
 */

require_once __DIR__ . '/../../includes/services/PdfTextExtractor.php';
require_once __DIR__ . '/../../includes/services/AiCvParserService.php';
require_once __DIR__ . '/../../includes/cv_import_rules.php';

$pdfPath = $argv[1] ?? '';
if ($pdfPath === '') {
    echo "Usage: php " . basename(__FILE__) . " \"uploads\\cv\\file.pdf\"\n";
    exit(1);
}

$projectRoot = realpath(__DIR__ . '/../..');
$candidates = [$pdfPath];
if ($projectRoot !== false) {
    $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $pdfPath);
}
$resolved = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $resolved = $candidate;
        break;
    }
}
if ($resolved === null) {
    echo "PDF not found: {$pdfPath}\n";
    exit(1);
}

echo "pdf={$resolved}\n";

$extract = PdfTextExtractor::extract($resolved);
echo 'extract_ok=' . ($extract['ok'] ? 'true' : 'false') . "\n";
if (!$extract['ok']) {
    echo 'extract_message=' . ($extract['message'] ?? '') . "\n";
    exit(1);
}

$text = (string) ($extract['text'] ?? '');
$len = function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
echo "text_len={$len}\n";

$result = AiCvParserService::parseTextToDraft($text);
echo 'ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
echo 'provider=' . (string) ($result['provider'] ?? 'unknown') . "\n";
if (!empty($result['message'])) {
    echo 'message=' . $result['message'] . "\n";
}

if (!empty($result['draft']) && is_array($result['draft'])) {
    $draft = $result['draft'];
    echo 'full_name=' . (string) ($draft['full_name'] ?? '') . "\n";
    echo 'email=' . (string) ($draft['email'] ?? '') . "\n";
    echo 'phone=' . (string) ($draft['phone'] ?? '') . "\n";
    $eduCount = is_array($draft['educations'] ?? null) ? count($draft['educations']) : 0;
    $expCount = is_array($draft['experiences'] ?? null) ? count($draft['experiences']) : 0;
    echo 'educations_count=' . $eduCount . "\n";
    echo 'experiences_count=' . $expCount . "\n";
}
