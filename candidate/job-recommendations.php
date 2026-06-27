<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/ai_screening_config.php';
require_once __DIR__ . '/../includes/job_recommendation_rules.php';
require_once __DIR__ . '/../includes/ai_i18n.php';
require_once __DIR__ . '/../includes/services/CvService.php';
require_once __DIR__ . '/../includes/services/JobRecommendationService.php';
require_once __DIR__ . '/../includes/repositories/JobRepository.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../login.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);
$cvs = $schemaReady ? CvService::listForUser($conn, $userId) : [];
$openJobCount = $schemaReady ? JobRepository::countOpenForRecommendation($conn) : 0;

$defaultCvId = 0;
foreach ($cvs as $cv) {
    if ((int) ($cv['is_primary'] ?? 0) === 1) {
        $defaultCvId = (int) $cv['id'];
        break;
    }
}
if ($defaultCvId === 0 && $cvs !== []) {
    $defaultCvId = (int) $cvs[0]['id'];
}

$selectedCvId = isset($_GET['cv_id']) ? (int) $_GET['cv_id'] : $defaultCvId;
$validCv = false;
foreach ($cvs as $cv) {
    if ((int) ($cv['id'] ?? 0) === $selectedCvId) {
        $validCv = true;
        break;
    }
}
if (!$validCv) {
    $selectedCvId = $defaultCvId;
}

$panelHint = $schemaReady && $selectedCvId > 0
    ? JobRecommendationService::buildPanelHint($conn, $userId, $selectedCvId)['hint']
    : ($schemaReady ? '' : 'Schema CV chưa sẵn sàng.');

$canRun = $schemaReady
    && $selectedCvId > 0
    && $panelHint === ''
    && ai_screening_config_ready();

$sessionResult = JobRecommendationService::getSessionResult();
$appliedJobIds = [];
if ($sessionResult !== null && $schemaReady) {
    $candidateId = CvService::resolveCandidateId($conn, $userId);
    if ($candidateId !== null) {
        $appliedJobIds = JobRecommendationService::loadAppliedJobIds($conn, $candidateId);
    }
}

include '../includes/header.php';
?>

<div id="recLoadingOverlay" class="rec-loading-overlay d-none" aria-hidden="true">
    <div class="rec-loading-box text-center">
        <div class="spinner-border text-success mb-3" role="status"></div>
        <div class="fw-bold">Đang phân tích CV…</div>
        <p class="small text-muted mb-0">
            Đang so khớp với <?= (int) $openJobCount ?> tin tuyển dụng.
            Có thể mất 1–3 phút — vui lòng không đóng trang.
        </p>
    </div>
</div>

