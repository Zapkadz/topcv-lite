<?php
declare(strict_types=1);

$remote = $_SERVER['REMOTE_ADDR'] ?? '';
if (!in_array($remote, ['127.0.0.1', '::1'], true) && PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit('Chỉ chạy trên localhost.');
}

require_once __DIR__ . '/../../config/db.php';
require_once __DIR__ . '/../../includes/schema_ai_taxonomy.php';

header('Content-Type: text/html; charset=utf-8');

function run_admin_taxonomy_migration(PDO $conn): array
{
    if (ai_taxonomy_schema_ready($conn)) {
        return ['ok' => true, 'message' => 'Schema admin taxonomy đã sẵn sàng (không cần chạy lại).'];
    }

    $sql = trim((string) file_get_contents(__DIR__ . '/phase-admin-taxonomy.sql'));
    if ($sql === '') {
        return ['ok' => false, 'message' => 'Không đọc được phase-admin-taxonomy.sql'];
    }

    try {
        $statements = array_filter(array_map('trim', preg_split('/;\s*\n/', $sql) ?: []));
        foreach ($statements as $statement) {
            if ($statement !== '') {
                $conn->exec($statement);
            }
        }

        if (!ai_taxonomy_schema_ready($conn)) {
            return ['ok' => false, 'message' => 'Migration chạy xong nhưng thiếu bảng taxonomy.'];
        }

        return ['ok' => true, 'message' => 'Migration admin taxonomy hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

$result = run_admin_taxonomy_migration($conn);
$color = $result['ok'] ? '#198754' : '#dc3545';

echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
    . '<h2 style="color:' . $color . '">' . ($result['ok'] ? '✓' : '✗') . ' '
    . htmlspecialchars($result['message']) . '</h2>'
    . '<p><a href="/topcv_lite/admin/ai_taxonomy_suggestions.php">Admin taxonomy suggestions</a> · '
    . '<a href="/topcv_lite/docs/migrations/_test-admin-taxonomy.php">Test report</a></p>'
    . '</body></html>';
