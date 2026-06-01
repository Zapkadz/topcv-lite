<?php
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/repositories/UserRepository.php';
require_once __DIR__ . '/../includes/services/UserModerationService.php';
require_once __DIR__ . '/../includes/user_status.php';
require_once __DIR__ . '/../includes/schema_users.php';
include 'includes/header.php';

if (!users_schema_has_phase2a($conn)) {
    echo users_schema_migration_hint_html();
    include 'includes/footer.php';
    exit;
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'approve_employer') {
        if (!csrf_validate('admin_approve_employer_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        } else {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $adminId = (int) ($_SESSION['user_id'] ?? 0);
            if (UserModerationService::approveEmployer($conn, $userId, $adminId)) {
                $_SESSION['swal_icon'] = 'success';
                $_SESSION['swal_title'] = 'Đã duyệt Nhà tuyển dụng!';
            } else {
                $_SESSION['swal_icon'] = 'error';
                $_SESSION['swal_title'] = 'Không thể duyệt tài khoản này.';
            }
        }
        header('Location: users.php');
        exit();
    }

    if ($action === 'reject_employer') {
        if (!csrf_validate('admin_reject_employer_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        } else {
            $userId = (int) ($_POST['user_id'] ?? 0);
            $adminId = (int) ($_SESSION['user_id'] ?? 0);
            if (UserModerationService::rejectEmployer($conn, $userId, $adminId)) {
                $_SESSION['swal_icon'] = 'success';
                $_SESSION['swal_title'] = 'Đã từ chối tài khoản Nhà tuyển dụng.';
            } else {
                $_SESSION['swal_icon'] = 'error';
                $_SESSION['swal_title'] = 'Không thể từ chối tài khoản này.';
            }
        }
        header('Location: users.php');
        exit();
    }
}

$users_pending = UserRepository::listPendingEmployers($conn);
$users_active = UserRepository::listAllExceptPendingEmployers($conn);
?>

<h3 class="mb-4 fw-bold text-success">Quản lý Người dùng</h3>

<?php if (count($users_pending) > 0): ?>
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
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_approve_employer_form')) ?>">
                            <input type="hidden" name="action" value="approve_employer">
                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                            <button type="button" onclick="confirmApprove(this)" class="btn btn-success btn-sm fw-bold">
                                <i class="fas fa-check-circle"></i> Duyệt
                            </button>
                        </form>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('admin_reject_employer_form')) ?>">
                            <input type="hidden" name="action" value="reject_employer">
                            <input type="hidden" name="user_id" value="<?= (int) $u['id'] ?>">
                            <button type="button" onclick="confirmReject(this)" class="btn btn-danger btn-sm fw-bold">
                                <i class="fas fa-times-circle"></i> Từ chối
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
                    <th>Tài khoản</th>
                    <th>Duyệt NTD</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($users_active as $u): ?>
                <tr>
                    <td><?= (int) $u['id'] ?></td>
                    <td><?= htmlspecialchars($u['fullname']) ?></td>
                    <td><?= htmlspecialchars($u['email']) ?></td>
                    <td>
                        <?php
                        if ($u['role'] === 'admin') {
                            echo '<span class="badge bg-danger">Admin</span>';
                        } elseif ($u['role'] === 'employer') {
                            echo '<span class="badge bg-primary">Nhà tuyển dụng</span>';
                        } else {
                            echo '<span class="badge bg-secondary">Ứng viên</span>';
                        }
                        ?>
                    </td>
                    <td><?= user_account_status_badge_html($u) ?></td>
                    <td><?= user_employer_approval_badge_html($u) ?></td>
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
            text: 'Người này sẽ được phép đăng tin tuyển dụng.',
            icon: 'info',
            showCancelButton: true,
            confirmButtonColor: '#198754',
            confirmButtonText: 'Đồng ý duyệt'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        });
    }

    function confirmReject(btn) {
        Swal.fire({
            title: 'Từ chối tài khoản NTD?',
            text: 'Người này sẽ không đăng nhập được với vai trò Nhà tuyển dụng.',
            icon: 'warning',
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            confirmButtonText: 'Từ chối'
        }).then((result) => {
            if (result.isConfirmed) {
                btn.closest('form').submit();
            }
        });
    }
</script>

<?php include 'includes/footer.php'; ?>
