<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true)) {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_cvs.php';
require_once __DIR__ . '/../../includes/cv_rules.php';

header('Content-Type: text/html; charset=utf-8');

function run_cv_b_format_migration(PDO $conn): array
{
    if (!cvs_schema_ready($conn)) {
        return ['ok' => false, 'message' => 'Chưa có schema CV-A. Chạy migrate-phase-cv-a.php trước.'];
    }

    $sqlFile = __DIR__ . '/phase-cv-b-formats.sql';
    if (!is_readable($sqlFile)) {
        return ['ok' => false, 'message' => 'Không đọc được phase-cv-b-formats.sql'];
    }

    $raw = file_get_contents($sqlFile);
    if ($raw === false) {
        return ['ok' => false, 'message' => 'Không đọc được nội dung SQL.'];
    }

    $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $raw)));

    try {
        foreach ($statements as $sql) {
            if ($sql === '' || str_starts_with($sql, '--')) {
                continue;
            }
            $conn->exec($sql);
        }

        $stmt = $conn->query('SELECT id, phone FROM cv_profiles WHERE phone IS NOT NULL');
        $rows = $stmt ? $stmt->fetchAll(PDO::FETCH_ASSOC) : [];
        $fix = $conn->prepare('UPDATE cv_profiles SET phone = ? WHERE id = ?');
        foreach ($rows as $row) {
            $normalized = cv_normalize_phone((string) ($row['phone'] ?? ''));
            if (cv_is_valid_phone_vn($normalized)) {
                $fix->execute([$normalized, (int) $row['id']]);
            }
        }

        return ['ok' => true, 'message' => 'Migration CV-B formats hoàn tất (phone 10 số, tháng/năm CHAR(7) YYYY-MM).'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

$result = run_cv_b_format_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';
$icon = $result['ok'] ? '✓' : '✗';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . $icon . ' ' . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/candidate/cv-manage.php">Quản lý CV</a> · '
    . '<a href="/topcv_lite/candidate/cv-builder.php">Tạo CV</a></p>'
    . '<p class="text-muted"><small>Chuẩn DB: <code>phone</code> varchar(10); <code>start_date</code>/<code>end_date</code> char(7) dạng <code>YYYY-MM</code>.</small></p>'
    . '</body></html>';
