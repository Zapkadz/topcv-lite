<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/upload_validate.php';
require_once __DIR__ . '/../includes/ai_config.php';
require_once __DIR__ . '/../includes/services/CvParseService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);
$aiReady = ai_config_ready();

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('candidate_cv_import_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: cv-import.php');
        exit();
    }

    $fileCheck = upload_validate($_FILES['cv_pdf'] ?? [], 'cv_pdf_import');
    if (!$fileCheck['ok']) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $fileCheck['message'];
        header('Location: cv-import.php');
        exit();
    }

    $uploadDir = dirname(__DIR__) . '/uploads/cv/import/';
    if (!is_dir($uploadDir) && !mkdir($uploadDir, 0755, true) && !is_dir($uploadDir)) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Không tạo được thư mục lưu file.';
        header('Location: cv-import.php');
        exit();
    }

    $filename = $userId . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.pdf';
    $absolutePath = $uploadDir . $filename;
    $relativePath = 'uploads/cv/import/' . $filename;

    if (!move_uploaded_file($_FILES['cv_pdf']['tmp_name'], $absolutePath)) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Không lưu được file PDF, vui lòng thử lại.';
        header('Location: cv-import.php');
        exit();
    }

    $parseResult = CvParseService::importFromPdfPath($absolutePath);
    if (!$parseResult['ok']) {
        @unlink($absolutePath);
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = $parseResult['message'] ?: 'Không phân tích được CV từ PDF.';
        header('Location: cv-import.php');
        exit();
    }

    $_SESSION['cv_import_draft'] = [
        'user_id' => $userId,
        'profile' => $parseResult['profile'],
        'children' => $parseResult['children'],
        'attachment_path' => $relativePath,
        'meta' => $parseResult['meta'] ?? ['parse_source' => 'unknown', 'warnings' => []],
    ];

    header('Location: cv-builder.php?from_import=1');
    exit();
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="mb-4">
        <a href="cv-manage.php" class="text-success text-decoration-none"><i class="fas fa-arrow-left"></i> Quản lý CV</a>
        <h3 class="fw-bold mt-2 mb-1"><i class="fas fa-file-upload text-success"></i> Tạo CV từ PDF</h3>
        <p class="text-muted mb-0">Upload CV PDF có chữ (text-based) — hệ thống sẽ gợi ý điền form. Vui lòng kiểm tra trước khi lưu.</p>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php else: ?>
        <?php if (!$aiReady): ?>
            <div class="alert alert-warning">
                <i class="fas fa-exclamation-triangle"></i>
                Chưa cấu hình AI (<code>config/ai.local.php</code>). Hệ thống vẫn thử phân tích bằng fallback cơ bản — kết quả có thể ít field hơn.
            </div>
        <?php endif; ?>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <ul class="text-muted small mb-4">
                    <li>Chỉ chấp nhận file <strong>PDF</strong> (tối đa 5MB).</li>
                    <li>CV nên là file có thể <strong>bôi đen/copy chữ</strong> trong PDF (không phải scan ảnh).</li>
                    <li>Quá trình phân tích có thể mất <strong>10–30 giây</strong>.</li>
                </ul>

                <form method="POST" enctype="multipart/form-data" id="cv-import-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_import_form')) ?>">
                    <div class="mb-3">
                        <label for="cv_pdf" class="form-label fw-bold">Chọn file CV (PDF)</label>
                        <input type="file" name="cv_pdf" id="cv_pdf" class="form-control" accept=".pdf,application/pdf" required>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-success fw-bold" id="cv-import-submit">
                            <i class="fas fa-magic"></i> Phân tích và điền form
                        </button>
                        <a href="cv-builder.php" class="btn btn-outline-secondary">Tạo CV thủ công</a>
                    </div>
                </form>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    var form = document.getElementById('cv-import-form');
    var btn = document.getElementById('cv-import-submit');
    if (!form || !btn) {
        return;
    }
    form.addEventListener('submit', function () {
        btn.disabled = true;
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang phân tích...';
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
