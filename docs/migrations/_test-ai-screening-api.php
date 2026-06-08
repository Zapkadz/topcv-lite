<?php
declare(strict_types=1);

/**
 * Test AI screening qua FastAPI (driver=api).
 *
 * Usage:
 *   php docs/migrations/_test-ai-screening-api.php
 *   php docs/migrations/_test-ai-screening-api.php 17 2
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/ai_screening_config.php';
require_once __DIR__ . '/../../includes/ai_screening_api.php';
require_once __DIR__ . '/../../includes/services/AiScreeningService.php';

$jobId = isset($argv[1]) ? (int) $argv[1] : 17;
$companyId = isset($argv[2]) ? (int) $argv[2] : 2;

echo 'driver=' . ai_screening_driver() . "\n";
echo 'config_ready=' . (ai_screening_config_ready() ? 'true' : 'false') . "\n";
echo 'status=' . ai_screening_config_status_message() . "\n";
echo 'health=' . (ai_screening_check_api_health() ? 'true' : 'false') . "\n";

if (ai_screening_driver() !== 'api') {
    echo "Set driver=api in config/ai_screening.local.php\n";
    exit(1);
}

$result = AiScreeningService::runForJob($conn, $jobId, $companyId);

echo 'ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
echo 'message=' . ($result['message'] ?? '') . "\n";
if (!empty($result['detail'])) {
    echo 'detail=' . $result['detail'] . "\n";
}
if (!empty($result['run_id'])) {
    echo 'run_id=' . $result['run_id'] . "\n";
}
if (isset($result['ranked_count'])) {
    echo 'ranked_count=' . $result['ranked_count'] . "\n";
}

exit($result['ok'] ? 0 : 2);
