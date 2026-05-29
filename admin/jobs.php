<?php
include 'includes/header.php';

// --- XỬ LÝ POST (Duyệt hoặc Từ chối) ---
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $job_id = $_POST['job_id'];
    $action = $_POST['action'];

    if ($action == 'approve') {
        $stmt = $conn->prepare("UPDATE jobs SET status = 'approved', admin_note = NULL WHERE id = ?");
        $stmt->execute([$job_id]);
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Đã duyệt tin đăng!';
    } elseif ($action == 'reject') {
        $note = $_POST['admin_note'];
        $stmt = $conn->prepare("UPDATE jobs SET status = 'rejected', admin_note = ? WHERE id = ?");
        $stmt->execute([$note, $job_id]);
        $_SESSION['swal_icon'] = 'success';
        $_SESSION['swal_title'] = 'Đã từ chối tin đăng!';
    }

    header("Location: jobs.php");
    exit();
}

// --- XỬ LÝ XÓA VĨNH VIỄN ---
if (isset($_GET['delete'])) {
    $stmt = $conn->prepare("DELETE FROM jobs WHERE id = ?");
    $stmt->execute([$_GET['delete']]);
    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Đã xóa vĩnh viễn tin đăng!';
    header("Location: jobs.php");
    exit();
}

// Lấy danh sách tin (JOIN với Companies để biết cty nào đăng)
$sql = "SELECT j.*, c.name as company_name, c.logo 
        FROM jobs j 
        JOIN companies c ON j.company_id = c.id 
        ORDER BY j.created_at DESC";
$all_jobs = $conn->query($sql)->fetchAll();

// Tách ra 2 mảng để hiển thị tab
$pending_jobs = array_filter($all_jobs, function ($j) {
    return $j['status'] == 'pending';
});
?>

<h3 class="mb-4 fw-bold text-success">Kiểm duyệt tin tuyển dụng</h3>

<ul class="nav nav-pills mb-3" id="pills-tab" role="tablist">
    <li class="nav-item" role="presentation">
        <button class="nav-link active fw-bold" id="pills-pending-tab" data-bs-toggle="pill" data-bs-target="#pills-pending" type="button">
            Chờ duyệt <span class="badge bg-danger rounded-pill"><?= count($pending_jobs) ?></span>
        </button>
    </li>
    <li class="nav-item" role="presentation">
        <button class="nav-link fw-bold" id="pills-all-tab" data-bs-toggle="pill" data-bs-target="#pills-all" type="button">
            Tất cả tin
        </button>
    </li>
</ul>

