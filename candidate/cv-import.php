<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/upload_validate.php';
require_once __DIR__ . '/../includes/cv_import_rules.php';
require_once __DIR__ . '/../includes/cv_import_pdf_quality.php';
require_once __DIR__ . '/../includes/cv_import_vip.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);

if ($schemaReady && $_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('candidate_cv_import_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: cv-import.php');
        exit();
    }

    $rateCheck = cv_import_rate_limit_check($userId);
    if (!$rateCheck['ok']) {
        $_SESSION['swal_icon'] = 'warning';
        $_SESSION['swal_title'] = $rateCheck['message'];
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

    cv_import_rate_limit_record($userId);

    $originalName = trim((string) ($_FILES['cv_pdf']['name'] ?? 'cv.pdf'));
    $pending = cv_import_build_pending_from_path($userId, $absolutePath, $relativePath, $originalName);
    $_SESSION['cv_import_pending'] = $pending;

    if (cv_user_import_is_vip($userId)) {
        cv_import_run_parse_and_redirect($userId, $absolutePath, $relativePath, 'vision');
    }

    header('Location: cv-import-choose.php');
    exit();
}

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="mb-4">
        <a href="cv-manage.php" class="text-success text-decoration-none"><i class="fas fa-arrow-left"></i> Quản lý CV</a>
        <h3 class="fw-bold mt-2 mb-1"><i class="fas fa-file-upload text-success"></i> Tạo CV từ PDF</h3>
        <p class="text-muted mb-0">Upload PDF — sau đó chọn Text-base (Groq) hoặc Chuẩn GPT (vision).</p>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <ul class="text-muted small mb-4">
                    <li>Chỉ chấp nhận file <strong>PDF</strong> (tối đa 5MB).</li>
                    <li>PDF text sạch → <strong>Text-base</strong> (nhanh, không giới hạn).</li>
                    <li>PDF scan / Canva → <strong>Chuẩn GPT</strong> (tối đa 5 lần/tài khoản; VIP không giới hạn).</li>
                    <li>Giới hạn <strong><?= (int) cv_import_rate_limit_max_per_hour() ?> lần upload / giờ</strong> trên mỗi tài khoản.</li>
                </ul>

                <form method="POST" enctype="multipart/form-data" id="cv-import-form">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_import_form')) ?>">
                    <div class="mb-3">
                        <label for="cv_pdf" class="form-label fw-bold">Chọn file CV (PDF)</label>
                        <input type="file" name="cv_pdf" id="cv_pdf" class="form-control" accept=".pdf,application/pdf" required>
                    </div>
                    <div class="d-flex flex-wrap gap-2">
                        <button type="submit" class="btn btn-success fw-bold" id="cv-import-submit">
                            <i class="fas fa-upload"></i> Upload và chọn cách phân tích
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
        btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> Đang upload...';
    });
})();
</script>

<?php include '../includes/footer.php'; ?>
