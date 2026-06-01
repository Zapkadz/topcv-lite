<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_applications_cv.php';
require_once __DIR__ . '/../../includes/schema_cvs.php';
require_once __DIR__ . '/_cv-c-migrate-steps.php';

header('Content-Type: text/html; charset=utf-8');

function run_cv_c_migration(PDO $conn): array
{
    if (!cvs_schema_ready($conn)) {
        return ['ok' => false, 'message' => 'Chưa có schema CV-A. Chạy migrate-phase-cv-a.php trước.'];
    }

    $steps = cv_c_migration_steps($conn);
    $ran = [];

    try {
        foreach ($steps as $step) {
            $skip = $step['skip'] ?? null;
            if (is_callable($skip) && $skip()) {
                continue;
            }
            $conn->exec($step['sql']);
            $ran[] = $step['sql'];
        }

        if (!applications_cv_columns_ready($conn)) {
            return [
                'ok' => false,
                'message' => 'Migration chạy xong nhưng thiếu cột cv_snapshot_json. Kiểm tra bảng applications trong phpMyAdmin.',
            ];
        }

        return ['ok' => true, 'message' => 'Migration CV-C hoàn tất (' . count($ran) . ' bước thực thi).'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

$result = run_cv_c_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . ($result['ok'] ? '✓' : '✗') . ' '
    . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/docs/migrations/migrate-phase-cv-c.php">Chạy lại migration</a> · '
    . '<a href="/topcv_lite/job-detail.php?id=1">Thử job detail</a> · '
    . '<a href="/topcv_lite/employer/applicants.php">Ứng viên (NTD)</a></p>'
    . '</body></html>';