<div class="tab-content" id="pills-tabContent">

    <div class="tab-pane fade show active" id="pills-pending">
        <?php if (count($pending_jobs) > 0): ?>
            <?php foreach ($pending_jobs as $job): ?>
                <div class="card mb-3 border-0 shadow-sm">
                    <div class="card-body">
                        <div class="d-flex align-items-center justify-content-between">
                            <div class="d-flex align-items-center">
                                <img src="../<?= $job['logo'] ?? 'uploads/default.png' ?>" width="60" height="60" class="rounded me-3 object-fit-cover">
                                <div>
                                    <h5 class="mb-1 fw-bold text-success"><?= htmlspecialchars($job['title']) ?></h5>
                                    <p class="mb-0 text-muted small">
                                        <i class="fas fa-building"></i> <?= htmlspecialchars($job['company_name']) ?> |
                                        <i class="fas fa-money-bill-wave"></i> <?= htmlspecialchars($job['salary_range']) ?>
                                    </p>
                                    <p class="mb-0 text-muted small">
                                        Đăng ngày: <?= date('d/m/Y H:i', strtotime($job['created_at'])) ?>
                                    </p>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-info btn-sm text-white me-2" onclick="viewJobDetail(<?= $job['id'] ?>)" title="Xem chi tiết">
                                    <i class="fas fa-eye"></i>
                                </button>
                                <button onclick="quickApprove(<?= $job['id'] ?>)" class="btn btn-success btn-sm me-2">
                                    <i class="fas fa-check"></i> Duyệt ngay
                                </button>
                                <button class="btn btn-danger btn-sm btn-reject" data-id="<?= $job['id'] ?>" data-bs-toggle="modal" data-bs-target="#rejectModal">
                                    <i class="fas fa-times"></i> Từ chối
                                </button>
                            </div>
                        </div>
                        <div class="mt-3 bg-light p-3 rounded">
                            <strong>Mô tả:</strong> <br>
                            <?= nl2br(htmlspecialchars(substr($job['description'], 0, 300))) ?>...
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div class="alert alert-success">Tuyệt vời! Không có tin nào cần duyệt.</div>
        <?php endif; ?>
    </div>

    <div class="tab-pane fade" id="pills-all">
        <table class="table table-hover">
            <thead>
                <tr>
                    <th>Tiêu đề</th>
                    <th>Công ty</th>
                    <th>Ngày đăng</th>
                    <th>Trạng thái</th>
                    <th>Ghi chú Admin</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($all_jobs as $job): ?>
                    <tr>
                        <td><strong><?= htmlspecialchars($job['title']) ?></strong></td>
                        <td><?= htmlspecialchars($job['company_name']) ?></td>
                        <td><?= date('d/m/Y', strtotime($job['created_at'])) ?></td>
                        <td>
                            <?php
                            if ($job['status'] == 'approved') echo '<span class="badge bg-success">Đang hiện</span>';
                            elseif ($job['status'] == 'pending') echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                            elseif ($job['status'] == 'rejected') echo '<span class="badge bg-danger">Đã từ chối</span>';
                            elseif ($job['status'] == 'hidden') echo '<span class="badge bg-secondary">Đã ẩn</span>';
                            ?>
                        </td>
                        <td class="text-danger small fst-italic"><?= htmlspecialchars($job['admin_note'] ?? '') ?></td>
                        <td>
                            <button onclick="viewJobDetail(<?= $job['id'] ?>)" class="btn btn-sm btn-info text-white me-1">
                                <i class="fas fa-eye"></i>
                            </button>
                            <button onclick="confirmDelete('jobs.php?delete=<?= $job['id'] ?>')" class="btn btn-sm btn-outline-danger">
                                <i class="fas fa-trash"></i>
                            </button>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<form id="approveForm" method="POST" style="display:none;">
    <input type="hidden" name="action" value="approve">
    <input type="hidden" name="job_id" id="approve_job_id">
</form>

<div class="modal fade" id="rejectModal" tabindex="-1">
    <div class="modal-dialog">
        <form method="POST" class="modal-content">
            <div class="modal-header bg-danger text-white">
                <h5 class="modal-title">Từ chối tin tuyển dụng</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <input type="hidden" name="action" value="reject">
                <input type="hidden" name="job_id" id="reject_job_id">

                <div class="mb-3">
                    <label class="form-label fw-bold">Lý do từ chối / Yêu cầu sửa:</label>
                    <textarea name="admin_note" class="form-control" rows="4" placeholder="VD: Tin tuyển dụng thiếu địa chỉ cụ thể, vui lòng cập nhật..." required></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Hủy</button>
                <button type="submit" class="btn btn-danger">Xác nhận từ chối</button>
            </div>
        </form>
    </div>
