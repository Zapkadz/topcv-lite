<?php
declare(strict_types=1);

/**
 * Đối chiếu JD từ DB (job thật trên web) với file plain text mẫu (VD: JD_2.txt).
 *
 * Usage:
 *   php docs/migrations/_test-ai-screening-jd-normalize.php 18
 *   php docs/migrations/_test-ai-screening-jd-normalize.php 18 C:\SEMANTIC_SKILLS_RESUME\data\jobs\JD_2.txt
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/ai_screening_job_text.php';
require_once __DIR__ . '/../../includes/ai_screening_payload.php';

$jobId = isset($argv[1]) ? (int) $argv[1] : 18;
$referencePath = isset($argv[2]) ? trim((string) $argv[2]) : 'C:\\SEMANTIC_SKILLS_RESUME\\data\\jobs\\JD_2.txt';

$stmt = $conn->prepare(
    'SELECT id, title, description, requirements, benefits, experience, job_level, job_type
     FROM jobs WHERE id = ? LIMIT 1'
);
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "Job #{$jobId} not found in DB.\n";
    echo "Mở job trên web: http://localhost/topcv_lite/job-detail.php?id={$jobId}\n";
    exit(1);
}

$payload = ai_screening_build_job_payload($job);
$jobText = ai_screening_build_job_text($job);

echo "=== JD normalize test — job #{$jobId} ===\n";
echo 'Title: ' . ($job['title'] ?? '') . "\n";
echo 'URL: http://localhost/topcv_lite/job-detail.php?id=' . $jobId . "\n\n";

$rawReq = (string) ($job['requirements'] ?? '');
$rawDesc = (string) ($job['description'] ?? '');
$hasHtmlReq = (bool) preg_match('/<[^>]+>/', $rawReq);
$hasHtmlDesc = (bool) preg_match('/<[^>]+>/', $rawDesc);

echo "--- DB raw (snippet) ---\n";
echo 'requirements has HTML: ' . ($hasHtmlReq ? 'yes' : 'no') . "\n";
echo 'description has HTML: ' . ($hasHtmlDesc ? 'yes' : 'no') . "\n";
if ($hasHtmlDesc) {
    echo 'description snippet: ' . substr($rawDesc, 0, 120) . "...\n";
}
echo "\n";

$reqLines = $payload['requirements'] ?? [];
$respLines = $payload['responsibilities'] ?? [];
$anyHtmlInPayload = false;
foreach (array_merge($reqLines, $respLines, [$payload['description'] ?? '']) as $line) {
    if (is_string($line) && preg_match('/<[^>]+>/', $line)) {
        $anyHtmlInPayload = true;
        break;
    }
}

echo "--- After normalize (API payload) ---\n";
echo 'requirements count: ' . count($reqLines) . "\n";
echo 'responsibilities count: ' . count($respLines) . "\n";
echo 'payload still has HTML tags: ' . ($anyHtmlInPayload ? 'YES (BUG)' : 'no (OK)') . "\n";
echo 'job_text_valid: ' . (ai_screening_job_text_is_valid($jobText) ? 'true' : 'false') . "\n";

$polluted = [];
foreach ($reqLines as $line) {
    $line = (string) $line;
    if (str_starts_with($line, 'Cấp bậc:') || str_starts_with($line, 'Hình thức:')) {
        $polluted[] = $line;
    }
}
$niceToHave = $payload['nice_to_have'] ?? [];
if ($niceToHave !== []) {
    $polluted[] = 'nice_to_have not empty (' . count($niceToHave) . ' items)';
}
if (str_contains($jobText, 'Nice to have:')) {
    $polluted[] = 'JD text still has Nice to have section';
}
echo 'payload metadata pollution: ' . ($polluted === [] ? 'none (OK)' : implode('; ', $polluted)) . "\n\n";

echo "First 8 requirements lines:\n";
foreach (array_slice($reqLines, 0, 8) as $i => $line) {
    echo '  ' . ($i + 1) . '. ' . $line . "\n";
}

echo "\nFirst 5 responsibilities lines:\n";
foreach (array_slice($respLines, 0, 5) as $i => $line) {
    echo '  ' . ($i + 1) . '. ' . substr((string) $line, 0, 100) . (strlen((string) $line) > 100 ? '...' : '') . "\n";
}

echo "\n--- JD text file (CLI / contract) ---\n";
echo substr($jobText, 0, 1500) . (strlen($jobText) > 1500 ? "\n...(truncated)" : '') . "\n";

if ($referencePath !== '' && is_file($referencePath)) {
    $ref = file_get_contents($referencePath);
    $refLines = array_values(array_filter(array_map('trim', preg_split('/\r\n|\r|\n/', (string) $ref) ?: [])));
    $plainDesc = ai_screening_html_to_plain_text($rawDesc);
    $plainReq = ai_screening_html_to_plain_text($rawReq);
    $dbPlainLineCount = count(array_filter(explode("\n", $plainDesc . "\n" . $plainReq)));

    echo "\n--- Compare with reference file ---\n";
    echo 'Reference: ' . $referencePath . "\n";
    echo 'Reference line count (non-empty): ' . count($refLines) . "\n";
    echo 'DB normalized line count (desc+req): ' . $dbPlainLineCount . "\n";
    echo "Ghi chú: JD_2.txt là file test AI thủ công; job #{$jobId} là tin thật trên web.\n";
    echo "Sau fix, payload không còn thẻ HTML và có nhiều dòng requirements/responsibilities.\n";
} else {
    echo "\n(reference file not found: {$referencePath})\n";
}

exit($anyHtmlInPayload ? 1 : 0);
