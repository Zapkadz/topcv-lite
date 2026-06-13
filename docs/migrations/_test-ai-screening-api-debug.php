<?php
declare(strict_types=1);

/**
 * Smoke test AI screening API debug (CLI, không qua web).
 * Usage: php docs/migrations/_test-ai-screening-api-debug.php 18
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/services/ApplicationService.php';
require_once __DIR__ . '/../../includes/ai_screening_payload.php';
require_once __DIR__ . '/../../includes/ai_screening_api.php';

$jobId = isset($argv[1]) ? (int) $argv[1] : 18;

$stmt = $conn->prepare('SELECT j.*, j.company_id FROM jobs j WHERE j.id = ? LIMIT 1');
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "Job #{$jobId} not found\n";
    exit(1);
}

$companyId = (int) ($job['company_id'] ?? 0);
$applications = ApplicationService::listApplicationsForAiScreening($conn, $jobId, $companyId);
$built = ai_screening_build_screening_payload($job, $applications);

echo 'debug_enabled=' . (ai_screening_debug_api_enabled() ? 'true' : 'false') . PHP_EOL;
echo 'candidates=' . count($built['payload']['candidates']) . ' skipped=' . $built['skipped'] . PHP_EOL;

if ($built['payload']['candidates'] === []) {
    echo "No candidates with cv_snapshot_text\n";
    exit(1);
}

if (!ai_screening_check_api_health()) {
    echo "API health check failed\n";
    exit(1);
}

$api = ai_screening_call_api($built['payload']);
echo 'api_ok=' . ($api['ok'] ? 'true' : 'false') . ' http=' . ($api['http_code'] ?? 0) . PHP_EOL;
if (!$api['ok']) {
    echo substr((string) ($api['error'] ?? ''), 0, 500) . PHP_EOL;
    exit(1);
}

echo "Check storage/logs/ai_screening.log and C:\\topcv_ai_runtime\\api-debug\n";
exit(0);
