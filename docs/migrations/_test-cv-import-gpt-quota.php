<?php
declare(strict_types=1);

/**
 * Dev test — quota Chuẩn GPT (F5).
 *
 *   php docs/migrations/_test-cv-import-gpt-quota.php --user=6
 *   php docs/migrations/_test-cv-import-gpt-quota.php --user=6 --set-used=5
 *   php docs/migrations/_test-cv-import-gpt-quota.php --user=6 --check-block
 *   php docs/migrations/_test-cv-import-gpt-quota.php --user=6 --reset
 */

require __DIR__ . '/../../config/db.php';
require __DIR__ . '/../../includes/cv_import_rules.php';
require __DIR__ . '/../../includes/schema_cv_import.php';
require __DIR__ . '/../../includes/cv_import_vip.php';

$userId = 0;
$setUsed = null;
$reset = false;
$checkBlock = false;

foreach (array_slice($argv, 1) as $arg) {
    if (str_starts_with($arg, '--user=')) {
        $userId = (int) substr($arg, 7);
    } elseif (str_starts_with($arg, '--set-used=')) {
        $setUsed = (int) substr($arg, 11);
    } elseif ($arg === '--reset') {
        $reset = true;
    } elseif ($arg === '--check-block') {
        $checkBlock = true;
    }
}

if ($userId <= 0) {
    fwrite(STDERR, "Usage: php _test-cv-import-gpt-quota.php --user=ID [--set-used=N] [--reset] [--check-block]\n");
    exit(1);
}

$dbReady = users_cv_gpt_quota_ready($conn);
echo 'db_column=' . ($dbReady ? 'yes' : 'no') . PHP_EOL;

if ($reset) {
    cv_import_gpt_quota_reset($userId);
    echo "reset user {$userId}\n";
}

if ($setUsed !== null) {
    cv_import_gpt_quota_set_used_dev($userId, $setUsed);
    echo "set_used={$setUsed} user {$userId}\n";
}

$quota = cv_import_gpt_quota_check($userId);
echo 'used=' . $quota['used'] . ' remaining=' . $quota['remaining'] . '/' . $quota['max'] . PHP_EOL;
echo 'storage=' . $quota['storage'] . ' ok=' . ($quota['ok'] ? 'yes' : 'no') . PHP_EOL;
if ($quota['message'] !== '') {
    echo 'message=' . $quota['message'] . PHP_EOL;
}

if ($checkBlock) {
    if ($quota['ok']) {
        fwrite(STDERR, "FAIL: expected block at used>={$quota['max']}\n");
        exit(1);
    }
    echo "PASS: quota blocked as expected\n";
    exit(0);
}

exit(0);
