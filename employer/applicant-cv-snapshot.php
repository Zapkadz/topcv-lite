<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/auth_check.php';
require_once __DIR__ . '/../includes/cv_preview_render.php';
require_once __DIR__ . '/../includes/services/ApplicationService.php';

$appId = isset($_GET['app_id']) ? (int) $_GET['app_id'] : 0;
if ($appId <= 0) {
    http_response_code(400);
    exit('Thiếu mã hồ sơ.');
}

$stmt = $conn->prepare('SELECT id FROM companies WHERE user_id = ? LIMIT 1');
$stmt->execute([(int) $_SESSION['user_id']]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$company) {
    http_response_code(403);
    exit('Không tìm thấy công ty.');
}

$row = ApplicationService::getApplicationForCompany($conn, $appId, (int) $company['id']);
if (!$row) {
    http_response_code(404);
    exit('Hồ sơ không tồn tại hoặc bạn không có quyền xem.');
}

$json = $row['cv_snapshot_json'] ?? null;
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV — <?= htmlspecialchars((string) ($row['fullname'] ?? '')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <style>
        body { background: #f4f5f5; padding: 1rem; }
        .cv-preview-contact .text-break { word-break: break-word; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <p class="small text-muted mb-2">
        Ứng viên: <strong><?= htmlspecialchars((string) ($row['fullname'] ?? '')) ?></strong>
        — <?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?>
        (snapshot lúc ứng tuyển)
    </p>
    <?= cv_render_snapshot_from_json(is_string($json) ? $json : null) ?>
</body>
</html>
