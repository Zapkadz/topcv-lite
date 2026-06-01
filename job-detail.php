<?php
// --- PHẦN 1: LOGIC PHP ---
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config/db.php'; 
require_once __DIR__ . '/includes/csrf.php';
require_once __DIR__ . '/includes/job_rules.php';
require_once __DIR__ . '/includes/html_content.php';
require_once __DIR__ . '/includes/repositories/JobRepository.php';
require_once __DIR__ . '/includes/schema_saved_jobs.php';
require_once __DIR__ . '/includes/services/SavedJobService.php';
require_once __DIR__ . '/includes/schema_cvs.php';
require_once __DIR__ . '/includes/schema_applications_cv.php';
require_once __DIR__ . '/includes/services/CvService.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$job_id = intval($_GET['id']);

// [LOGIC 1] TĂNG LƯỢT XEM (CHỐNG SPAM F5)
// Chỉ tăng view nếu trong phiên làm việc này chưa xem tin này
$job = JobRepository::findPublicById($conn, $job_id);

if ($job && !isset($_SESSION['viewed_job_' . $job_id])) {
    $stmt_view = $conn->prepare(
        'UPDATE jobs SET view_count = view_count + 1 WHERE id = ? AND deleted_at IS NULL'
    );
    $stmt_view->execute([$job_id]);
    $_SESSION['viewed_job_' . $job_id] = true;
    $job = JobRepository::findPublicById($conn, $job_id);
}

$job_expired = $job ? job_is_expired($job['deadline']) : false;
$job_open_for_apply = $job ? job_is_open_for_apply($job) : false;

if (!$job) {
    echo "<div class='container py-5 text-center'>
            <img src='https://cdn-icons-png.flaticon.com/512/2748/2748558.png' width='100' class='mb-3'>
            <h3 class='text-muted'>Tin tuyển dụng không tồn tại hoặc đã bị ẩn.</h3>
            <a href='index.php' class='btn btn-primary mt-3'>Quay về trang chủ</a>
          </div>";
    include 'includes/footer.php';
    exit();
}

// [LOGIC 3] KIỂM TRA TRẠNG THÁI ỨNG TUYỂN
$has_applied = false;
$job_is_saved = false;
$user_role = $_SESSION['role'] ?? 'guest';
$candidate_id = 0;

if (isset($_SESSION['user_id']) && $user_role == 'candidate') {
    $stmt_cand = $conn->prepare("SELECT id FROM candidates WHERE user_id = ?");
    $stmt_cand->execute([$_SESSION['user_id']]);
    $candidate = $stmt_cand->fetch();
    
    if ($candidate) {
        $candidate_id = (int) $candidate['id'];
        $check = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
        $check->execute([$job_id, $candidate_id]);
        if ($check->rowCount() > 0) {
            $has_applied = true;
        }
        if (saved_jobs_schema_ready($conn)) {
            $job_is_saved = SavedJobService::isSaved($conn, $candidate_id, $job_id);
        }
    }
}

$cv_list = [];
$cv_schema_ready = false;
$default_cv_id = 0;
if ($user_role === 'candidate' && isset($_SESSION['user_id']) && cvs_schema_ready($conn)) {
    $cv_schema_ready = true;
    $cv_list = CvService::listForUser($conn, (int) $_SESSION['user_id']);
    foreach ($cv_list as $cv) {
        if ((int) ($cv['is_primary'] ?? 0) === 1) {
            $default_cv_id = (int) $cv['id'];
            break;
        }
    }
    if ($default_cv_id === 0 && count($cv_list) > 0) {
        $default_cv_id = (int) $cv_list[0]['id'];
    }
}

include 'includes/header.php';
?>

