<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true) && PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_ai_screening.php';

header('Content-Type: text/html; charset=utf-8');

function run_emp_b_ai_screening_migration(PDO $conn): array
{
    if (ai_screening_results_ready($conn)) {
        return ['ok' => true, 'message' => 'Bảng ai_screening_results đã sẵn sàng (không cần chạy lại).'];
    }

    $sql = trim((string) file_get_contents(__DIR__ . '/phase-emp-b-ai-screening.sql'));
    if ($sql === '') {
        return ['ok' => false, 'message' => 'Không đọc được phase-emp-b-ai-screening.sql'];
    }

    try {
        $conn->exec($sql);

        if (!ai_screening_results_ready($conn)) {
            return ['ok' => false, 'message' => 'Migration chạy xong nhưng thiếu bảng ai_screening_results.'];
        }

        return ['ok' => true, 'message' => 'Migration EMP-B ai_screening_results hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

$result = run_emp_b_ai_screening_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . ($result['ok'] ? '✓' : '✗') . ' '
    . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/docs/migrations/migrate-phase-emp-b-ai-screening.php">Chạy lại</a> · '
    . '<a href="/topcv_lite/employer/job_candidates.php?job_id=1">Job candidates</a></p>'
    . '</body></html>';
