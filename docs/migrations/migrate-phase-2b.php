<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_jobs.php';

header('Content-Type: text/html; charset=utf-8');

function jobs_column_exists(PDO $conn, string $column): bool
{
    $stmt = $conn->prepare('SHOW COLUMNS FROM jobs LIKE ?');
    $stmt->execute([$column]);

    return (bool) $stmt->fetch();
}

function jobs_index_exists(PDO $conn, string $index): bool
{
    $stmt = $conn->prepare('SHOW INDEX FROM jobs WHERE Key_name = ?');
    $stmt->execute([$index]);

    return (bool) $stmt->fetch();
}

function run_phase_2b_migration(PDO $conn): array
{
    if (jobs_schema_has_soft_delete($conn) && jobs_index_exists($conn, 'idx_jobs_deleted_at')) {
        return ['ok' => true, 'message' => 'Schema Phase 2B đã sẵn sàng (không cần chạy lại).'];
    }

    try {
        if (!jobs_column_exists($conn, 'deleted_at')) {
            $conn->exec(
                'ALTER TABLE `jobs` ADD COLUMN `deleted_at` datetime NULL DEFAULT NULL'
            );
        }

        if (!jobs_index_exists($conn, 'idx_jobs_deleted_at')) {
            if (!jobs_column_exists($conn, 'deleted_at')) {
                return ['ok' => false, 'message' => 'Không thêm được cột deleted_at. Kiểm tra bảng jobs trong phpMyAdmin.'];
            }
            $conn->exec('CREATE INDEX `idx_jobs_deleted_at` ON `jobs` (`deleted_at`)');
        }

        return ['ok' => true, 'message' => 'Migration Phase 2B hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

if (jobs_schema_has_soft_delete($conn) && jobs_index_exists($conn, 'idx_jobs_deleted_at')) {
    echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
        . '<h2 style="color:#198754">✓ Phase 2B đã có trên DB</h2>'
        . '<p>Cột <code>deleted_at</code> và index đã tồn tại.</p>'
        . '<p><a href="/topcv_lite/employer/manage-jobs.php">→ Quản lý tin</a></p></body></html>';
    exit;
}

$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = run_phase_2b_migration($conn);
    $message = $result['message'];
    $ok = $result['ok'];
} elseif (jobs_column_exists($conn, 'deleted_at') && !jobs_index_exists($conn, 'idx_jobs_deleted_at')) {
    $message = 'Đã có cột deleted_at nhưng thiếu index — bấm chạy để tạo index.';
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Migration Phase 2B</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width:520px">
    <div class="card shadow-sm"><div class="card-body p-4">
        <h1 class="h4 text-success">Migration Phase 2B</h1>
        <p class="small text-muted">Thêm <code>jobs.deleted_at</code> (soft delete).</p>
        <?php if ($message): ?>
            <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php if ($ok): ?><a href="/topcv_lite/employer/manage-jobs.php" class="btn btn-success">Quản lý tin</a><?php endif; ?>
        <?php endif; ?>
        <?php if (!$ok): ?>
            <form method="POST" class="mt-3">
                <button type="submit" class="btn btn-primary w-100">Chạy migration</button>
            </form>
        <?php endif; ?>
    </div></div>
</div>
</body>
</html>
