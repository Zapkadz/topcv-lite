<?php
// --- PHẦN 1: LOGIC PHP (Xử lý dữ liệu) ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/schema_jobs.php';
require_once __DIR__ . '/../includes/services/JobService.php';
require_once __DIR__ . '/../includes/repositories/JobRepository.php';
include 'auth_check.php';

$user_id = $_SESSION['user_id'];

// 1. Xác thực Công ty (Bắt buộc phải có công ty mới được quản lý job)
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: company.php");
    exit();
}
$company_id = $company['id'];

// 2. POST: xóa mềm / khôi phục
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';
    $jobIdPost = (int) ($_POST['job_id'] ?? 0);
    $viewPost = ($_POST['view'] ?? 'active') === 'deleted' ? 'deleted' : 'active';

    if ($action === 'soft_delete' && csrf_validate('employer_job_delete_form', $_POST['csrf_token'] ?? '')) {
        $result = JobService::softDeleteForCompany($conn, $jobIdPost, $company_id);
        $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
        $_SESSION['swal_title'] = $result['message'];
    } elseif ($action === 'restore' && csrf_validate('employer_job_restore_form', $_POST['csrf_token'] ?? '')) {
        $result = JobService::restoreForCompany($conn, $jobIdPost, $company_id);
        $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
        $_SESSION['swal_title'] = $result['message'];
    } else {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ.';
    }

    header('Location: manage-jobs.php?view=' . $viewPost . '&page=' . (int) ($_POST['page'] ?? 1));
    exit();
}

$view = (isset($_GET['view']) && $_GET['view'] === 'deleted') ? 'deleted' : 'active';
$showDeleted = ($view === 'deleted');

// 3. CẤU HÌNH PHÂN TRANG (PAGINATION)
$limit = 10; // Số tin mỗi trang

// Lấy trang hiện tại, ép kiểu int, đảm bảo min là 1
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
if ($page < 1) $page = 1;

// Tính offset (Vị trí bắt đầu lấy dữ liệu trong DB)
$offset = ($page - 1) * $limit;

// 4. ĐẾM TỔNG SỐ TIN (Để tính tổng số trang)
$total_records = JobRepository::countByCompany($conn, $company_id, $showDeleted);

// Tính tổng số trang (dùng ceil để làm tròn lên)
$total_pages = ceil($total_records / $limit);

// Nếu trang hiện tại lớn hơn tổng số trang (trừ khi không có tin nào), quay về trang cuối
if ($total_pages > 0 && $page > $total_pages) {
    $page = $total_pages;
    $offset = ($page - 1) * $limit; // Tính lại offset
}

// 5. LẤY DANH SÁCH TIN (QUERY CHÍNH)
$jobs = JobRepository::listByCompany($conn, $company_id, $showDeleted, $limit, $offset);

// --- PHẦN 2: GIAO DIỆN HTML ---
include '../includes/header.php';

if (!jobs_schema_has_soft_delete($conn)) {
    echo '<div class="container py-5"><div class="alert alert-danger">Chưa có cột <code>deleted_at</code>. Chạy migration: '
        . '<a href="/topcv_lite/docs/migrations/migrate-phase-2b.php">migrate-phase-2b.php</a></div></div>';
    include '../includes/footer.php';
    exit;
}
?>

