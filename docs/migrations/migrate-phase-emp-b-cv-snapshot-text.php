<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_applications_cv.php';
require_once __DIR__ . '/../../includes/cv_snapshot_text.php';

header('Content-Type: text/html; charset=utf-8');

/**
 * @return array{ok: bool, message: string, backfilled: int}
 */
function run_emp_b_cv_snapshot_text_migration(PDO $conn): array
{
    if (applications_cv_snapshot_text_ready($conn)) {
        return ['ok' => true, 'message' => 'Cột cv_snapshot_text đã sẵn sàng (không cần chạy lại).', 'backfilled' => 0];
    }

    if (!applications_cv_columns_ready($conn)) {
        return ['ok' => false, 'message' => 'Chưa có schema CV-C. Chạy migrate-phase-cv-c.php trước.', 'backfilled' => 0];
    }

    $sql = trim((string) file_get_contents(__DIR__ . '/phase-emp-b-cv-snapshot-text.sql'));
    if ($sql === '') {
        return ['ok' => false, 'message' => 'Không đọc được phase-emp-b-cv-snapshot-text.sql', 'backfilled' => 0];
    }

    try {
        $conn->exec($sql);

        if (!applications_cv_snapshot_text_ready($conn)) {
            return ['ok' => false, 'message' => 'Migration chạy xong nhưng thiếu cột cv_snapshot_text.', 'backfilled' => 0];
        }

        $backfilled = backfill_applications_cv_snapshot_text($conn);

        return [
            'ok' => true,
            'message' => 'Migration cv_snapshot_text hoàn tất. Backfill ' . $backfilled . ' đơn.',
            'backfilled' => $backfilled,
        ];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage(), 'backfilled' => 0];
    }
}

function backfill_applications_cv_snapshot_text(PDO $conn): int
{
    $stmt = $conn->query(
        'SELECT id, cv_snapshot_json FROM applications
         WHERE cv_snapshot_json IS NOT NULL
           AND TRIM(cv_snapshot_json) <> \'\''
    );
    $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
    if (!is_array($rows) || $rows === []) {
        return 0;
    }

    $update = $conn->prepare('UPDATE applications SET cv_snapshot_text = ? WHERE id = ?');
    $count = 0;

    foreach ($rows as $row) {
        $text = cv_snapshot_text_from_json((string) ($row['cv_snapshot_json'] ?? ''));
        if ($text === null || trim($text) === '') {
            continue;
        }
        $update->execute([$text, (int) $row['id']]);
        $count++;
    }

    return $count;
}

$result = run_emp_b_cv_snapshot_text_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . ($result['ok'] ? '✓' : '✗') . ' '
    . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/docs/migrations/migrate-phase-emp-b-cv-snapshot-text.php">Chạy lại</a> · '
    . '<a href="/topcv_lite/employer/job_candidates.php?job_id=1">Job candidates</a></p>'
    . '</body></html>';
