<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';

echo "=== jobs (approved, not deleted) ===\n";
$jobs = $conn->query(
    "SELECT j.id, j.company_id, j.title, j.status,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id) AS total_apps,
            (SELECT COUNT(*) FROM applications a WHERE a.job_id = j.id AND a.cv_snapshot_text IS NOT NULL AND TRIM(a.cv_snapshot_text) <> '') AS apps_with_text
     FROM jobs j
     WHERE j.deleted_at IS NULL
     ORDER BY j.id"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($jobs as $row) {
    echo sprintf(
        "job_id=%s company_id=%s status=%s apps=%s with_text=%s | %s\n",
        $row['id'],
        $row['company_id'],
        $row['status'],
        $row['total_apps'],
        $row['apps_with_text'],
        $row['title']
    );
}

echo "\n=== applications detail ===\n";
$apps = $conn->query(
    'SELECT app.id, app.job_id, app.candidate_id, j.company_id, j.status AS job_status,
            CASE WHEN app.cv_snapshot_text IS NOT NULL AND TRIM(app.cv_snapshot_text) <> \'\' THEN 1 ELSE 0 END AS has_text
     FROM applications app
     JOIN jobs j ON app.job_id = j.id
     ORDER BY app.id'
)->fetchAll(PDO::FETCH_ASSOC);

if ($apps === []) {
    echo "(no applications)\n";
} else {
    foreach ($apps as $row) {
        echo sprintf(
            "app_id=%s job_id=%s company_id=%s candidate_id=%s has_text=%s job_status=%s\n",
            $row['id'],
            $row['job_id'],
            $row['company_id'],
            $row['candidate_id'],
            $row['has_text'],
            $row['job_status']
        );
    }
}

echo "\n=== suggested test command ===\n";
foreach ($jobs as $row) {
    if ((int) $row['apps_with_text'] > 0 && $row['status'] === 'approved') {
        echo 'php docs/migrations/_test-ai-screening-run.php ' . $row['id'] . ' ' . $row['company_id'] . "\n";
    }
}