<div class="container py-5">
    <ul class="nav nav-tabs mb-3">
        <li class="nav-item">
            <a class="nav-link <?= !$showDeleted ? 'active' : '' ?>" href="manage-jobs.php?view=active">Đang quản lý</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $showDeleted ? 'active' : '' ?>" href="manage-jobs.php?view=deleted">Đã xóa</a>
        </li>
    </ul>

    <div class="row mb-4 align-items-end">
        <div class="col-md-8">
            <h3 class="fw-bold text-primary"><i class="fas fa-briefcase"></i> Quản lý tin tuyển dụng</h3>
            <p class="text-muted mb-0">
                Hiển thị <strong><?= count($jobs) ?></strong> trên tổng số <strong><?= $total_records ?></strong> tin
                (<?= $showDeleted ? 'đã xóa' : 'đang hoạt động' ?>).
            </p>
        </div>
        <div class="col-md-4 text-md-end">
            <a href="job-create.php" class="btn btn-success fw-bold shadow-sm rounded-pill px-4">
                <i class="fas fa-plus-circle"></i> Đăng tin mới
            </a>
        </div>
    </div>

    <div class="card border-0 shadow-sm rounded-3 overflow-hidden">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="bg-light text-dark">
                        <tr>
                            <th class="py-3 ps-4">Công việc</th>
                            <th class="py-3">Thống kê</th>
                            <th class="py-3">Trạng thái</th>
                            <th class="py-3 text-end pe-4">Hành động</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($jobs) > 0): ?>
                            <?php foreach ($jobs as $job): ?>
                                <?php 
                                    // Xử lý logic hiển thị trạng thái
                                    $is_expired = job_is_expired($job['deadline']);
                                    $deadline_ts = strtotime($job['deadline']);
                                    
                                    // URL sửa tin: Kèm theo ID và trang hiện tại (để back lại đúng chỗ)
                                    $edit_url = "job-edit.php?id=" . $job['id'] . "&ref_page=" . $page;
                                ?>
                                <tr>
                                    <td class="ps-4" style="width: 40%;">
                                        <h6 class="mb-1 fw-bold text-dark">
                                            <a href="<?= $edit_url ?>" class="text-decoration-none text-dark">
                                                <?= htmlspecialchars($job['title']) ?>
                                            </a>
                                        </h6>
                                        <small class="text-muted">
                                            <i class="far fa-clock"></i> Đăng: <?= date('d/m/Y', strtotime($job['created_at'])) ?>
                                            &bull; 
                                            <i class="far fa-calendar-times"></i> Hết hạn: <span class="<?= $is_expired ? 'text-danger fw-bold' : '' ?>"><?= date('d/m/Y', $deadline_ts) ?></span>
                                        </small>
                                        
                                        <?php if($job['status'] == 'rejected' && !empty($job['admin_note'])): ?>
                                            <div class="alert alert-danger p-2 mt-2 mb-0 small fst-italic border-0 bg-danger bg-opacity-10 text-danger">
                                                <i class="fas fa-exclamation-triangle"></i> <strong>Admin note:</strong> <?= htmlspecialchars($job['admin_note']) ?>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td>
                                        <div class="d-flex flex-column small">
                                            <span class="mb-1"><i class="fas fa-eye text-primary"></i> <?= $job['view_count'] ?> lượt xem</span>
                                            </div>
                                    </td>
                                    
                                    <td>
                                        <?php if ($is_expired): ?>
                                            <span class="badge bg-secondary rounded-pill">Hết hạn</span>
                                        <?php else: ?>
                                            <?php switch ($job['status']): 
                                                case 'approved': ?>
                                                    <span class="badge bg-success rounded-pill">Đang hiển thị</span>
                                                    <?php break; ?>
                                                <?php case 'pending': ?>
                                                    <span class="badge bg-warning text-dark rounded-pill">Chờ duyệt</span>
                                                    <?php break; ?>
                                                <?php case 'rejected': ?>
                                                    <span class="badge bg-danger rounded-pill">Bị từ chối</span>
                                                    <?php break; ?>
                                                <?php case 'hidden': ?>
                                                    <span class="badge bg-dark rounded-pill">Đang ẩn</span>
                                                    <?php break; ?>
                                                <?php default: ?>
                                                    <span class="badge bg-light text-dark border">Không rõ</span>
                                            <?php endswitch; ?>
                                        <?php endif; ?>
                                    </td>
                                    
                                    <td class="text-end pe-4">
                                        <?php if (!$showDeleted): ?>
                                            <a href="<?= $edit_url ?>" class="btn btn-outline-primary btn-sm me-1" title="Sửa tin">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <form method="POST" class="d-inline" id="del-form-<?= (int) $job['id'] ?>">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_job_delete_form')) ?>">
                                                <input type="hidden" name="action" value="soft_delete">
                                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                                <input type="hidden" name="view" value="active">
                                                <input type="hidden" name="page" value="<?= (int) $page ?>">
                                                <button type="button" class="btn btn-outline-danger btn-sm" title="Xóa tin"
                                                        onclick="confirmSoftDelete(<?= (int) $job['id'] ?>)">
                                                    <i class="fas fa-trash-alt"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <form method="POST" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_job_restore_form')) ?>">
                                                <input type="hidden" name="action" value="restore">
                                                <input type="hidden" name="job_id" value="<?= (int) $job['id'] ?>">
                                                <input type="hidden" name="view" value="deleted">
                                                <input type="hidden" name="page" value="<?= (int) $page ?>">
                                                <button type="submit" class="btn btn-outline-success btn-sm" title="Khôi phục">
                                                    <i class="fas fa-undo"></i> Khôi phục
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" class="text-center py-5">
                                    <img src="https://cdn-icons-png.flaticon.com/512/4076/4076432.png" width="80" class="opacity-50 mb-3" alt="Empty">
                                    <p class="text-muted fw-bold">Bạn chưa đăng tin tuyển dụng nào.</p>
                                    <a href="job-create.php" class="btn btn-primary btn-sm">Đăng ngay</a>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        
        <?php if ($total_pages > 1): ?>
        <div class="card-footer bg-white border-0 py-3">
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mb-0">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?view=<?= htmlspecialchars($view) ?>&page=<?= $page - 1 ?>" aria-label="Previous">
                            <span aria-hidden="true">&laquo;</span>
                        </a>
                    </li>
                    
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page == $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?view=<?= htmlspecialchars($view) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?view=<?= htmlspecialchars($view) ?>&page=<?= $page + 1 ?>" aria-label="Next">
                            <span aria-hidden="true">&raquo;</span>
                        </a>
                    </li>
                </ul>
            </nav>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function confirmSoftDelete(id) {
    Swal.fire({
        title: 'Xóa tin này?',
        text: 'Tin sẽ ẩn khỏi trang công khai. Bạn có thể khôi phục trong tab Đã xóa.',
        icon: 'warning',
        showCancelButton: true,
        confirmButtonColor: '#dc3545',
        confirmButtonText: 'Xóa'
    }).then((result) => {
        if (result.isConfirmed) {
            document.getElementById('del-form-' + id).submit();
        }
    });
}
</script>

<?php include '../includes/footer.php'; ?>