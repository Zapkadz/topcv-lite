<?php
/**
 * Chạy migration Phase 2A qua trình duyệt (localhost only).
 * URL: http://localhost/topcv_lite/docs/migrations/migrate-phase-2a.php
 */
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy được trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_users.php';

header('Content-Type: text/html; charset=utf-8');

function column_exists(PDO $conn, string $column): bool
{
    $stmt = $conn->prepare('SHOW COLUMNS FROM users LIKE ?');
    $stmt->execute([$column]);

    return (bool) $stmt->fetch();
}

function run_migration(PDO $conn): array
{
    if (users_schema_has_phase2a($conn) && !column_exists($conn, 'status')) {
        return ['ok' => true, 'message' => 'Schema Phase 2A đã sẵn sàng (không cần chạy lại).'];
    }

    $hasStatus = column_exists($conn, 'status');
    $hasNew = column_exists($conn, 'employer_approval_status');

    try {
        if (!$hasNew) {
            $conn->exec(
                "ALTER TABLE `users`
                  ADD COLUMN `account_status` enum('active','suspended','pending_verification') NOT NULL DEFAULT 'active' AFTER `role`,
                  ADD COLUMN `employer_approval_status` enum('pending','approved','rejected') NULL DEFAULT NULL AFTER `account_status`"
            );
            $hasNew = true;
        }

        if ($hasStatus) {
            $conn->exec(
                "UPDATE `users` SET
                  `account_status` = IF(`status` = 0, 'suspended', 'active'),
                  `employer_approval_status` = NULL
                 WHERE `role` IN ('candidate', 'admin')"
            );
            $conn->exec(
                "UPDATE `users` SET
                  `account_status` = 'active',
                  `employer_approval_status` = IF(`status` = 0, 'pending', 'approved')
                 WHERE `role` = 'employer'"
            );
            $conn->exec('ALTER TABLE `users` DROP COLUMN `status`');
        } else {
            $conn->exec(
                "UPDATE `users` SET
                  `account_status` = 'active',
                  `employer_approval_status` = NULL
                 WHERE `role` IN ('candidate', 'admin')"
            );
            $conn->exec(
                "UPDATE `users` SET
                  `account_status` = 'active',
                  `employer_approval_status` = 'approved'
                 WHERE `role` = 'employer'
                   AND (`employer_approval_status` IS NULL OR `employer_approval_status` = '')"
            );
        }

        return ['ok' => true, 'message' => 'Migration Phase 2A hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => $e->getMessage()];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = run_migration($conn);
    $message = $result['message'];
    $ok = $result['ok'];
} else {
    $message = '';
    $ok = false;
    if (users_schema_has_phase2a($conn)) {
        $message = column_exists($conn, 'status')
            ? 'Đã có cột mới nhưng vẫn còn cột status cũ — bấm chạy để hoàn tất.'
            : 'Schema Phase 2A đã có. Có thể vào Admin → Người dùng.';
        $ok = !column_exists($conn, 'status');
    }
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Migration Phase 2A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width:600px">
    <div class="card shadow-sm">
        <div class="card-body p-4">
            <h1 class="h4 text-success mb-3">Migration Phase 2A</h1>

            <?php if ($message): ?>
                <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php endif; ?>

            <?php if ($ok && $_SERVER['REQUEST_METHOD'] === 'POST'): ?>
                <a href="/topcv_lite/admin/users.php" class="btn btn-success">Mở Admin → Người dùng</a>
            <?php elseif (!$ok || column_exists($conn, 'status')): ?>
                <p class="small text-muted">Nếu phpMyAdmin báo lỗi <code>Unknown column 'status'</code>, dùng nút bên dưới (tự nhận schema dở).</p>
                <form method="POST" class="mt-3">
                    <button type="submit" class="btn btn-primary w-100">Chạy / sửa migration Phase 2A</button>
                </form>
            <?php endif; ?>
        </div>
    </div>
</div>
</body>
</html>
