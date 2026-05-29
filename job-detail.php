<?php
// --- PHẦN 1: LOGIC PHP ---
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config/db.php'; 
require_once __DIR__ . '/includes/csrf.php';
include 'includes/header.php';

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$job_id = intval($_GET['id']);

// [LOGIC 1] TĂNG LƯỢT XEM (CHỐNG SPAM F5)
// Chỉ tăng view nếu trong phiên làm việc này chưa xem tin này
if (!isset($_SESSION['viewed_job_' . $job_id])) {
    $stmt_view = $conn->prepare("UPDATE jobs SET view_count = view_count + 1 WHERE id = ?");
    $stmt_view->execute([$job_id]);
    $_SESSION['viewed_job_' . $job_id] = true;
}

// [LOGIC 2] LẤY CHI TIẾT TIN & CÔNG TY
// Join thêm bảng locations để lấy tên thành phố
$sql = "SELECT j.*, c.name as company_name, c.logo, c.address as company_address, l.name as city 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        JOIN locations l ON j.location_id = l.id 
        WHERE j.id = ? AND j.status = 'approved'";
$stmt = $conn->prepare($sql);
$stmt->execute([$job_id]);
$job = $stmt->fetch();

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
$user_role = $_SESSION['role'] ?? 'guest';
$candidate_id = 0;

if (isset($_SESSION['user_id']) && $user_role == 'candidate') {
    $stmt_cand = $conn->prepare("SELECT id FROM candidates WHERE user_id = ?");
    $stmt_cand->execute([$_SESSION['user_id']]);
    $candidate = $stmt_cand->fetch();
    
    if ($candidate) {
        $candidate_id = $candidate['id'];
        $check = $conn->prepare("SELECT id FROM applications WHERE job_id = ? AND candidate_id = ?");
        $check->execute([$job_id, $candidate_id]);
        if ($check->rowCount() > 0) $has_applied = true;
    }
}
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
                <?php if ($user_role == 'employer'): ?>
                    <button class="btn btn-secondary w-100 disabled">Dành cho ứng viên</button>
                <?php elseif ($has_applied): ?>
                    <button class="btn btn-success w-100 disabled"><i class="fas fa-check-circle"></i> Đã ứng tuyển</button>
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
                    <div class="text-secondary text-justify">
                        <?= nl2br(htmlspecialchars($job['description'])) ?>
                    </div>

                    <div class="content-label">Yêu cầu ứng viên</div>
                    <div class="text-secondary text-justify">
                        <?= nl2br(htmlspecialchars($job['requirements'])) ?>
                    </div>

                    <div class="content-label">Quyền lợi được hưởng</div>
                    <div class="text-secondary text-justify">
                        <?= nl2br(htmlspecialchars($job['benefits'])) ?>
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

<?php if ($user_role == 'candidate' && !$has_applied): ?>
<div class="modal fade" id="applyModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <form action="apply.php" method="POST" enctype="multipart/form-data" class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fs-6">Ứng tuyển: <strong><?= htmlspecialchars($job['title']) ?></strong></h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="job_id" value="<?= $job['id'] ?>">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('apply_job_form')) ?>">
                
                <p class="small text-muted mb-3">Vui lòng kiểm tra kỹ thông tin trước khi ứng tuyển.</p>

                <?php 
                    $stmt_cv = $conn->prepare("SELECT cv_path FROM candidates WHERE id = ?");
                    $stmt_cv->execute([$candidate_id]);
                    $online_cv = $stmt_cv->fetchColumn();
                ?>

                <div class="mb-3">
                    <label class="form-label fw-bold">Chọn CV <span class="text-danger">*</span></label>
                    
                    <div class="form-check card p-2 mb-2 bg-light border-0">
                        <input class="form-check-input ms-1 mt-2" type="radio" name="cv_type" value="online" id="cv_online" <?= $online_cv ? 'checked' : 'disabled' ?>>
                        <label class="form-check-label ms-2" for="cv_online">
                            <div>Dùng CV đã tải lên hồ sơ</div>
                            <?php if($online_cv): ?>
                                <small><a href="<?= $online_cv ?>" target="_blank" class="text-primary text-decoration-none"><i class="fas fa-eye"></i> Xem CV hiện tại</a></small>
                            <?php else: ?>
                                <small class="text-danger fst-italic">(Bạn chưa cập nhật CV trong hồ sơ)</small>
                            <?php endif; ?>
                        </label>
                    </div>

                    <div class="form-check card p-2 border-0 bg-light">
                        <input class="form-check-input ms-1 mt-2" type="radio" name="cv_type" value="upload" id="cv_upload" <?= !$online_cv ? 'checked' : '' ?>>
                        <label class="form-check-label ms-2 w-100" for="cv_upload">
                            <div>Tải lên CV mới (PDF, DOC, DOCX)</div>
                            <input type="file" name="new_cv" class="form-control form-control-sm mt-2" id="cv_file_input" accept=".pdf,.doc,.docx" <?= $online_cv ? 'disabled' : '' ?>>
                        </label>
                    </div>
                </div>

                <div class="mb-3">
                    <label class="form-label fw-bold">Thư giới thiệu (Cover Letter)</label>
                    <textarea name="cover_letter" class="form-control" rows="4" placeholder="Viết ngắn gọn lý do tại sao bạn phù hợp với vị trí này..."></textarea>
                </div>
            </div>
            <div class="modal-footer border-top-0 pt-0">
                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-primary fw-bold px-4">Gửi hồ sơ</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Script xử lý bật/tắt input file khi chọn loại CV
    document.addEventListener("DOMContentLoaded", function() {
        const fileInput = document.getElementById('cv_file_input');
        const radios = document.querySelectorAll('input[name="cv_type"]');

        radios.forEach(radio => {
            radio.addEventListener('change', function() {
                if (this.value === 'upload') {
                    fileInput.disabled = false;
                    fileInput.required = true;
                } else {
                    fileInput.disabled = true;
                    fileInput.required = false;
                    fileInput.value = ''; // Reset file
                }
            });
        });
        
        // Kích hoạt trạng thái ban đầu (nếu load trang mà đang chọn upload)
        if(document.getElementById('cv_upload').checked) {
             fileInput.disabled = false;
             fileInput.required = true;
        }
    });
</script>
<?php endif; ?>

<?php include 'includes/footer.php'; ?>