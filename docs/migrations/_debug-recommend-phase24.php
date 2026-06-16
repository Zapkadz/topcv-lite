<?php
/**
 * Debug Phase 24 candidate-side recommendation — read-only audit.
 *
 * Usage: php docs/migrations/_debug-recommend-phase24.php
 */
require_once __DIR__ . '/../../includes/ai_screening_config.php';
require_once __DIR__ . '/../../includes/ai_screening_api.php';
require_once __DIR__ . '/../../includes/ai_screening_runtime.php';
require_once __DIR__ . '/../../includes/job_recommendation_rules.php';

$cfg = ai_screening_config();
$healthUrl = trim((string) ($cfg['health_url'] ?? ''));
$recommendUrl = trim((string) ($cfg['recommend_jobs_api_url'] ?? ''));

echo "health_url={$healthUrl}\n";
echo "recommend_jobs_api_url={$recommendUrl}\n";
echo 'health_ok=' . (ai_screening_check_api_health() ? 'true' : 'false') . "\n";

$phase = '';
$service = '';
if ($healthUrl !== '' && function_exists('curl_init')) {
    $ch = curl_init($healthUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_CONNECTTIMEOUT => 3,
        CURLOPT_TIMEOUT => 8,
    ]);
    $body = curl_exec($ch);
    $code = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    echo "health_http={$code}\n";
    if (is_string($body) && $body !== '') {
        $decoded = json_decode($body, true);
        if (is_array($decoded)) {
            $phase = (string) ($decoded['phase'] ?? '');
            $service = (string) ($decoded['service'] ?? '');
            echo 'phase=' . $phase . "\n";
            echo 'service=' . $service . "\n";
            $expected = 'Phase 24 - JD Quality Gate and Recommendation Eligibility';
            echo 'phase24_expected=' . ($phase === $expected ? 'yes' : 'NO — got: ' . $phase) . "\n";
        } else {
            echo "health_body_invalid_json\n";
        }
    }
}

$debugDir = ai_screening_debug_api_dir();
echo 'debug_dir=' . $debugDir . "\n";

$latest = null;
$latestMtime = 0;
if (is_dir($debugDir)) {
    foreach (glob($debugDir . '/*-recommend-response.json') ?: [] as $file) {
        $mtime = (int) filemtime($file);
        if ($mtime >= $latestMtime) {
            $latestMtime = $mtime;
            $latest = $file;
        }
    }
}

if ($latest === null) {
    echo "latest_response=none\n";
    exit(0);
}

echo 'latest_response=' . basename($latest) . "\n";
$raw = file_get_contents($latest);
$data = is_string($raw) ? json_decode($raw, true) : null;
if (!is_array($data)) {
    echo "latest_response_invalid\n";
    exit(1);
}

$top = is_array($data['top_jobs'] ?? null) ? $data['top_jobs'] : [];
$excluded = is_array($data['excluded_jobs'] ?? null) ? $data['excluded_jobs'] : [];
$warnings = is_array($data['warnings'] ?? null) ? $data['warnings'] : [];
$qStats = is_array($data['job_quality_stats'] ?? null) ? $data['job_quality_stats'] : [];

echo 'top_jobs_count=' . count($top) . "\n";
echo 'excluded_jobs_count=' . count($excluded) . "\n";
echo 'warnings_count=' . count($warnings) . "\n";
echo 'has_job_quality_stats=' . ($qStats !== [] ? 'yes' : 'no') . "\n";
if ($qStats !== []) {
    echo sprintf(
        "job_quality_stats received=%s eligible=%s excluded=%s\n",
        (string) ($qStats['jobs_received'] ?? '?'),
        (string) ($qStats['eligible_jobs'] ?? '?'),
        (string) ($qStats['excluded_jobs'] ?? '?')
    );
}

$topIds = [];
foreach ($top as $row) {
    if (is_array($row)) {
        $topIds[] = (string) ($row['job_id'] ?? '?');
    }
}
$excludedIds = [];
foreach ($excluded as $row) {
    if (is_array($row)) {
        $excludedIds[] = (string) ($row['job_id'] ?? '?');
    }
}
$overlap = array_intersect($topIds, $excludedIds);

echo 'top_job_ids=' . ($topIds !== [] ? implode(',', $topIds) : 'none') . "\n";
echo 'excluded_job_ids=' . ($excludedIds !== [] ? implode(',', $excludedIds) : 'none') . "\n";
echo 'overlap_top_excluded=' . ($overlap !== [] ? implode(',', $overlap) . ' BUG' : 'none OK') . "\n";

foreach ($excluded as $row) {
    if (!is_array($row)) {
        continue;
    }
    $q = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : [];
    echo sprintf(
        "  excluded job_id=%s label=%s eligible=%s\n",
        (string) ($row['job_id'] ?? '?'),
        (string) ($q['quality_label'] ?? '?'),
        ($q['recommendation_eligible'] ?? false) ? 'true' : 'false'
    );
}

foreach (array_slice($top, 0, 5) as $row) {
    if (!is_array($row)) {
        continue;
    }
    $q = is_array($row['job_quality'] ?? null) ? $row['job_quality'] : [];
    echo sprintf(
        "  top job_id=%s fit=%s score=%s quality=%s\n",
        (string) ($row['job_id'] ?? '?'),
        (string) ($row['fit_label'] ?? '?'),
        (string) ($row['fit_score'] ?? '?'),
        (string) ($q['quality_label'] ?? 'n/a')
    );
}
