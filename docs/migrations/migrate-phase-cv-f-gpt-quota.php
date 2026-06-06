<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_cv_import.php';

header('Content-Type: text/html; charset=utf-8');

function run_cv_f_gpt_quota_migration(PDO $conn): array
{
    if (users_cv_gpt_quota_ready($conn)) {
        return ['ok' => true, 'message' => 'Migration CV-F GPT quota đã sẵn sàng (không cần chạy lại).'];
    }

    $sql = trim((string) file_get_contents(__DIR__ . '/phase-cv-f-gpt-quota.sql'));
    if ($sql === '') {
        return ['ok' => false, 'message' => 'Không đọc được phase-cv-f-gpt-quota.sql'];
    }

    try {
        $conn->exec($sql);
        if (!users_cv_gpt_quota_ready($conn)) {
            return ['ok' => false, 'message' => 'Migration chạy xong nhưng thiếu cột cv_gpt_import_uses.'];
        }

        return ['ok' => true, 'message' => 'Migration CV-F GPT quota hoàn tất (users.cv_gpt_import_uses).'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

$result = run_cv_f_gpt_quota_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . ($result['ok'] ? '✓' : '✗') . ' '
    . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/docs/migrations/migrate-phase-cv-f-gpt-quota.php">Chạy lại</a> · '
    . '<a href="/topcv_lite/candidate/cv-import.php">Import PDF</a></p>'
    . '</body></html>';
