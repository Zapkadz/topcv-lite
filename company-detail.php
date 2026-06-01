<?php
// --- PHẦN 1: LOGIC PHP ---
if (session_status() === PHP_SESSION_NONE) session_start();
include 'config/db.php';
require_once __DIR__ . '/includes/job_rules.php';
include 'includes/header.php';

// 1. Kiểm tra ID công ty
if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    header("Location: index.php");
    exit();
}

$company_id = intval($_GET['id']);

// 2. Lấy thông tin Công ty
$stmt = $conn->prepare("SELECT * FROM companies WHERE id = ?");
$stmt->execute([$company_id]);
$comp = $stmt->fetch();

if (!$comp) {
    echo "<div class='container py-5 text-center'><h3>Công ty không tồn tại.</h3></div>";
    include 'includes/footer.php';
    exit();
}

// 3. LOGIC KIỂM TRA ỨNG TUYỂN (Để hiển thị trạng thái "Đã ứng tuyển")
$applied_jobs = []; // Mảng chứa ID các job đã nộp
if (isset($_SESSION['user_id']) && isset($_SESSION['role']) && $_SESSION['role'] == 'candidate') {
    // Lấy ID ứng viên
    $stmt_cand = $conn->prepare("SELECT id FROM candidates WHERE user_id = ?");
    $stmt_cand->execute([$_SESSION['user_id']]);
    $cand = $stmt_cand->fetch();
    
    if ($cand) {
        // Lấy tất cả job_id mà ứng viên này đã nộp tại công ty này
        $stmt_check = $conn->prepare("
            SELECT job_id FROM applications a
            JOIN jobs j ON a.job_id = j.id
            WHERE a.candidate_id = ? AND j.company_id = ?
        ");
        $stmt_check->execute([$cand['id'], $company_id]);
        $applied_jobs = $stmt_check->fetchAll(PDO::FETCH_COLUMN, 0); // Lấy về mảng [1, 5, 8...]
    }
}

// 4. CẤU HÌNH PHÂN TRANG
$limit = 5; // Số tin mỗi trang
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;
$offset = ($page - 1) * $limit;

// Đếm tổng số tin active
$stmt_count = $conn->prepare(
    'SELECT COUNT(*) FROM jobs WHERE company_id = ? AND status = \'approved\' AND deadline >= CURDATE() AND ' . job_sql_not_deleted()
);
$stmt_count->execute([$company_id]);
$total_records = $stmt_count->fetchColumn();
$total_pages = ceil($total_records / $limit);

// 5. Lấy danh sách việc làm (Có Limit & Offset)
$sql_jobs = 'SELECT j.*, l.name AS location_name
             FROM jobs j
             JOIN locations l ON j.location_id = l.id
             WHERE j.company_id = ? AND j.status = \'approved\' AND j.deadline >= CURDATE() AND ' . job_sql_not_deleted('j') . '
             ORDER BY j.created_at DESC
             LIMIT ' . (int) $limit . ' OFFSET ' . (int) $offset;

$stmt_jobs = $conn->prepare($sql_jobs);
$stmt_jobs->execute([$company_id]);
$jobs = $stmt_jobs->fetchAll();
?>

<style>
    /* CSS Tùy chỉnh màu Xanh lá */
    .company-banner {
        /* Gradient xanh lá cây */
        background: linear-gradient(135deg, #198754 0%, #0f5132 100%); 
        color: white;
        padding-top: 3rem;
        padding-bottom: 3rem;
    }
    .comp-logo-large {
        width: 120px;
        height: 120px;
        object-fit: contain;
        background: #fff;
        border: 4px solid rgba(255,255,255,0.2);
        border-radius: 12px;
        padding: 5px;
    }
    .job-card-hover:hover {
        transform: translateY(-3px);
        box-shadow: 0 .5rem 1rem rgba(0,0,0,.10)!important;
        transition: all 0.2s;
        border-color: #198754 !important; /* Viền xanh khi hover */
    }
    .icon-box {
        width: 32px;
        text-align: center;
        color: #198754; /* Icon xanh lá */
    }
    .text-success-custom {
        color: #198754 !important;
    }
    .page-link {
        color: #198754;
    }
    .page-item.active .page-link {
        background-color: #198754;
        border-color: #198754;
    }
</style>

<div class="company-banner">
    <div class="container">
        <div class="row align-items-center">
            <div class="col-md-auto text-center mb-3 mb-md-0">
                <img src="<?= !empty($comp['logo']) ? $comp['logo'] : 'uploads/default-logo.png' ?>" 
                     class="comp-logo-large shadow" alt="Logo">
            </div>
            <div class="col-md">
                <h2 class="fw-bold mb-1"><?= htmlspecialchars($comp['name']) ?></h2>
                <div class="d-flex flex-wrap gap-3 opacity-75 mb-3">
                    <?php if(!empty($comp['website'])): ?>
                        <span><i class="fas fa-globe me-1"></i> <a href="<?= htmlspecialchars($comp['website']) ?>" target="_blank" class="text-white text-decoration-none">Website công ty</a></span>
                    <?php endif; ?>
                    <span><i class="fas fa-map-marker-alt me-1"></i> <?= htmlspecialchars($comp['address']) ?></span>
                </div>
            </div>
            <div class="col-md-auto">
                <button class="btn btn-light fw-bold shadow-sm text-success">
                    <i class="fas fa-plus me-1"></i> Theo dõi công ty
                </button>
            </div>
        </div>
    </div>
</div>

<div class="container py-5">
    <div class="row">
        <div class="col-lg-8">
            
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h5 class="fw-bold border-start border-4 border-success ps-3 mb-3">Giới thiệu công ty</h5>
                    <div class="text-secondary text-justify">
                        <?= nl2br($comp['description']) ?>
                    </div>
                </div>
            </div>

            <h5 class="fw-bold border-start border-4 border-success ps-3 mb-3">
                Việc làm đang tuyển (<?= $total_records ?>)
            </h5>
            
            <?php if (count($jobs) > 0): ?>
                <div class="row">
                    <?php foreach ($jobs as $job): ?>
                        <?php 
                            // Kiểm tra xem job này có trong danh sách đã nộp không
                            $is_applied = in_array($job['id'], $applied_jobs);
                        ?>
                        <div class="col-12 mb-3">
                            <div class="card border border-light shadow-sm job-card-hover h-100">
                                <div class="card-body">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="w-75">
                                            <h5 class="fw-bold mb-1">
                                                <a href="job-detail.php?id=<?= $job['id'] ?>" class="text-dark text-decoration-none stretched-link">
                                                    <?= htmlspecialchars($job['title']) ?>
                                                </a>
                                            </h5>
                                            <div class="text-muted small mb-2 mt-2">
                                                <span class="me-3"><i class="fas fa-dollar-sign text-success"></i> <span class="fw-bold text-success-custom"><?= htmlspecialchars($job['salary_range']) ?></span></span>
                                                <span class="me-3"><i class="fas fa-map-marker-alt text-secondary"></i> <?= htmlspecialchars($job['location_name']) ?></span>
                                                <span><i class="fas fa-clock text-secondary"></i> Hạn nộp: <?= date('d/m/Y', strtotime($job['deadline'])) ?></span>
                                            </div>
                                            <div class="mt-2">
                                                <span class="badge bg-light text-dark border me-1"><?= $job['job_type'] ?></span>
                                                <span class="badge bg-light text-dark border"><?= $job['job_level'] ?></span>
                                            </div>
                                        </div>
                                        
                                        <div class="text-end" style="z-index: 2; position: relative;">
                                            <?php if ($is_applied): ?>
                                                <button class="btn btn-secondary btn-sm disabled" title="Bạn đã nộp hồ sơ cho vị trí này">
                                                    <i class="fas fa-check-circle"></i> Đã ứng tuyển
                                                </button>
                                            <?php else: ?>
                                                <a href="job-detail.php?id=<?= $job['id'] ?>" class="btn btn-sm btn-outline-success fw-bold">
                                                    Ứng tuyển ngay
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                </div>

                <?php if ($total_pages > 1): ?>
                <nav aria-label="Page navigation" class="mt-4">
                    <ul class="pagination justify-content-center">
                        <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?id=<?= $company_id ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                                <span aria-hidden="true">&laquo;</span>
                            </a>
                        </li>
                        
                        <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                            <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                                <a class="page-link" href="?id=<?= $company_id ?>&page=<?= $i ?>"><?= $i ?></a>
                            </li>
                        <?php endfor; ?>
                        
                        <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                            <a class="page-link" href="?id=<?= $company_id ?>&page=<?= $page + 1 ?>" aria-label="Next">
                                <span aria-hidden="true">&raquo;</span>
                            </a>
                        </li>
                    </ul>
                </nav>
                <?php endif; ?>

            <?php else: ?>
                <div class="alert alert-light text-center border py-4">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" width="60" class="opacity-50 mb-3">
                    <p class="mb-0 text-muted">Hiện tại công ty chưa có tin tuyển dụng nào khác.</p>
                </div>
            <?php endif; ?>

        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm sticky-top" style="top: 20px; z-index: 1;">
                <div class="card-header bg-white fw-bold py-3 text-success">
                    Thông tin liên hệ
                </div>
                <div class="card-body">
                    <ul class="list-unstyled mb-0">
                        <li class="d-flex mb-3">
                            <div class="icon-box"><i class="fas fa-map-marker-alt"></i></div>
                            <div>
                                <small class="text-muted d-block">Địa chỉ</small>
                                <span><?= htmlspecialchars($comp['address']) ?></span>
                            </div>
                        </li>
                        
                        <?php if(!empty($comp['phone'])): ?>
                        <li class="d-flex mb-3">
                            <div class="icon-box"><i class="fas fa-phone-alt"></i></div>
                            <div>
                                <small class="text-muted d-block">Điện thoại</small>
                                <span><?= htmlspecialchars($comp['phone']) ?></span>
                            </div>
                        </li>
                        <?php endif; ?>

                        <?php if(!empty($comp['email'])): ?>
                        <li class="d-flex mb-3">
                            <div class="icon-box"><i class="fas fa-envelope"></i></div>
                            <div>
                                <small class="text-muted d-block">Email</small>
                                <span><?= htmlspecialchars($comp['email']) ?></span>
                            </div>
                        </li>
                        <?php endif; ?>
                        
                        <li class="d-flex">
                            <div class="icon-box"><i class="fas fa-users"></i></div>
                            <div>
                                <small class="text-muted d-block">Quy mô công ty</small>
                                <span><?= isset($comp['scale']) ? htmlspecialchars($comp['scale']) : 'Đang cập nhật' ?></span>
                            </div>
                        </li>
                    </ul>

                    <?php if(!empty($comp['website'])): ?>
                        <hr>
                        <a href="<?= htmlspecialchars($comp['website']) ?>" target="_blank" class="btn btn-outline-success fw-bold w-100">
                            <i class="fas fa-external-link-alt"></i> Truy cập Website
                        </a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include 'includes/footer.php'; ?>