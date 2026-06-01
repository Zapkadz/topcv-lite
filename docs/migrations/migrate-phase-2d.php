<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_saved_jobs.php';

header('Content-Type: text/html; charset=utf-8');

function run_phase_2d_migration(PDO $conn): array
{
    if (saved_jobs_schema_ready($conn)) {
        return ['ok' => true, 'message' => 'Schema Phase 2D đã sẵn sàng (không cần chạy lại).'];
    }

    try {
        $conn->exec(
            "CREATE TABLE IF NOT EXISTS `saved_jobs` (
              `id` int(11) NOT NULL AUTO_INCREMENT,
              `candidate_id` int(11) NOT NULL,
              `job_id` int(11) NOT NULL,
              `created_at` datetime NOT NULL DEFAULT current_timestamp(),
              PRIMARY KEY (`id`),
              UNIQUE KEY `uniq_saved_job` (`candidate_id`,`job_id`),
              KEY `idx_saved_candidate` (`candidate_id`),
              KEY `idx_saved_job` (`job_id`),
              CONSTRAINT `saved_jobs_candidate_fk` FOREIGN KEY (`candidate_id`) REFERENCES `candidates` (`id`) ON DELETE CASCADE,
              CONSTRAINT `saved_jobs_job_fk` FOREIGN KEY (`job_id`) REFERENCES `jobs` (`id`) ON DELETE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci"
        );

        return ['ok' => true, 'message' => 'Migration Phase 2D hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

if (saved_jobs_schema_ready($conn)) {
    echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
        . '<h2 style="color:#198754">✓ Phase 2D đã có trên DB</h2>'
        . '<p>Bảng <code>saved_jobs</code> đã tồn tại.</p>'
        . '<p><a href="/topcv_lite/candidate/my-jobs.php?tab=saved">→ Việc đã lưu</a></p></body></html>';
    exit;
}

$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = run_phase_2d_migration($conn);
    $message = $result['message'];
    $ok = $result['ok'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Migration Phase 2D</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width:520px">
    <div class="card shadow-sm"><div class="card-body p-4">
        <h1 class="h4 text-success">Migration Phase 2D</h1>
        <p class="small text-muted">Tạo bảng <code>saved_jobs</code>.</p>
        <?php if ($message): ?>
            <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php if ($ok): ?><a href="/topcv_lite/job-detail.php?id=1" class="btn btn-success">Thử lưu tin</a><?php endif; ?>
        <?php else: ?>
            <form method="POST" class="mt-3">
                <button type="submit" class="btn btn-primary w-100">Chạy migration</button>
            </form>
        <?php endif; ?>
    </div></div>
</div>
</body>
</html>