<style>
    /* CSS Tùy chỉnh cho trang Job Detail */
    .job-header { background: #f8f9fa; border-bottom: 1px solid #dee2e6; }
    .company-logo-box { width: 100px; height: 100px; object-fit: contain; background: #fff; padding: 5px; border: 1px solid #eee; border-radius: 8px; }
    .info-icon { width: 24px; text-align: center; margin-right: 10px; color: #0d6efd; }
    .sticky-sidebar { position: -webkit-sticky; position: sticky; top: 20px; }
    .content-label { font-weight: 700; color: #212529; margin-top: 1.5rem; margin-bottom: 0.5rem; border-left: 4px solid #0d6efd; padding-left: 10px; }
    .bg-soft-primary { background-color: #e7f1ff; color: #0c63e4; }
</style>

<div class="job-header py-4">
    <div class="container">
        <nav aria-label="breadcrumb">
            <ol class="breadcrumb small">
                <li class="breadcrumb-item"><a href="index.php">Trang chủ</a></li>
                <li class="breadcrumb-item"><a href="jobs.php">Việc làm</a></li>
                <li class="breadcrumb-item active" aria-current="page"><?= htmlspecialchars($job['title']) ?></li>
            </ol>
        </nav>
        
        <?php if ($job_expired): ?>
        <div class="alert alert-warning mt-3 mb-0 py-2 small">
            <i class="fas fa-exclamation-triangle me-1"></i> Tin tuyển dụng đã hết hạn nộp hồ sơ (hạn: <?= date('d/m/Y', strtotime($job['deadline'])) ?>).
        </div>
        <?php endif; ?>

        <div class="row align-items-center mt-3">
            <div class="col-md-auto text-center text-md-start mb-3 mb-md-0">
                <img src="<?= !empty($job['logo']) ? $job['logo'] : 'uploads/default-logo.png' ?>" 
                     class="company-logo-box shadow-sm" alt="Logo">
            </div>
            <div class="col-md">
                <h4 class="fw-bold text-dark mb-1"><?= htmlspecialchars($job['title']) ?></h4>
                <div class="text-secondary fw-bold mb-2"><?= htmlspecialchars($job['company_name']) ?></div>
                <div class="d-flex flex-wrap gap-3 text-muted small">
                    <span><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['city']) ?></span>
                    <span><i class="fas fa-clock me-1"></i> Hạn nộp: <?= date('d/m/Y', strtotime($job['deadline'])) ?></span>
                    <span><i class="fas fa-eye me-1"></i> <?= $job['view_count'] ?> lượt xem</span>
                </div>
            </div>
            <div class="col-md-auto mt-3 mt-md-0">
                <?php if ($user_role === 'candidate' && saved_jobs_schema_ready($conn)): ?>
                    <form method="POST" action="candidate/toggle-save-job.php" class="mb-2">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_save_job_form')) ?>">
                        <input type="hidden" name="job_id" value="<?= (int) $job_id ?>">
                        <button type="submit" class="btn btn-outline-warning w-100 fw-bold">
                            <i class="fas fa-bookmark<?= $job_is_saved ? '' : '-o' ?>"></i>
                            <?= $job_is_saved ? 'Bỏ lưu tin' : 'Lưu tin' ?>
                        </button>
                    </form>
                <?php endif; ?>
                <?php if ($user_role == 'employer'): ?>
                    <button class="btn btn-secondary w-100 disabled">Dành cho ứng viên</button>
                <?php elseif ($has_applied): ?>
                    <button class="btn btn-success w-100 disabled"><i class="fas fa-check-circle"></i> Đã ứng tuyển</button>
                <?php elseif ($job_expired): ?>
                    <button class="btn btn-secondary w-100 disabled">Tin đã hết hạn nộp hồ sơ</button>
                <?php elseif ($job['status'] !== 'approved'): ?>
                    <button class="btn btn-secondary w-100 disabled">Tin chưa được duyệt</button>
                <?php elseif (!isset($_SESSION['user_id'])): ?>
                    <a href="login.php" class="btn btn-primary w-100 px-4 fw-bold">Đăng nhập để ứng tuyển</a>
                <?php else: ?>
                    <button class="btn btn-primary w-100 px-4 fw-bold shadow-sm" data-bs-toggle="modal" data-bs-target="#applyModal">
                        <i class="fas fa-paper-plane me-1"></i> Ứng tuyển ngay
                    </button>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<div class="container py-4">
    <div class="row">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-3 mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold mb-3">Chi tiết tin tuyển dụng</h5>
                    
                    <div class="d-flex flex-wrap gap-2 mb-4">
                        <span class="badge bg-soft-primary px-3 py-2 rounded-pill">
                            <i class="fas fa-dollar-sign"></i> <?= htmlspecialchars($job['salary_range']) ?>
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                            <i class="fas fa-map-marker-alt text-danger"></i> <?= htmlspecialchars($job['city']) ?>
                        </span>
                        <span class="badge bg-light text-dark border px-3 py-2 rounded-pill">
                            <i class="fas fa-hourglass-half text-warning"></i> <?= htmlspecialchars($job['job_type']) ?>
                        </span>
                    </div>

                    <div class="content-label">Mô tả công việc</div>
                    <div class="text-secondary text-justify job-html-content">
                        <?= html_display($job['description'] ?? '') ?>
                    </div>

                    <div class="content-label">Yêu cầu ứng viên</div>
                    <div class="text-secondary text-justify job-html-content">
                        <?= html_display($job['requirements'] ?? '') ?>
                    </div>

                    <div class="content-label">Quyền lợi được hưởng</div>
                    <div class="text-secondary text-justify job-html-content">
                        <?= html_display($job['benefits'] ?? '') ?>
                    </div>

                    <div class="content-label">Địa điểm làm việc</div>
                    <div class="text-secondary">
                        <i class="fas fa-building me-2 text-muted"></i> <?= htmlspecialchars($job['company_address']) ?>
                    </div>
                    
                    <div class="alert alert-warning mt-4 small border-0">
                        <i class="fas fa-exclamation-triangle me-1"></i> 
                        <strong>Lưu ý:</strong> Hãy cẩn thận với các yêu cầu nộp phí tuyển dụng. JobBoard không bao giờ yêu cầu ứng viên nộp phí.
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="sticky-sidebar">
                <div class="card border-0 shadow-sm rounded-3 mb-3">
                    <div class="card-header bg-white fw-bold py-3 border-bottom-0">
                        Thông tin chung
                    </div>
                    <div class="card-body pt-0">
                        <ul class="list-unstyled mb-0">
                            <li class="d-flex align-items-center mb-3">
                                <span class="info-icon"><i class="fas fa-briefcase"></i></span>
                                <div>
                                    <small class="text-muted d-block">Cấp bậc</small>
                                    <strong><?= htmlspecialchars($job['job_level']) ?></strong>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <span class="info-icon"><i class="fas fa-star"></i></span>
                                <div>
                                    <small class="text-muted d-block">Kinh nghiệm</small>
                                    <strong><?= htmlspecialchars($job['experience']) ?></strong>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <span class="info-icon"><i class="fas fa-user-friends"></i></span>
                                <div>
                                    <small class="text-muted d-block">Số lượng tuyển</small>
                                    <strong><?= $job['quantity'] ?> người</strong>
                                </div>
                            </li>
                            <li class="d-flex align-items-center mb-3">
                                <span class="info-icon"><i class="fas fa-venus-mars"></i></span>
                                <div>
                                    <small class="text-muted d-block">Giới tính</small>
                                    <strong><?= htmlspecialchars($job['gender']) ?></strong>
                                </div>
                            </li>
                            <li class="d-flex align-items-center">
                                <span class="info-icon"><i class="fas fa-suitcase"></i></span>
                                <div>
                                    <small class="text-muted d-block">Hình thức</small>
                                    <strong><?= htmlspecialchars($job['job_type']) ?></strong>
                                </div>
                            </li>
                        </ul>
                    </div>
                </div>

                <div class="card border-0 shadow-sm rounded-3">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <img src="<?= !empty($job['logo']) ? $job['logo'] : 'uploads/default-logo.png' ?>" width="50" class="rounded border me-2">
                            <div class="fw-bold text-truncate"><?= htmlspecialchars($job['company_name']) ?></div>
                        </div>
                        <p class="small text-muted mb-3">
                            <i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($job['company_address']) ?>
                        </p>
                        <a href="company-detail.php?id=<?= $job['company_id'] ?>" class="btn btn-outline-primary btn-sm w-100">Xem trang công ty</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php if ($user_role == 'candidate' && !$has_applied && $job_open_for_apply): ?>
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="apply.php" method="POST" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fs-6">Ứng tuyển: <strong><?= htmlspecialchars($job['title']) ?></strong></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('apply_job_form')) ?>">

                <p class="small text-muted mb-3">Chọn một CV từ Quản lý CV online. Hệ thống lưu bản CV tại thời điểm nộp.</p>

                <?php if (!$cv_schema_ready): ?>
                    <?= applications_cv_migration_hint_html() ?>
                <?php elseif (count($cv_list) === 0): ?>
                    <div class="alert alert-warning">
                        Bạn chưa có CV online.
                        <a href="candidate/cv-manage.php" class="alert-link">Tạo CV ngay</a>
                    </div>
                <?php else: ?>
                    <div class="mb-3">
                        <label class="form-label fw-bold" for="cv_profile_id">Chọn CV để nộp <span class="text-danger">*</span></label>
                        <select name="cv_profile_id" id="cv_profile_id" class="form-select" required>
                            <?php foreach ($cv_list as $cv): ?>
                                <?php
                                $cvId = (int) $cv['id'];
                                $isPrimary = (int) ($cv['is_primary'] ?? 0) === 1;
                                $label = (string) $cv['title'] . ($isPrimary ? ' ★' : '');
                                ?>
                                <option value="<?= $cvId ?>" <?= $cvId === $default_cv_id ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($label) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <small class="text-muted d-block mt-1">★ = CV mặc định</small>
                        <a href="#" id="apply-preview-cv-link" class="small text-primary" target="_blank" rel="noopener">
                            <i class="fas fa-eye"></i> Xem trước CV đã chọn
                        </a>
                    </div>
                <?php endif; ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Thư giới thiệu (Cover Letter)</label>
                    <textarea name="cover_letter" class="form-control" rows="4"
                        placeholder="Viết ngắn gọn lý do tại sao bạn phù hợp với vị trí này..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary fw-bold px-4"
                    <?= (!$cv_schema_ready || count($cv_list) === 0) ? 'disabled' : '' ?>>
                    Gửi hồ sơ
                </button>
            </div>
        </form>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function () {
    var select = document.getElementById('cv_profile_id');
    var link = document.getElementById('apply-preview-cv-link');
    if (!select || !link) {
        return;
    }
    function updatePreviewLink() {
        link.href = 'candidate/cv-preview.php?id=' + encodeURIComponent(select.value);
    }
    select.addEventListener('change', updatePreviewLink);
    updatePreviewLink();
});
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>