</div>
<div class="modal fade" id="viewJobModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-success text-white py-3">
                <h5 class="modal-title fw-bold"><i class="fas fa-file-invoice me-2"></i> CHI TIẾT TIN TUYỂN DỤNG</h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4 bg-light">
                <div class="row">
                    <div class="col-md-4">
                        <div class="card border-0 shadow-sm rounded-3 mb-3">
                            <div class="card-body p-3">
                                <div class="text-center mb-3">
                                    <img id="m-logo" src="" class="rounded border p-2 mb-2" style="width: 100px; height: 100px; object-fit: contain;">
                                    <h6 id="m-company" class="fw-bold text-success mb-0"></h6>
                                </div>
                                <hr>
                                <ul class="list-unstyled small mb-0">
                                    <li class="mb-2"><i class="fas fa-money-bill-wave text-muted me-2"></i><strong>Lương:</strong> <span id="m-salary"></span></li>
                                    <li class="mb-2"><i class="fas fa-users text-muted me-2"></i><strong>Số lượng:</strong> <span id="m-quantity"></span></li>
                                    <li class="mb-2"><i class="fas fa-clock text-muted me-2"></i><strong>Hình thức:</strong> <span id="m-type"></span></li>
                                    <li class="mb-2"><i class="fas fa-user-tie text-muted me-2"></i><strong>Cấp bậc:</strong> <span id="m-level"></span></li>
                                    <li class="mb-2"><i class="fas fa-star text-muted me-2"></i><strong>Kinh nghiệm:</strong> <span id="m-exp"></span></li>
                                    <li class="mb-2"><i class="fas fa-calendar-times text-muted me-2"></i><strong>Hạn nộp:</strong> <span id="m-deadline"></span></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-8">
                        <div class="card border-0 shadow-sm rounded-3">
                            <div class="card-body p-4">
                                <h4 id="m-title" class="fw-bold text-dark mb-4"></h4>

                                <h6 class="fw-bold text-success border-bottom pb-2"><i class="fas fa-align-left me-2"></i>Mô tả công việc</h6>
                                <div id="m-description" class="mb-4 text-secondary" style="line-height: 1.6;"></div>

                                <h6 class="fw-bold text-success border-bottom pb-2"><i class="fas fa-list-ul me-2"></i>Yêu cầu ứng viên</h6>
                                <div id="m-requirements" class="mb-4 text-secondary" style="line-height: 1.6;"></div>

                                <h6 class="fw-bold text-success border-bottom pb-2"><i class="fas fa-gift me-2"></i>Quyền lợi</h6>
                                <div id="m-benefits" class="text-secondary" style="line-height: 1.6;"></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-footer bg-white border-top-0">
                <button type="button" class="btn btn-secondary px-4 rounded-pill" data-bs-dismiss="modal">Đóng</button>
            </div>
        </div>
    </div>
</div>
<script>
    // Xử lý nút Duyệt nhanh
    function quickApprove(id) {
        Swal.fire({
            title: 'Duyệt tin này?',
            text: "Tin sẽ được hiển thị công khai ngay lập tức.",
            icon: 'question',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Duyệt ngay'
        }).then((result) => {
            if (result.isConfirmed) {
                document.getElementById('approve_job_id').value = id;
                document.getElementById('approveForm').submit();
            }
        })
    }

    // Truyền ID vào Modal Từ chối
    document.addEventListener("DOMContentLoaded", function() {
        const rejectBtns = document.querySelectorAll('.btn-reject');
        rejectBtns.forEach(btn => {
            btn.addEventListener('click', function() {
                document.getElementById('reject_job_id').value = this.dataset.id;
            });
        });
    });

    function viewJobDetail(id) {
        fetch(`get-job-json.php?id=${id}`)
            .then(response => {
                if (!response.ok) throw new Error('404/500 Not Found');
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    Swal.fire('Thông báo', data.error, 'warning');
                    return;
                }

                // Hàm gán text/html an toàn
                const safeSet = (id, value, isHtml = false) => {
                    const el = document.getElementById(id);
                    if (el) {
                        if (isHtml) el.innerHTML = value || 'Chưa cập nhật';
                        else el.innerText = value || 'Chưa cập nhật';
                    }
                };

                // Gán dữ liệu cơ bản
                safeSet('m-title', data.title);
                safeSet('m-company', data.company_name);
                safeSet('m-salary', data.salary_range);
                safeSet('m-quantity', data.quantity + ' người');
                safeSet('m-type', data.job_type);
                safeSet('m-level', data.job_level);
                safeSet('m-exp', data.experience);
                safeSet('m-gender', data.gender); // Hiển thị thêm giới tính
                safeSet('m-address', data.address); // Hiển thị thêm địa điểm

                if (data.deadline) {
                    safeSet('m-deadline', new Date(data.deadline).toLocaleDateString('vi-VN'));
                }

                // Gán các phần nội dung dài (HTML từ CKEditor)
                safeSet('m-description', data.description, true);
                safeSet('m-requirements', data.requirements, true);
                safeSet('m-benefits', data.benefits, true);

                // Xử lý logo
                const imgEl = document.getElementById('m-logo');
                if (imgEl) {
                    imgEl.src = data.logo ? '../' + data.logo : '../uploads/default-logo.png';
                }

                // Hiện Modal
                const modal = new bootstrap.Modal(document.getElementById('viewJobModal'));
                modal.show();
            })
            .catch(error => {
                console.error('Fetch error:', error);
                Swal.fire('Lỗi', 'Không thể kết nối hoặc dữ liệu lỗi!', 'error');
            });
    }
</script>

<?php include 'includes/footer.php'; ?>