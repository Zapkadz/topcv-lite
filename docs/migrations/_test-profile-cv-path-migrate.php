<?php
declare(strict_types=1);

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/profile_cv_path_migration.php';

$pending = profile_count_pending_cv_path($conn);
$dry = profile_migrate_cv_path_batch($conn, true);

echo "=== Profile P2 cv_path migration — test report ===\n\n";
echo "Pending cv_path rows: {$pending}\n";
echo 'Dry-run: ' . ($dry['ok'] ? 'OK' : 'FAIL') . ' — ' . $dry['message'] . "\n\n";

foreach ($dry['stats'] as $key => $value) {
    echo "  {$key}: {$value}\n";
}

if ($dry['details'] !== []) {
    echo "\nDetails:\n";
    foreach ($dry['details'] as $detail) {
        echo '  candidate #' . ($detail['candidate_id'] ?? '?')
            . ' user #' . ($detail['user_id'] ?? '?')
            . ' → ' . ($detail['action'] ?? '')
            . ' path=' . ($detail['cv_path'] ?? '')
            . (isset($detail['cv_profile_id']) ? ' profile_id=' . $detail['cv_profile_id'] : '')
            . (isset($detail['note']) ? ' [' . $detail['note'] . ']' : '')
            . (!empty($detail['file_missing']) ? ' [file missing on disk]' : '')
            . "\n";
    }
}

echo "\nChạy migration thật:\n";
echo "  php docs/migrations/migrate-phase-profile-cv-path.php\n";
echo "  hoặc http://localhost/topcv_lite/docs/migrations/migrate-phase-profile-cv-path.php\n";
