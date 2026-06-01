<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_moderation.php';

header('Content-Type: text/html; charset=utf-8');

function run_phase_2c_migration(PDO $conn): array
{
    if (moderation_schema_ready($conn)) {
        return ['ok' => true, 'message' => 'Schema Phase 2C đã sẵn sàng (không cần chạy lại).'];
    }

    try {
        $conn->exec(
            "CREATE TABLE IF NOT EXISTS `moderation_logs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `admin_id` int(11) NOT NULL,
              `entity_type` enum('job','employer') NOT NULL,
              `entity_id` int(11) NOT NULL,
              `action` enum('approve','reject') NOT NULL,
              `note` text DEFAULT NULL,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              KEY `idx_moderation_entity` (`entity_type`,`entity_id`),
              KEY `idx_moderation_created` (`created_at`),
              KEY `idx_moderation_admin` (`admin_id`),
              CONSTRAINT `moderation_logs_admin_fk` FOREIGN KEY (`admin_id`) REFERENCES `users` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        return ['ok' => true, 'message' => 'Migration Phase 2C hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

if (moderation_schema_ready($conn)) {
    echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
        . '<h2 style="color:#198754">✓ Phase 2C đã có trên DB</h2>'
        . '<p>Bảng <code>moderation_logs</code> đã tồn tại.</p>'
        . '<p><a href="/topcv_lite/admin/moderation-log.php">→ Nhật ký kiểm duyệt</a></p></body></html>';
    exit;
}

$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = run_phase_2c_migration($conn);
    $message = $result['message'];
    $ok = $result['ok'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Migration Phase 2C</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width:520px">
    <div class="card shadow-sm"><div class="card-body p-4">
        <h1 class="h4 text-success">Migration Phase 2C</h1>
        <p class="small text-muted">Tạo bảng <code>moderation_logs</code> (audit duyệt job / NTD).</p>
        <?php if ($message): ?>
            <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php if ($ok): ?><a href="/topcv_lite/admin/moderation-log.php" class="btn btn-success">Nhật ký kiểm duyệt</a><?php endif; ?>
        <?php else: ?>
            <form method="POST" class="mt-3">
                <button type="submit" class="btn btn-primary w-100">Chạy migration</button>
            </form>
        <?php endif; ?>
    </div></div>
</div>
</body>
</html>
