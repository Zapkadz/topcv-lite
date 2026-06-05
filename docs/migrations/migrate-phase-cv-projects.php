<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_cvs.php';

header('Content-Type: text/html; charset=utf-8');

function run_cv_projects_migration(PDO $conn): array
{
    if (!cvs_schema_ready($conn)) {
        return ['ok' => false, 'message' => 'Chưa có schema CV-A. Chạy migrate-phase-cv-a.php trước.'];
    }

    if (cvs_projects_ready($conn)) {
        return ['ok' => true, 'message' => 'Bảng cv_projects đã sẵn sàng (không cần chạy lại).'];
    }

    require __DIR__ . '/_cv-projects-migrate-steps.php';

    try {
        foreach ($cv_projects_migration_steps as $sql) {
            $conn->exec($sql);
        }

        if (!cvs_projects_ready($conn)) {
            return ['ok' => false, 'message' => 'Migration chạy xong nhưng thiếu bảng cv_projects.'];
        }

        return ['ok' => true, 'message' => 'Migration CV Projects hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

$result = run_cv_projects_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . ($result['ok'] ? '✓' : '✗') . ' '
    . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/docs/migrations/migrate-phase-cv-projects.php">Chạy lại</a> · '
    . '<a href="/topcv_lite/candidate/cv-builder.php">Mở CV builder</a></p>'
    . '</body></html>';
