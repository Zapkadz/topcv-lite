<?php
/**
 * DEV ONLY — CLI test CV-E pipeline PDF→AI→normalize. Không expose qua web.
 * @see docs/setup-cv-import.md
 */

require_once __DIR__ . '/../../includes/services/CvParseService.php';

$inputPath = $argv[1] ?? '';
if ($inputPath === '') {
    echo "Usage: php " . basename(__FILE__) . " \"uploads\\cv\\file.pdf\"\n";
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

$result = CvParseService::importFromPdfPath($resolved);

echo 'ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
if (!empty($result['message'])) {
    echo 'message=' . $result['message'] . "\n";
}

$meta = $result['meta'] ?? [];
echo 'parse_source=' . (string) ($meta['parse_source'] ?? '') . "\n";
echo 'text_noise_score=' . (string) ($meta['text_noise_score'] ?? '') . "\n";
if (!empty($meta['text_clean_steps']) && is_array($meta['text_clean_steps'])) {
    echo 'text_clean_steps=' . implode(',', $meta['text_clean_steps']) . "\n";
}
if (!empty($meta['warnings']) && is_array($meta['warnings'])) {
    foreach ($meta['warnings'] as $warning) {
        echo 'warning=' . $warning . "\n";
    }
}

$profile = $result['profile'] ?? [];
echo 'title=' . (string) ($profile['title'] ?? '') . "\n";
echo 'full_name=' . (string) ($profile['full_name'] ?? '') . "\n";
echo 'email=' . (string) ($profile['email'] ?? '') . "\n";
echo 'phone=' . (string) ($profile['phone'] ?? '') . "\n";

$children = $result['children'] ?? [];
foreach (['educations', 'experiences', 'skills', 'projects'] as $section) {
    $count = is_array($children[$section] ?? null) ? count($children[$section]) : 0;
    echo $section . '_count=' . $count . "\n";
}

if (!empty($children['educations'][0]['start_date'])) {
    echo 'education_1_start=' . (string) $children['educations'][0]['start_date'] . "\n";
}
if (!empty($children['educations'][0]['end_date'])) {
    echo 'education_1_end=' . (string) $children['educations'][0]['end_date'] . "\n";
}
