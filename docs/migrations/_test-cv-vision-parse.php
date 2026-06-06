<?php

/**
 * DEV ONLY — Test GPT vision parse PDF (F2).
 *
 * Usage:
 *   php docs/migrations/_test-cv-vision-parse.php "uploads\cv\file.pdf"
 *   php docs/migrations/_test-cv-vision-parse.php "uploads\cv\file.pdf" --pipeline
 */

require_once __DIR__ . '/../../includes/services/OpenAiCvVisionParserService.php';
require_once __DIR__ . '/../../includes/services/CvParseService.php';

$inputPath = $argv[1] ?? '';
$usePipeline = in_array('--pipeline', $argv, true);

if ($inputPath === '') {
    echo "Usage: php " . basename(__FILE__) . " \"path\\to.pdf\" [--pipeline]\n";
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

if ($usePipeline) {
    $result = CvParseService::importFromPdfPath($resolved, ['parse_mode' => 'vision']);
    echo "mode=pipeline_vision\n";
} else {
    $result = OpenAiCvVisionParserService::parsePdfToDraft($resolved);
    echo "mode=vision_service_only\n";
    if (!empty($result['method'])) {
        echo 'vision_method=' . (string) $result['method'] . "\n";
    }
}

echo 'ok=' . (!empty($result['ok']) ? 'true' : 'false') . "\n";
if (!empty($result['message'])) {
    echo 'message=' . $result['message'] . "\n";
}

$meta = $result['meta'] ?? [];
if (is_array($meta)) {
    foreach (['parse_mode', 'parse_source', 'vision_method', 'vision_provider'] as $key) {
        if (!empty($meta[$key])) {
            echo $key . '=' . (string) $meta[$key] . "\n";
        }
    }
}

$profile = $result['profile'] ?? [];
if (is_array($profile)) {
    echo 'full_name=' . (string) ($profile['full_name'] ?? '') . "\n";
    echo 'email=' . (string) ($profile['email'] ?? '') . "\n";
    echo 'phone=' . (string) ($profile['phone'] ?? '') . "\n";
}

$draft = $result['draft'] ?? null;
if (is_array($draft)) {
    echo 'full_name=' . (string) ($draft['full_name'] ?? '') . "\n";
    echo 'email=' . (string) ($draft['email'] ?? '') . "\n";
}

$children = $result['children'] ?? [];
if (is_array($children)) {
    foreach (['educations', 'experiences', 'skills'] as $section) {
        $count = is_array($children[$section] ?? null) ? count($children[$section]) : 0;
        echo $section . '_count=' . $count . "\n";
    }
}

exit(!empty($result['ok']) ? 0 : 1);
