<?php
// --- PHẦN 1: LOGIC PHP (Chạy trước khi xuất HTML) ---
if (session_status() === PHP_SESSION_NONE) session_start();
include '../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
include 'auth_check.php';

$user_id = $_SESSION['user_id'];

// 1. Lấy ID công ty
$stmt = $conn->prepare("SELECT id FROM companies WHERE user_id = ?");
$stmt->execute([$user_id]);
$company = $stmt->fetch();

if (!$company) {
    header("Location: company.php");
    exit();
}
$company_id = $company['id'];

// 2. XỬ LÝ POST: Đổi trạng thái hồ sơ
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['app_id'])) {
    if (!csrf_validate('employer_applicant_status_form', $_POST['csrf_token'] ?? '')) {
        $_SESSION['swal_icon'] = 'error';
        $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        header('Location: applicants.php');
        exit();
    }
    $status = $_POST['status'];
    $app_id = $_POST['app_id'];

    $stmt = $conn->prepare("UPDATE applications SET status = ? WHERE id = ?");
    $stmt->execute([$status, $app_id]);

    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Đã cập nhật trạng thái hồ sơ!';

    header("Location: applicants.php");
    exit();
}

// --- PHẦN 2: GIAO DIỆN HTML ---
include '../includes/header.php';

// 3. SỬA LỖI SQL: Join qua bảng candidates để lấy đúng thông tin User ứng viên
$sql = "SELECT app.id as app_id, app.created_at as time_apply, app.status, app.cv_snapshot, app.cover_letter,
               u.fullname, u.email, u.phone, 
               j.title as job_title 
        FROM applications app
        JOIN jobs j ON app.job_id = j.id
        JOIN candidates cand ON app.candidate_id = cand.id
        JOIN users u ON cand.user_id = u.id
        WHERE j.company_id = ?
        ORDER BY app.created_at DESC";

$stmt = $conn->prepare($sql);
$stmt->execute([$company_id]);
$apps = $stmt->fetchAll();
?>

<div class="container py-5">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h3 class="fw-bold text-primary"><i class="fas fa-user-tie"></i> Quản lý hồ sơ ứng viên</h3>
            <p class="text-muted mb-0">Danh sách các ứng viên đã nộp hồ sơ vào công ty của bạn.</p>
        </div>
        <a href="dashboard.php" class="btn btn-outline-secondary"><i class="fas fa-arrow-left"></i> Quay lại</a>
    </div>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <?php if (count($apps) > 0): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="bg-light">
                            <tr>
                                <th class="ps-4">Ứng viên</th>
                                <th>Vị trí ứng tuyển</th>
                                <th>Hồ sơ</th>
                                <th>Ngày nộp</th>
                                <th>Trạng thái</th>
                                <th class="text-end pe-4">Hành động</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($apps as $row): ?>
                                <tr>
                                    <td class="ps-4">
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($row['fullname']) ?></div>

                                        <div class="small text-muted">
                                            <i class="fas fa-envelope fa-fw"></i> <?= htmlspecialchars($row['email']) ?>
                                        </div>

                                        <div class="small text-muted">
                                            <i class="fas fa-phone fa-fw"></i> <?= htmlspecialchars($row['phone'] ?? 'Chưa cập nhật') ?>
                                        </div>
                                    </td>
                                    <td>
                                        <span class="badge bg-soft-primary text-primary border border-primary-subtle px-2 py-1">
                                            <?= htmlspecialchars($row['job_title']) ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div class="d-flex gap-1">
                                            <a href="../<?= $row['cv_snapshot'] ?>" target="_blank" class="btn btn-sm btn-outline-danger shadow-sm">
                                                <i class="fas fa-file-pdf"></i> CV
                                            </a>
                                            <?php if ($row['cover_letter']): ?>
                                                <button class="btn btn-sm btn-outline-info shadow-sm"
                                                    onclick="showCoverLetter(`<?= htmlspecialchars($row['fullname']) ?>`, `<?= htmlspecialchars(addslashes($row['cover_letter'])) ?>`)">
                                                    <i class="fas fa-envelope-open-text"></i> Thư
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td><span class="text-muted small"><?= date('H:i d/m/Y', strtotime($row['time_apply'])) ?></span></td>
                                    <td>
                                        <?php
                                        switch ($row['status']) {
                                            case 'pending':
                                                echo '<span class="badge rounded-pill bg-warning text-dark">Chờ duyệt</span>';
                                                break;
                                            case 'viewed':
                                                echo '<span class="badge rounded-pill bg-info">Đã xem</span>';
                                                break;
                                            case 'interview':
                                                echo '<span class="badge rounded-pill bg-success">Hẹn PV</span>';
                                                break;
                                            case 'rejected':
                                                echo '<span class="badge rounded-pill bg-secondary">Từ chối</span>';
                                                break;
                                        }
                                        ?>
                                    </td>
                                    <td class="text-end pe-4">
                                        <button class="btn btn-sm btn-primary px-3 rounded-pill"
                                            data-bs-toggle="modal" data-bs-target="#statusModal"
                                            onclick="setModalData(<?= $row['app_id'] ?>, '<?= $row['status'] ?>')">
                                            <i class="fas fa-cog me-1"></i> Xử lý
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php else: ?>
                <div class="text-center py-5 text-muted">
                    <img src="https://cdn-icons-png.flaticon.com/512/7486/7486754.png" width="80" class="opacity-25 mb-3">
                    <p>Chưa có hồ sơ ứng tuyển nào được gửi đến.</p>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<div class="modal fade" id="statusModal" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <form method="POST" class="modal-content shadow-lg border-0">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('employer_applicant_status_form')) ?>">
            <div class="modal-header">
                <h5 class="modal-title fw-bold">Cập nhật trạng thái ứng viên</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body py-4">
                <input type="hidden" name="app_id" id="modal_app_id">
                <div class="mb-3">
                    <label class="form-label fw-bold">Trạng thái mới:</label>
                    <select name="status" id="modal_status" class="form-select form-select-lg">
                        <option value="pending">Chờ duyệt</option>
                        <option value="viewed">Đã xem</option>
                        <option value="interview">Mời phỏng vấn</option>
                        <option value="rejected">Từ chối hồ sơ</option>
                    </select>
                </div>
                <div class="alert alert-info border-0 small mb-0">
                    <i class="fas fa-info-circle me-1"></i> Hệ thống sẽ ghi nhận thay đổi này và hiển thị cho ứng viên.
                </div>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary px-4" data-bs-dismiss="modal">Đóng</button>
                <button type="submit" class="btn btn-success px-4 fw-bold">Lưu thay đổi</button>
            </div>
        </form>
    </div>
</div>

<script>
    function setModalData(id, status) {
        document.getElementById('modal_app_id').value = id;
        document.getElementById('modal_status').value = status;
    }

    function showCoverLetter(name, text) {
        Swal.fire({
            title: '<h5 class="fw-bold">Thư giới thiệu từ ' + name + '</h5>',
            html: '<div class="text-start p-2 border rounded bg-light" style="font-size: 0.95rem; line-height: 1.6;">' + text + '</div>',
            icon: 'info',
            confirmButtonText: 'Đóng',
            confirmButtonColor: '#0d6efd'
        });
    }
</script>

<style>
    .bg-soft-primary {
        background-color: #e7f1ff;
    }

    .table thead th {
        font-weight: 600;
        font-size: 0.85rem;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
</style>

<?php include '../includes/footer.php'; ?>