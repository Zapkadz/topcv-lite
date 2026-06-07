<?php
// File: employer/dashboard.php
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/schema_applications_cv.php';
require_once __DIR__ . '/../includes/services/ApplicationService.php';
include '../includes/header.php';
include 'auth_check.php';

$user_id = $_SESSION['user_id'];

// 1. KIỂM TRA HỒ SƠ CÔNG TY
// Nếu chưa tạo hồ sơ công ty thì không cho vào Dashboard chính, bắt sang trang tạo công ty
$stmt = $conn->prepare("SELECT * FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    echo "<script>
        Swal.fire({
            icon: 'info',
            title: 'Chào mừng Nhà tuyển dụng mới!',
            text: 'Vui lòng cập nhật thông tin Công ty để bắt đầu đăng tin.',
            confirmButtonText: 'Cập nhật ngay'
        }).then((result) => {
            window.location.href = 'company.php';
        });
    </script>";
    exit(); // Dừng trang tại đây
}

$company_id = $company['id'];

// 2. LẤY THỐNG KÊ
// Đếm số tin đang hiển thị (Approved)
$sql_jobs = 'SELECT COUNT(*) FROM jobs WHERE company_id = ? AND status = \'approved\' AND deleted_at IS NULL';
$stmt_jobs = $conn->prepare($sql_jobs);
$stmt_jobs->execute([$company_id]);
$active_jobs = $stmt_jobs->fetchColumn();

// Đếm tổng số CV đã nhận (Tất cả các tin)
$sql_apps = "SELECT COUNT(*) FROM applications app 
             JOIN jobs j ON app.job_id = j.id 
             WHERE j.company_id = ?";
$stmt_apps = $conn->prepare($sql_apps);
$stmt_apps->execute([$company_id]);
$total_cv = $stmt_apps->fetchColumn();

// Đếm số CV MỚI (Trạng thái Pending) - Cần xử lý gấp
$sql_new_apps = "SELECT COUNT(*) FROM applications app 
                 JOIN jobs j ON app.job_id = j.id 
                 WHERE j.company_id = ? AND app.status = 'pending'";
$stmt_new_apps = $conn->prepare($sql_new_apps);
$stmt_new_apps->execute([$company_id]);
$new_cv = $stmt_new_apps->fetchColumn();

$screening_pending = 0;
if (applications_cv_columns_ready($conn)) {
    $screening_pending = ApplicationService::countPendingForScreeningHub($conn, (int) $company_id);
} else {
    $screening_pending = (int) $new_cv;
}

?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-success mb-0">Bảng tin tuyển dụng</h3>
            <p class="text-muted small mb-0">Xin chào, <strong><?= htmlspecialchars($company['name']) ?></strong></p>
        </div>
        <div>
            <a href="job-create.php" class="btn btn-success fw-bold shadow-sm">
                <i class="fas fa-plus-circle"></i> Đăng tin mới
            </a>
            <a href="company.php" class="btn btn-outline-secondary ms-2">
                <i class="fas fa-cog"></i> Cấu hình
            </a>
        </div>
    </div>

    <div class="row g-4 mb-5">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-success">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">Tin đang hiện</p>
                            <h2 class="mb-0 fw-bold text-dark"><?= $active_jobs ?></h2>
                        </div>
                        <div class="bg-light p-3 rounded-circle text-success">
                            <i class="fas fa-briefcase fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-warning">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">CV chờ duyệt</p>
                            <h2 class="mb-0 fw-bold text-warning"><?= $new_cv ?></h2>
                        </div>
                        <div class="bg-light p-3 rounded-circle text-warning">
                            <i class="fas fa-user-clock fa-lg"></i>
                        </div>
                    </div>
                    <?php if($new_cv > 0): ?>
                        <div class="mt-2">
                            <a href="applicants.php" class="small text-decoration-none text-warning fw-bold">Xử lý ngay <i class="fas fa-arrow-right"></i></a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <div class="card border-0 shadow-sm h-100 border-start border-4 border-primary">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <p class="text-muted mb-1 small fw-bold text-uppercase">Tổng CV</p>
                            <h2 class="mb-0 fw-bold text-primary"><?= $total_cv ?></h2>
                        </div>
                        <div class="bg-light p-3 rounded-circle text-primary">
                            <i class="fas fa-file-alt fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-md-3">
            <a href="candidate_screening.php" class="text-decoration-none d-block h-100">
                <div class="card border-0 shadow-sm h-100 border-start border-4 border-info hover-bg-light">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-center">
                            <div>
                                <p class="text-muted mb-1 small fw-bold text-uppercase">Sàng lọc ứng viên</p>
                                <h2 class="mb-0 fw-bold text-info"><?= (int) $screening_pending ?></h2>
                            </div>
                            <div class="bg-light p-3 rounded-circle text-info">
                                <i class="fas fa-user-check fa-lg"></i>
                            </div>
                        </div>
                        <div class="mt-2">
                            <span class="small text-info fw-bold">CV chờ xử lý trên hub <i class="fas fa-arrow-right"></i></span>
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>

    <div class="row">
        <div class="col-md-8">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white fw-bold py-3">
                    <i class="fas fa-bolt text-warning"></i> Thao tác nhanh
                </div>
                <div class="card-body p-4">
                    <div class="row text-center">
                        <div class="col-4">
                            <a href="job-create.php" class="text-decoration-none text-dark d-block p-3 rounded hover-bg-light border">
                                <i class="fas fa-plus-circle fa-2x text-success mb-2"></i>
                                <div class="fw-bold">Đăng tin mới</div>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="manage-jobs.php" class="text-decoration-none text-dark d-block p-3 rounded hover-bg-light border">
                                <i class="fas fa-list-ul fa-2x text-primary mb-2"></i>
                                <div class="fw-bold">Quản lý tin</div>
                            </a>
                        </div>
                        <div class="col-4">
                            <a href="candidate_screening.php" class="text-decoration-none text-dark d-block p-3 rounded hover-bg-light border">
                                <i class="fas fa-user-check fa-2x text-info mb-2"></i>
                                <div class="fw-bold">Sàng lọc ứng viên</div>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="col-md-4">
            <div class="card border-0 shadow-sm bg-gradient-success text-white" style="background: #00b14f;">
                <div class="card-body text-center p-4">
                    <i class="fas fa-gem fa-3x mb-3 text-warning"></i>
                    <h5>Nâng cấp tài khoản?</h5>
                    <p class="small opacity-75">Tiếp cận hàng ngàn ứng viên tiềm năng bằng các gói dịch vụ cao cấp.</p>
                    <button class="btn btn-light text-success fw-bold w-100">Liên hệ Admin</button>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>