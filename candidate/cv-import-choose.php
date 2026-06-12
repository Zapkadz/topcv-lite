<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_import_rules.php';
require_once __DIR__ . '/../includes/schema_cv_import.php';
require_once __DIR__ . '/../includes/cv_import_pdf_quality.php';
require_once __DIR__ . '/../includes/cv_import_vip.php';
require_once __DIR__ . '/../includes/ai_config.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);

if (!$schemaReady) {
    header('Location: cv-import.php');
    exit();
}

$pending = cv_import_get_valid_pending($userId);
if ($pending === null) {
    $_SESSION['swal_icon'] = 'warning';
    $_SESSION['swal_title'] = 'Không tìm thấy file PDF đang chờ. Vui lòng upload lại.';
    header('Location: cv-import.php');
    exit();
}

$quality = is_array($pending['quality'] ?? null) ? $pending['quality'] : [];
$routeAuto = (string) ($pending['route_auto'] ?? 'text_fast');
$isNoisy = cv_import_quality_is_noisy($quality);
$openAiReady = ai_openai_ready();
$isVip = cv_user_import_is_vip($userId);
$gptQuota = cv_import_gpt_quota_check($userId);
$originalName = (string) ($pending['original_name'] ?? 'CV.pdf');
$absolutePath = (string) $pending['absolute_path'];
$relativePath = (string) $pending['attachment_path'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!csrf_validate('candidate_cv_import_choose_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: cv-import-choose.php');
        exit();
    }

    $parseMode = cv_import_normalize_parse_mode_request((string) ($_POST['parse_mode'] ?? 'text'));
    if (!in_array($parseMode, ['text', 'vision'], true)) {
        $parseMode = 'text';
    }

    cv_import_run_parse_and_redirect($userId, $absolutePath, $relativePath, $parseMode);
}

