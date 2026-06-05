<?php

require_once __DIR__ . '/../../includes/services/AiCvParserService.php';
require_once __DIR__ . '/../../includes/cv_import_rules.php';

$textPath = $argv[1] ?? '';
if ($textPath === '') {
    echo "Usage: php " . basename(__FILE__) . " \"C:\\path\\to\\cv_text.txt\"\n";
    echo "Example: php " . basename(__FILE__) . " \"docs\\migrations\\cv-text-test.txt\"\n";
    exit(1);
}

// Hỗ trợ đường dẫn tương đối từ thư mục project root.
$projectRoot = realpath(__DIR__ . '/../..');
$candidates = [$textPath];
if ($projectRoot !== false) {
    $candidates[] = $projectRoot . DIRECTORY_SEPARATOR . str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $textPath);
}
$resolved = null;
foreach ($candidates as $candidate) {
    if (is_file($candidate)) {
        $resolved = $candidate;
        break;
    }
}
if ($resolved === null) {
    echo "File not found: {$textPath}\n";
    echo "Usage: php " . basename(__FILE__) . " \"C:\\path\\to\\cv_text.txt\"\n";
    echo "Tip: tạo file text trước, hoặc chạy _test-e2-from-pdf.php với file PDF.\n";
    exit(1);
}
$textPath = $resolved;

$text = (string) file_get_contents($textPath);
if (trim($text) === '') {
    echo "Input text file is empty.\n";
    exit(1);
}

$result = AiCvParserService::parseTextToDraft($text);
echo 'ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
echo 'provider=' . (string) ($result['provider'] ?? 'unknown') . "\n";
if (!empty($result['message'])) {
    echo 'message=' . $result['message'] . "\n";
}

if (!empty($result['draft']) && is_array($result['draft'])) {
    $draft = $result['draft'];
    $fullName = (string) ($draft['full_name'] ?? '');
    $email = (string) ($draft['email'] ?? '');
    $phone = (string) ($draft['phone'] ?? '');
    $eduCount = is_array($draft['educations'] ?? null) ? count($draft['educations']) : 0;
    $expCount = is_array($draft['experiences'] ?? null) ? count($draft['experiences']) : 0;
    $skillCount = is_array($draft['skills'] ?? null) ? count($draft['skills']) : 0;

    echo 'full_name=' . $fullName . "\n";
    echo 'email=' . $email . "\n";
    echo 'phone=' . $phone . "\n";
    echo 'educations_count=' . $eduCount . "\n";
    echo 'experiences_count=' . $expCount . "\n";
    echo 'skills_count=' . $skillCount . "\n";

    $json = json_encode($draft, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
    if (is_string($json)) {
        echo "draft_preview=\n";
        echo substr($json, 0, 800) . "\n";
    }
}

