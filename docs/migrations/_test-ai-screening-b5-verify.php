<?php
declare(strict_types=1);

/**
 * EMP-B B5 — smoke verify (CLI). Manual UI checks: plan §9 in phase-emp-b-checklist.md
 *
 * Usage:
 *   php docs/migrations/_test-ai-screening-b5-verify.php
 *   php docs/migrations/_test-ai-screening-b5-verify.php --run
 */

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_ai_screening.php';
require_once __DIR__ . '/../../includes/schema_applications_cv.php';
require_once __DIR__ . '/../../includes/ai_screening_config.php';
require_once __DIR__ . '/../../includes/ai_screening_job_text.php';
require_once __DIR__ . '/../../includes/employer_screening_rules.php';
require_once __DIR__ . '/../../includes/services/ApplicationService.php';
require_once __DIR__ . '/../../includes/services/AiScreeningService.php';
require_once __DIR__ . '/../../includes/repositories/AiScreeningRepository.php';

$doRun = in_array('--run', $argv ?? [], true);
$passed = 0;
$failed = 0;
$manual = 0;

function b5_line(string $id, string $status, string $detail): void
{
    global $passed, $failed, $manual;
    echo sprintf("[%s] %s — %s\n", $status, $id, $detail);
    if ($status === 'PASS') {
        $passed++;
    } elseif ($status === 'FAIL') {
        $failed++;
    } else {
        $manual++;
    }
}

echo "=== EMP-B B5 verify ===\n\n";

// B5.1 — schema cv_snapshot_text
$textReady = applications_cv_snapshot_text_ready($conn);
b5_line(
    'B5.1 cv_snapshot_text column',
    $textReady ? 'PASS' : 'FAIL',
    $textReady ? 'Column exists' : 'Run migrate-phase-emp-b-cv-snapshot-text.php'
);

// B5.2 — schema ai_screening_results
$aiReady = ai_screening_results_ready($conn);
b5_line(
    'B5.2 ai_screening_results table',
    $aiReady ? 'PASS' : 'FAIL',
    $aiReady ? 'Table exists' : 'Run migrate-phase-emp-b-ai-screening.php'
);

// B5.3 — config
$configReady = ai_screening_config_ready();
b5_line(
    'B5.3 AI config (Python CLI paths)',
    $configReady ? 'PASS' : 'FAIL',
    ai_screening_config_status_message()
);

// B5.4 — job with CV text for screening
$jobRow = $conn->query(
    "SELECT j.id, j.company_id, j.title,
            SUM(CASE WHEN a.cv_snapshot_text IS NOT NULL AND TRIM(a.cv_snapshot_text) <> '' THEN 1 ELSE 0 END) AS with_text
     FROM jobs j
     JOIN applications a ON a.job_id = j.id
     WHERE j.deleted_at IS NULL AND j.status = 'approved'
     GROUP BY j.id, j.company_id, j.title
     HAVING with_text > 0
     ORDER BY with_text DESC, j.id ASC
     LIMIT 1"
)->fetch(PDO::FETCH_ASSOC);

if (!is_array($jobRow)) {
    b5_line('B5.4 job with cv_snapshot_text', 'FAIL', 'No approved job with CV text — apply mới bằng CV online');
    $jobId = 0;
    $companyId = 0;
} else {
    $jobId = (int) $jobRow['id'];
    $companyId = (int) $jobRow['company_id'];
    b5_line(
        'B5.4 job with cv_snapshot_text',
        'PASS',
        "job_id={$jobId} company_id={$companyId} with_text={$jobRow['with_text']} | {$jobRow['title']}"
    );
}

// B5.5 — JD validation (empty job)
$emptyJdError = ai_screening_job_text_validation_error([
    'title' => 'Test empty JD',
    'requirements' => '',
    'description' => '',
    'experience' => '',
    'benefits' => '',
]);
b5_line(
    'B5.5 JD thiếu nội dung → message',
    $emptyJdError !== '' ? 'PASS' : 'FAIL',
    $emptyJdError !== '' ? $emptyJdError : 'Expected validation error'
);

