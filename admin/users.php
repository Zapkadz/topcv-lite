<?php
include 'includes/header.php';

// --- XỬ LÝ DUYỆT NTD ---
if (isset($_POST['action']) && $_POST['action'] == 'approve_employer') {
    $user_id = $_POST['user_id'];
    // Cập nhật status = 1 (Kích hoạt)
    $stmt = $conn->prepare("UPDATE users SET status = 1 WHERE id = ?");
    $stmt->execute([$user_id]);
    
    $_SESSION['swal_icon'] = 'success';
    $_SESSION['swal_title'] = 'Đã duyệt Nhà tuyển dụng!';
    header("Location: users.php");
    exit();
}

// Lấy danh sách Users (Chia làm 2 nhóm: Chờ duyệt và Đã hoạt động)
$users_pending = $conn->query("SELECT * FROM users WHERE role='employer' AND status=0 ORDER BY created_at DESC")->fetchAll();
$users_active  = $conn->query("SELECT * FROM users WHERE NOT (role='employer' AND status=0) ORDER BY id DESC")->fetchAll();
?>

<h3 class="mb-4 fw-bold text-success">Quản lý Người dùng</h3>

<?php if(count($users_pending) > 0): ?>
<div class="card border-warning mb-4">
    <div class="card-header bg-warning text-dark fw-bold">
        <i class="fas fa-user-clock"></i> Yêu cầu làm Nhà tuyển dụng cần duyệt (<?= count($users_pending) ?>)
    </div>
    <div class="card-body">
        <table class="table table-bordered mb-0">
            <thead class="table-light">
                <tr>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Ngày đăng ký</th>
                    <th>Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_pending as $u): ?>
                <tr>
                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td><?= date('d/m/Y H:i', strtotime($u['created_at'])) ?></td>
                    <td>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="action" value="approve_employer">
                            <input type="hidden" name="user_id" value="<?= $u['id'] ?>">
                            <button type="button" onclick="confirmApprove(this)" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-check-circle"></i> Duyệt ngay
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<div class="card">
    <div class="card-header fw-bold">Danh sách thành viên</div>
    <div class="card-body p-0">
        <table class="table table-hover mb-0">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Họ tên</th>
                    <th>Email</th>
                    <th>Vai trò</th>
                    <th>Trạng thái</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_active as $u): ?>
                <tr>
                    <td><?= $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php 
                            if($u['role']=='admin') echo '<span class="badge bg-danger">Admin</span>';
                            elseif($u['role']=='employer') echo '<span class="badge bg-primary">Nhà tuyển dụng</span>';
                            else echo '<span class="badge bg-secondary">Ứng viên</span>';
                        ?>
                    </td>
                    <td>
                        <?php if($u['status'] == 1): ?>
                            <span class="badge bg-success">Hoạt động</span>
                        <?php else: ?>
                            <span class="badge bg-dark">Đã khóa</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function confirmApprove(btn) {
        Swal.fire({
            title: 'Duyệt tài khoản này?',
            text: "Người này sẽ được phép đăng tin tuyển dụng ngay lập tức.",
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Đồng ý duyệt'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        })
    }
</script>

<?php include 'includes/footer.php'; ?>