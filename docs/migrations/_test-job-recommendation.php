<?php
/**
 * Smoke test Phase 23 — candidate job recommendation API.
 *
 * Usage: php docs/migrations/_test-job-recommendation.php [cv_profile_id]
 */
require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_cvs.php';
require_once __DIR__ . '/../../includes/ai_screening_config.php';
require_once __DIR__ . '/../../includes/services/CvService.php';
require_once __DIR__ . '/../../includes/services/JobRecommendationService.php';
require_once __DIR__ . '/../../includes/repositories/JobRepository.php';

if (!cvs_schema_ready($conn)) {
    echo "CV schema not ready.\n";
    exit(1);
}

$cvId = isset($argv[1]) ? (int) $argv[1] : 0;
if ($cvId <= 0) {
    $row = $conn->query('SELECT id, candidate_id FROM cv_profiles ORDER BY is_primary DESC, id ASC LIMIT 1')->fetch(PDO::FETCH_ASSOC);
    $cvId = (int) ($row['id'] ?? 0);
}

if ($cvId <= 0) {
    echo "No cv_profiles row found.\n";
    exit(1);
}

$profile = $conn->prepare('SELECT candidate_id FROM cv_profiles WHERE id = ? LIMIT 1');
$profile->execute([$cvId]);
$candidateId = (int) $profile->fetchColumn();
$userStmt = $conn->prepare('SELECT user_id FROM candidates WHERE id = ? LIMIT 1');
$userStmt->execute([$candidateId]);
$userId = (int) $userStmt->fetchColumn();

echo "health=" . (ai_screening_check_api_health() ? 'ok' : 'fail') . "\n";
echo "cv_profile_id={$cvId} candidate_id={$candidateId} user_id={$userId}\n";
echo 'open_jobs=' . JobRepository::countOpenForRecommendation($conn) . "\n";

$hint = JobRecommendationService::buildPanelHint($conn, $userId, $cvId);
echo 'panel_ok=' . ($hint['ok'] ? 'true' : 'false') . ' hint=' . ($hint['hint'] ?: 'none') . "\n";

if (!$hint['ok']) {
    exit(1);
}

$result = JobRecommendationService::runForCandidate($conn, $userId, $cvId);
echo 'run_ok=' . ($result['ok'] ? 'true' : 'false') . "\n";
echo 'message=' . ($result['message'] ?? '') . "\n";

if (!$result['ok']) {
    if (!empty($result['detail'])) {
        echo 'detail=' . $result['detail'] . "\n";
    }
    exit(1);
}

$top = is_array($result['top_jobs'] ?? null) ? $result['top_jobs'] : [];
$excluded = is_array($result['excluded_jobs'] ?? null) ? $result['excluded_jobs'] : [];
$qStats = is_array($result['job_quality_stats'] ?? null) ? $result['job_quality_stats'] : [];
echo 'top_jobs=' . count($top) . ' excluded_jobs=' . count($excluded) . "\n";
if ($qStats !== []) {
    echo sprintf(
        "quality_stats received=%s eligible=%s excluded=%s\n",
        (string) ($qStats['jobs_received'] ?? '?'),
        (string) ($qStats['eligible_jobs'] ?? '?'),
        (string) ($qStats['excluded_jobs'] ?? '?')
    );
}

foreach (array_slice($top, 0, 3) as $job) {
    if (!is_array($job)) {
        continue;
    }
    echo sprintf(
        "  #%s job_id=%s fit=%s score=%s\n",
        (string) ($job['rank'] ?? '?'),
        (string) ($job['job_id'] ?? '?'),
        (string) ($job['fit_label'] ?? '?'),
        (string) ($job['fit_score'] ?? '?')
    );
}

echo "OK\n";