// B5.6 — employer B cannot run AI on job A
if ($jobId > 0) {
    $wrongCompany = $companyId + 999;
    $denied = AiScreeningService::runForJob($conn, $jobId, $wrongCompany);
    $deniedOk = !$denied['ok'] && str_contains((string) ($denied['message'] ?? ''), 'quyền');
    b5_line(
        'B5.6 employer B không chạy job A',
        $deniedOk ? 'PASS' : 'FAIL',
        (string) ($denied['message'] ?? 'no message')
    );
} else {
    b5_line('B5.6 employer B không chạy job A', 'FAIL', 'Skipped — no test job');
}

// B5.7 — review card helpers
$sampleCard = employer_ai_review_card_for_ui([
    'summary' => 'OK',
    'concerns' => ['Gap: <ul><li>Benefit A</li></ul>'],
    'strengths' => [],
    'evidence_highlights' => [],
    'suggested_interview_questions' => [],
]);
$normalizeOk = is_array($sampleCard)
    && count($sampleCard['concerns'] ?? []) >= 2
    && !str_contains((string) ($sampleCard['concerns'][1] ?? ''), '<li>');
b5_line(
    'B5.7 review card HTML normalize',
    $normalizeOk ? 'PASS' : 'FAIL',
    $normalizeOk ? 'Embedded HTML flattened to plain bullets' : 'Normalize failed'
);

// B5.8 — CLI run (optional --run)
if ($doRun && $jobId > 0 && $configReady && $aiReady && $textReady) {
    $run = AiScreeningService::runForJob($conn, $jobId, $companyId);
    b5_line(
        'B5.8 CLI run AI',
        $run['ok'] ? 'PASS' : 'FAIL',
        (string) ($run['message'] ?? '')
    );

    if ($run['ok']) {
        $rows = AiScreeningRepository::listByJob($conn, $jobId);
        $hasRank = false;
        $hasReview = false;
        foreach ($rows as $row) {
            if (isset($row['ai_rank'], $row['final_score'], $row['recommendation'])) {
                $hasRank = true;
            }
            $card = employer_ai_review_card_parse((string) ($row['review_card_json'] ?? ''));
            if ($card !== null && employer_ai_review_card_has_content($card)) {
                $hasReview = true;
            }
        }
        b5_line(
            'B5.9 rank/score/recommendation in DB',
            $hasRank ? 'PASS' : 'FAIL',
            'rows=' . count($rows)
        );
        b5_line(
            'B5.10 review_card_json stored',
            $hasReview ? 'PASS' : 'WARN',
            $hasReview ? 'At least one review card' : 'No review_card yet (Python output)'
        );
        if (isset($run['skipped_count']) && (int) $run['skipped_count'] > 0) {
            b5_line(
                'B5.11 UV thiếu text → skip + thông báo',
                'PASS',
                'skipped_count=' . $run['skipped_count']
            );
        } else {
            b5_line(
                'B5.11 UV thiếu text → skip + thông báo',
                'MANUAL',
                'No skipped UV this run — test job có mix PDF/cũ không text'
            );
        }
    }
} elseif ($doRun) {
    b5_line('B5.8 CLI run AI', 'FAIL', 'Preconditions not met');
} else {
    b5_line(
        'B5.8 CLI run AI',
        'MANUAL',
        'Re-run with --run or: php docs/migrations/_test-ai-screening-run.php ' . ($jobId ?: '{job_id}') . ' ' . ($companyId ?: '{company_id}')
    );
    b5_line('B5.9 rank/score/recommendation in DB', 'MANUAL', 'Check job_candidates.php after run');
    b5_line('B5.10 review_card modal UI', 'MANUAL', 'Open AI review button on job_candidates.php');
    b5_line('B5.11 UV thiếu text → skip', 'MANUAL', 'Job with mix text/no-text apps');
}

b5_line('B5.12 Python path sai → Swal', 'MANUAL', 'Temporarily break ai_screening.local.php path');
b5_line('B5.13 regression EMP-A (status + CV modal)', 'MANUAL', 'job_candidates.php — Xử lý + CV online');

echo "\n=== Summary ===\n";
echo "PASS={$passed} FAIL={$failed} MANUAL={$manual}\n";
echo $failed === 0
    ? "Automated checks OK. Complete MANUAL items → 「EMP-B pass」\n"
    : "Fix FAIL items before EMP-B pass.\n";

exit($failed > 0 ? 1 : 0);
