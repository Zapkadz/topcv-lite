<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_ai_screening.php';
require_once __DIR__ . '/../../includes/schema_applications_cv.php';
require_once __DIR__ . '/../../includes/services/AiScreeningService.php';

$jobId = isset($argv[1]) ? (int) $argv[1] : 0;
$companyId = isset($argv[2]) ? (int) $argv[2] : 0;

if ($jobId <= 0 || $companyId <= 0) {
    echo "Usage: php _test-ai-screening-run.php {job_id} {company_id}\n";
    echo "Example: php _test-ai-screening-run.php 1 1\n";
    exit(1);
}

echo 'ai_screening_results_ready=' . (ai_screening_results_ready($conn) ? 'true' : 'false') . "\n";
echo 'cv_snapshot_text_ready=' . (applications_cv_snapshot_text_ready($conn) ? 'true' : 'false') . "\n";

$result = AiScreeningService::runForJob($conn, $jobId, $companyId);

echo 'ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
echo 'message=' . ($result['message'] ?? '') . "\n";
if (!empty($result['run_id'])) {
    echo 'run_id=' . $result['run_id'] . "\n";
}
if (isset($result['ranked_count'])) {
    echo 'ranked_count=' . $result['ranked_count'] . "\n";
}
if (isset($result['skipped_count'])) {
    echo 'skipped_count=' . $result['skipped_count'] . "\n";
}
if (!empty($result['runtime_path'])) {
    echo 'runtime_path=' . $result['runtime_path'] . "\n";
}

if (!$result['ok']) {
    exit(2);
}

$stmt = $conn->prepare('SELECT application_id, ai_rank, final_score, recommendation FROM ai_screening_results WHERE job_id = ? ORDER BY ai_rank ASC');
$stmt->execute([$jobId]);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    echo sprintf(
        "  app #%s rank=%s score=%s rec=%s\n",
        $row['application_id'],
        $row['ai_rank'] ?? '-',
        $row['final_score'] ?? '-',
        $row['recommendation'] ?? '-'
    );
}

exit(0);
