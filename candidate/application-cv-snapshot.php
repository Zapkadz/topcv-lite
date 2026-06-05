<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/cv_preview_render.php';
require_once __DIR__ . '/../includes/services/ApplicationService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$appId = isset($_GET['app_id']) ? (int) $_GET['app_id'] : 0;
if ($appId <= 0) {
    http_response_code(400);
    exit('Thiếu mã hồ sơ ứng tuyển.');
}

$row = ApplicationService::getApplicationForCandidate($conn, $appId, (int) $_SESSION['user_id']);
if (!$row) {
    http_response_code(404);
    exit('Hồ sơ không tồn tại hoặc bạn không có quyền xem.');
}

$json = $row['cv_snapshot_json'] ?? null;
if ($json === null || trim((string) $json) === '') {
    $filePath = trim((string) ($row['cv_snapshot'] ?? ''));
    if ($filePath !== '' && is_file(__DIR__ . '/../' . $filePath)) {
        header('Location: ../' . $filePath);
        exit();
    }
    http_response_code(404);
    exit('Không có bản CV đã lưu cho đơn này.');
}
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CV đã nộp — <?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?></title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <link rel="stylesheet" href="<?= BASE_URL ?>assets/css/cv-preview.css">
    <style>
        body { background: #f4f5f5; padding: 1rem; }
        .cv-preview-contact .text-break { word-break: break-word; overflow-wrap: anywhere; }
    </style>
</head>
<body>
    <p class="small text-muted mb-2">
        <a href="my-jobs.php" class="text-success"><i class="fas fa-arrow-left"></i> Lịch sử ứng tuyển</a>
        — <strong><?= htmlspecialchars((string) ($row['job_title'] ?? '')) ?></strong>
        tại <?= htmlspecialchars((string) ($row['company_name'] ?? '')) ?>
        <span class="text-muted">(bản CV lúc bạn nộp hồ sơ)</span>
    </p>
    <?= cv_render_snapshot_from_json(is_string($json) ? $json : null) ?>
</body>
</html>
