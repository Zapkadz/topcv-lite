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

function cvs_table_exists(PDO $conn, string $table): bool
{
    $stmt = $conn->prepare('SHOW TABLES LIKE ?');
    $stmt->execute([$table]);

    return (bool) $stmt->fetch();
}

function run_cv_a_migration(PDO $conn): array
{
    if (cvs_schema_ready($conn)
        && cvs_table_exists($conn, 'cv_educations')
        && cvs_table_exists($conn, 'cv_experiences')
        && cvs_table_exists($conn, 'cv_skills')) {
        return ['ok' => true, 'message' => 'Schema CV-A đã sẵn sàng (không cần chạy lại).'];
    }

    require __DIR__ . '/_cv-a-migrate-steps.php';

    try {
        foreach ($cv_a_migration_steps as $sql) {
            $conn->exec($sql);
        }

        return ['ok' => true, 'message' => 'Migration CV-A hoàn tất.'];
    } catch (Throwable $e) {
        return ['ok' => false, 'message' => 'Lỗi: ' . $e->getMessage()];
    }
}

if (cvs_schema_ready($conn) && cvs_table_exists($conn, 'cv_skills')) {
    echo '<!DOCTYPE html><html lang="vi"><body style="font-family:sans-serif;padding:2rem">'
        . '<h2 style="color:#198754">✓ CV-A đã có trên DB</h2>'
        . '<p>Bảng <code>cv_profiles</code> và bảng con đã tồn tại.</p>'
        . '<p><a href="/topcv_lite/docs/migrations/smoke-test-cv-a.php">Chạy smoke test</a></p></body></html>';
    exit;
}

$message = '';
$ok = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $result = run_cv_a_migration($conn);
    $message = $result['message'];
    $ok = $result['ok'];
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <title>Migration CV-A</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
</head>
<body class="bg-light py-5">
<div class="container" style="max-width:520px">
    <div class="card shadow-sm"><div class="card-body p-4">
        <h1 class="h4 text-success">Migration CV-A</h1>
        <p class="small text-muted">Tạo <code>cv_profiles</code> + educations / experiences / skills.</p>
        <?php if ($message): ?>
            <div class="alert alert-<?= $ok ? 'success' : 'danger' ?>"><?= htmlspecialchars($message) ?></div>
            <?php if ($ok): ?>
                <a href="/topcv_lite/docs/migrations/smoke-test-cv-a.php" class="btn btn-success">Smoke test</a>
            <?php endif; ?>
        <?php else: ?>
            <form method="POST" class="mt-3">
                <button type="submit" class="btn btn-primary w-100">Chạy migration</button>
            </form>
        <?php endif; ?>
    </div></div>
</div>
</body>
</html>