$textRecommended = !$isNoisy && $routeAuto === 'text_fast';
$visionRecommended = $isNoisy || in_array($routeAuto, ['vision_gpt', 'vision_gpt_forced'], true);
$visionDisabled = !$openAiReady || (!$gptQuota['ok'] && !$isVip);
$quotaDbReady = users_cv_gpt_quota_ready($conn);

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="mb-4">
        <a href="cv-import.php" class="text-success text-decoration-none"><i class="fas fa-arrow-left"></i> Upload lại</a>
        <h3 class="fw-bold mt-2 mb-1"><i class="fas fa-sliders-h text-success"></i> Chọn cách phân tích PDF</h3>
        <p class="text-muted mb-0">
            File: <strong><?= htmlspecialchars($originalName) ?></strong>
            — gợi ý: <strong><?= htmlspecialchars(cv_import_parse_mode_label($routeAuto)) ?></strong>
        </p>
    </div>

    <?php if (!$quotaDbReady && !$isVip): ?>
        <?= cv_import_gpt_quota_migration_hint_html() ?>
    <?php endif; ?>

    <?php if (!$isVip && !$gptQuota['ok']): ?>
        <div class="alert alert-danger border-0 shadow-sm mb-4">
            <strong><i class="fas fa-ban"></i> Đã hết lượt Chuẩn GPT</strong>
            <p class="mb-0 mt-1 small">
                Bạn đã dùng <strong><?= (int) $gptQuota['used'] ?>/<?= (int) $gptQuota['max'] ?></strong> lần Chuẩn GPT trên tài khoản.
                Vẫn có thể dùng <strong>Text-base (Groq)</strong> không giới hạn.
                <strong>Nâng cấp VIP</strong> để Chuẩn GPT không giới hạn (sắp ra mắt).
            </p>
        </div>
    <?php endif; ?>

    <?php if ($isNoisy): ?>
        <div class="alert alert-warning border-0 shadow-sm mb-4">
            <strong><i class="fas fa-exclamation-triangle"></i> PDF có thể là bản scan hoặc layout phức tạp</strong>
            <p class="mb-0 mt-1 small">
                Text trích xuất có thể thiếu hoặc lỗi. Nên dùng <strong>Chuẩn GPT</strong> để đọc trực tiếp từ PDF.
                <?php if (!$isVip && !$gptQuota['ok']): ?>
                    Bạn đã hết lượt Chuẩn GPT — nâng cấp VIP hoặc thử Text-base (kết quả có thể kém).
                <?php endif; ?>
            </p>
        </div>
    <?php endif; ?>

    <div class="row g-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100<?= $textRecommended ? ' border border-success border-2' : '' ?>">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0"><i class="fas fa-bolt text-warning"></i> Text-base</h5>
                        <?php if ($textRecommended): ?>
                            <span class="badge bg-success">Gợi ý</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        Phân tích nhanh bằng Groq AI từ text trích xuất. Phù hợp PDF text sạch.
                        <br><strong>Không giới hạn</strong> số lần dùng.
                    </p>
                    <?php if ($isNoisy): ?>
                        <p class="small text-warning mb-3">
                            PDF này có vẻ không phù hợp Text-base — kết quả có thể thiếu field.
                        </p>
                    <?php endif; ?>
                    <form method="POST" class="mt-auto" id="cv-choose-text-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_import_choose_form')) ?>">
                        <input type="hidden" name="parse_mode" value="text">
                        <button type="submit" class="btn btn-outline-primary w-100" id="cv-choose-text-btn">
                            Dùng Text-base (Groq)
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100<?= $visionRecommended ? ' border border-primary border-2' : '' ?>">
                <div class="card-body p-4 d-flex flex-column">
                    <div class="d-flex justify-content-between align-items-start mb-2">
                        <h5 class="fw-bold mb-0"><i class="fas fa-eye text-primary"></i> Chuẩn GPT</h5>
                        <?php if ($visionRecommended): ?>
                            <span class="badge bg-primary">Gợi ý</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-muted small flex-grow-1">
                        GPT đọc trực tiếp PDF (vision) — tốt cho scan, Canva, layout phức tạp.
                        <?php if ($isVip): ?>
                            <br><strong class="text-success">VIP: không giới hạn</strong>
                        <?php else: ?>
                            <br>Còn lại: <strong><?= (int) $gptQuota['remaining'] ?>/<?= (int) $gptQuota['max'] ?></strong> lần trên tài khoản.
                        <?php endif; ?>
                    </p>
                    <?php if (!$openAiReady): ?>
                        <p class="small text-danger mb-3">Chuẩn GPT chưa được cấu hình trên hệ thống.</p>
                    <?php elseif (!$gptQuota['ok'] && !$isVip): ?>
                        <p class="small text-danger mb-3"><?= htmlspecialchars($gptQuota['message']) ?></p>
                    <?php endif; ?>
                    <form method="POST" class="mt-auto" id="cv-choose-vision-form">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_import_choose_form')) ?>">
                        <input type="hidden" name="parse_mode" value="vision">
                        <button type="submit" class="btn btn-primary w-100" id="cv-choose-vision-btn"<?= $visionDisabled ? ' disabled' : '' ?>>
                            Dùng Chuẩn GPT
                        </button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <p class="text-muted small mt-4 mb-0 text-center">
        Sau khi chọn, hệ thống sẽ phân tích PDF (có thể mất 10–30 giây) rồi chuyển sang chọn mẫu CV.
    </p>
</div>

<script>
(function () {
    function bindLoading(formId, btnId, label) {
        var form = document.getElementById(formId);
        var btn = document.getElementById(btnId);
        if (!form || !btn) {
            return;
        }
        form.addEventListener('submit', function () {
            btn.disabled = true;
            btn.innerHTML = '<span class="spinner-border spinner-border-sm me-1"></span> ' + label;
        });
    }
    bindLoading('cv-choose-text-form', 'cv-choose-text-btn', 'Đang phân tích...');
    bindLoading('cv-choose-vision-form', 'cv-choose-vision-btn', 'Đang phân tích...');
})();
</script>

<?php include '../includes/footer.php'; ?>
