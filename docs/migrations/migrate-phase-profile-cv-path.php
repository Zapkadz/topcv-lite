<?php
declare(strict_types=1);

$isCli = PHP_SAPI === 'cli';
if (!$isCli) {
    $remote = $_SERVER['REMOTE_ADDR'] ?? '';
    if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
        http_response_code(403);
        exit('Chỉ chạy trên localhost.');
    }
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/profile_cv_path_migration.php';

$dryRun = in_array('--dry-run', $argv ?? [], true)
    || (isset($_GET['dry_run']) && $_GET['dry_run'] === '1');

$result = profile_migrate_cv_path_batch($conn, $dryRun);

if ($isCli) {
    echo ($result['ok'] ? 'OK' : 'FAIL') . ': ' . $result['message'] . "\n";
    foreach ($result['stats'] as $key => $value) {
        echo "  {$key}: {$value}\n";
    }
    foreach ($result['details'] as $detail) {
        echo '  - candidate #' . ($detail['candidate_id'] ?? '?')
            . ' action=' . ($detail['action'] ?? '')
            . ' path=' . ($detail['cv_path'] ?? '')
            . (isset($detail['cv_profile_id']) ? ' profile_id=' . $detail['cv_profile_id'] : '')
            . (isset($detail['note']) ? ' note=' . $detail['note'] : '')
            . "\n";
    }
    exit($result['ok'] ? 0 : 1);
}

header('Content-Type: text/html; charset=utf-8');
$color = $result['ok'] ? '#198754' : '#dc3545';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="utf-8">
    <title>Migration Profile P2 — cv_path</title>
</head>
<body style="font-family: system-ui, sans-serif; max-width: 720px; margin: 2rem auto; padding: 0 1rem;">
    <h1>Profile P2 — migrate cv_path</h1>
    <p style="color: <?= htmlspecialchars($color) ?>; font-weight: bold;">
        <?= htmlspecialchars($result['message']) ?>
        <?php if ($dryRun): ?> (dry-run)<?php endif; ?>
    </p>
    <ul>
        <?php foreach ($result['stats'] as $key => $value): ?>
            <li><strong><?= htmlspecialchars((string) $key) ?>:</strong> <?= (int) $value ?></li>
        <?php endforeach; ?>
    </ul>
    <?php if ($result['details'] !== []): ?>
        <h2>Chi tiết</h2>
        <pre style="background: #f8f9fa; padding: 1rem; overflow: auto;"><?=
            htmlspecialchars(json_encode($result['details'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE))
        ?></pre>
    <?php endif; ?>
    <p>
        <a href="?dry_run=1">Dry-run</a> ·
        <a href="?" onclick="return confirm('Chạy migration thật?');">Chạy migration</a> ·
        <a href="_test-profile-cv-path-migrate.php">Test report</a>
    </p>
</body>
</html>