<div class="container py-5">
    <nav aria-label="breadcrumb" class="mb-3">
        <ol class="breadcrumb small">
            <li class="breadcrumb-item"><a href="../index.php">Trang chủ</a></li>
            <li class="breadcrumb-item"><a href="cv-manage.php">Quản lý CV</a></li>
            <li class="breadcrumb-item active" aria-current="page">AI gợi ý việc làm</li>
        </ol>
    </nav>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-robot text-success"></i> AI gợi ý việc làm phù hợp</h3>
            <p class="text-muted mb-0">Phân tích CV hiện tại và gợi ý top công việc phù hợp cùng điểm cần cải thiện.</p>
        </div>
        <a href="cv-manage.php" class="btn btn-outline-success btn-sm"><i class="fas fa-arrow-left"></i> Quản lý CV</a>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php elseif ($cvs === []): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">Chưa có CV online</h5>
                <p class="text-muted">Tạo CV có cấu trúc để AI phân tích chính xác hơn.</p>
                <div class="d-flex flex-wrap justify-content-center gap-2">
                    <a href="<?= htmlspecialchars(cv_template_picker_url()) ?>" class="btn btn-success fw-bold">Tạo CV mới</a>
                    <a href="cv-import.php" class="btn btn-outline-success fw-bold">Tạo từ PDF</a>
                </div>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body">
                <form method="POST" action="run-job-recommendation.php" id="recRunForm" class="row g-3 align-items-end">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_job_recommendation_form')) ?>">
                    <div class="col-md-6">
                        <label class="form-label fw-bold" for="cv_profile_id">Chọn CV để phân tích</label>
                        <select name="cv_profile_id" id="cv_profile_id" class="form-select" onchange="location.href='job-recommendations.php?cv_id=' + this.value;">
                            <?php foreach ($cvs as $cv): ?>
                                <?php
                                $cvId = (int) $cv['id'];
                                $label = (string) ($cv['title'] ?? 'CV');
                                if ((int) ($cv['is_primary'] ?? 0) === 1) {
                                    $label .= ' ★';
                                }
                                $label .= ' — ' . (int) ($cv['completion_percent'] ?? 0) . '%';
                                ?>
                                <option value="<?= $cvId ?>" <?= $cvId === $selectedCvId ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="mt-2">
                            <a href="cv-preview.php?id=<?= (int) $selectedCvId ?>" class="small text-primary" target="_blank" rel="noopener">
                                <i class="fas fa-eye"></i> Xem trước CV đã chọn
                            </a>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="d-flex flex-wrap gap-2 mb-2">
                            <span class="badge bg-light text-dark border">
                                <i class="fas fa-briefcase"></i> <?= (int) $openJobCount ?> tin đang tuyển
                            </span>
                            <?php if ($sessionResult !== null && !empty($sessionResult['ran_at'])): ?>
                                <span class="badge bg-light text-dark border">
                                    <i class="fas fa-clock"></i> Lần chạy: <?= date('d/m/Y H:i', (int) $sessionResult['ran_at']) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                        <?php if ($panelHint !== ''): ?>
                            <p class="small text-warning mb-2"><i class="fas fa-info-circle"></i> <?= htmlspecialchars($panelHint) ?></p>
                        <?php endif; ?>
                        <button type="submit" class="btn btn-success fw-bold" id="recRunBtn" <?= $canRun ? '' : 'disabled' ?>>
                            <i class="fas fa-magic"></i> Chạy AI gợi ý
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <?php if ($sessionResult !== null && !empty($sessionResult['ran_at'])): ?>
            <?php include __DIR__ . '/../includes/partials/job_recommendation_results.php'; ?>
        <?php endif; ?>
    <?php endif; ?>
</div>

<div class="modal fade" id="recDetailModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-lg modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header align-items-start">
                <div class="me-auto pe-3">
                    <h5 class="modal-title fw-bold mb-1" id="recDetailTitle">Recommendation detail</h5>
                    <div class="small text-muted" id="recDetailMeta"></div>
                </div>
                <div class="d-flex align-items-center gap-2 flex-shrink-0">
                    <div class="btn-group btn-group-sm ai-lang-toggle" role="group" id="recDetailLangToggle">
                        <button type="button" class="btn btn-outline-secondary active" data-lang="en">English</button>
                        <button type="button" class="btn btn-outline-secondary" data-lang="vi">Tiếng Việt</button>
                    </div>
                    <button type="button" class="btn-close mt-1" data-bs-dismiss="modal"></button>
                </div>
            </div>
            <div class="modal-body" id="recDetailBody"></div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>

<style>
.rec-loading-overlay {
    position: fixed;
    inset: 0;
    background: rgba(255, 255, 255, 0.85);
    z-index: 2000;
    display: flex;
    align-items: center;
    justify-content: center;
}
.rec-loading-box {
    max-width: 360px;
    padding: 1.5rem;
}
</style>

<?= ai_i18n_script_tags() ?>

<script>
document.getElementById('recRunForm')?.addEventListener('submit', function () {
    var overlay = document.getElementById('recLoadingOverlay');
    var btn = document.getElementById('recRunBtn');
    if (overlay) {
        overlay.classList.remove('d-none');
    }
    if (btn) {
        btn.disabled = true;
    }
});

document.querySelectorAll('.js-rec-detail').forEach(function (btn) {
    btn.addEventListener('click', function () {
        var raw = btn.getAttribute('data-job');
        if (!raw) {
            return;
        }
        var job;
        try {
            job = JSON.parse(raw);
        } catch (e) {
            return;
        }
        if (window.TopCvAiUiI18n) {
            TopCvAiUiI18n.openCandidateModal(job);
        }
    });
});
</script>

<?php include '../includes/footer.php'; ?>
