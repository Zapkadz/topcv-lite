<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/ai_screening_config.php';
require_once __DIR__ . '/../../includes/ai_screening_job_text.php';

$jobId = isset($argv[1]) ? (int) $argv[1] : 1;

$stmt = $conn->prepare(
    'SELECT id, title, description, requirements, benefits, experience, job_level, job_type
     FROM jobs WHERE id = ? LIMIT 1'
);
$stmt->execute([$jobId]);
$job = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$job) {
    echo "Job #{$jobId} not found\n";
    exit(1);
}

$text = ai_screening_build_job_text($job);
$valid = ai_screening_job_text_is_valid($text);
$error = ai_screening_job_text_validation_error($job);

echo 'ai_screening_config_ready=' . (ai_screening_config_ready() ? 'true' : 'false') . "\n";
echo 'status=' . ai_screening_config_status_message() . "\n";
echo 'job_text_valid=' . ($valid ? 'true' : 'false') . "\n";
if ($error !== '') {
    echo 'validation_error=' . $error . "\n";
}
echo "--- JD text ---\n" . $text . "\n";
