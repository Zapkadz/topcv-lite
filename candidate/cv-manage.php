<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/csrf.php';
require_once __DIR__ . '/../includes/schema_cvs.php';
require_once __DIR__ . '/../includes/cv_rules.php';
require_once __DIR__ . '/../includes/services/CvService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$userId = (int) $_SESSION['user_id'];
$schemaReady = cvs_schema_ready($conn);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $schemaReady) {
    $action = $_POST['action'] ?? '';
    $cvId = isset($_POST['cv_id']) ? (int) $_POST['cv_id'] : 0;

    if ($action === 'set_primary') {
        if (!csrf_validate('candidate_cv_primary_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        } elseif ($cvId <= 0) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'CV không hợp lệ.';
        } else {
            $result = CvService::setPrimaryForUser($conn, $userId, $cvId);
            $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
            $_SESSION['swal_title'] = $result['message'];
        }
    } elseif ($action === 'delete') {
        if (!csrf_validate('candidate_cv_delete_form', $_POST['csrf_token'] ?? '')) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'Phiên làm việc không hợp lệ, vui lòng thử lại.';
        } elseif ($cvId <= 0) {
            $_SESSION['swal_icon'] = 'error';
            $_SESSION['swal_title'] = 'CV không hợp lệ.';
        } else {
            $result = CvService::deleteForUser($conn, $userId, $cvId);
            $_SESSION['swal_icon'] = $result['ok'] ? 'success' : 'error';
            $_SESSION['swal_title'] = $result['message'];
        }
    }

    header('Location: cv-manage.php');
    exit();
}

$cvs = $schemaReady ? CvService::listForUser($conn, $userId) : [];

include '../includes/header.php';
?>

<div class="container py-5">
    <div class="d-flex flex-wrap justify-content-between align-items-center mb-4 gap-2">
        <div>
            <h3 class="fw-bold mb-1"><i class="fas fa-file-alt text-success"></i> Quản lý CV online</h3>
            <p class="text-muted mb-0">Tạo nhiều bản CV, chọn bản mặc định khi ứng tuyển (tích hợp apply ở CV-C).</p>
        </div>
        <?php if ($schemaReady): ?>
            <a href="cv-builder.php" class="btn btn-success fw-bold">
                <i class="fas fa-plus"></i> Tạo CV mới
            </a>
        <?php endif; ?>
    </div>

    <?php if (!$schemaReady): ?>
        <?= cvs_schema_migration_hint_html() ?>
    <?php elseif (count($cvs) === 0): ?>
        <div class="card border-0 shadow-sm text-center py-5">
            <div class="card-body">
                <i class="fas fa-file-alt fa-3x text-muted mb-3"></i>
                <h5 class="fw-bold">Chưa có CV online</h5>
                <p class="text-muted">Tạo CV đầu tiên để nhà tuyển dụng đọc trực tiếp trên web.</p>
                <a href="cv-builder.php" class="btn btn-success fw-bold">Tạo CV đầu tiên</a>
            </div>
        </div>
    <?php else: ?>
        <div class="card border-0 shadow-sm">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th>Tên CV</th>
                            <th>Cập nhật</th>
                            <th>Hoàn thiện</th>
                            <th>Mặc định</th>
                            <th class="text-end">Thao tác</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($cvs as $cv): ?>
                            <?php
                            $cvId = (int) $cv['id'];
                            $isPrimary = (int) ($cv['is_primary'] ?? 0) === 1;
                            ?>
                            <tr>
                                <td>
                                    <span class="fw-bold"><?= htmlspecialchars((string) $cv['title']) ?></span>
                                    <?php if (trim((string) ($cv['full_name'] ?? '')) !== ''): ?>
                                        <br><small class="text-muted"><?= htmlspecialchars((string) $cv['full_name']) ?></small>
                                    <?php endif; ?>
                                </td>
                                <td><?= htmlspecialchars(cv_format_updated_at($cv['updated_at'] ?? null)) ?></td>
                                <td>
                                    <div class="progress" style="height: 8px; min-width: 80px;">
                                        <div class="progress-bar bg-success" style="width: <?= (int) ($cv['completion_percent'] ?? 0) ?>%"></div>
                                    </div>
                                    <small class="text-muted"><?= (int) ($cv['completion_percent'] ?? 0) ?>%</small>
                                </td>
                                <td>
                                    <?php if ($isPrimary): ?>
                                        <span class="badge bg-success"><i class="fas fa-star"></i> Mặc định</span>
                                    <?php else: ?>
                                        <form method="POST" class="d-inline">
                                            <input type="hidden" name="action" value="set_primary">
                                            <input type="hidden" name="cv_id" value="<?= $cvId ?>">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_primary_form')) ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-secondary">Đặt mặc định</button>
                                        </form>
                                    <?php endif; ?>
                                </td>
                                <td class="text-end text-nowrap">
                                    <a href="cv-preview.php?id=<?= $cvId ?>" class="btn btn-sm btn-outline-primary">Xem</a>
                                    <a href="cv-builder.php?id=<?= $cvId ?>" class="btn btn-sm btn-outline-success">Sửa</a>
                                    <form method="POST" class="d-inline js-cv-delete-form">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="cv_id" value="<?= $cvId ?>">
                                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars(csrf_token('candidate_cv_delete_form')) ?>">
                                        <button type="submit" class="btn btn-sm btn-outline-danger"
                                            data-cv-title="<?= htmlspecialchars((string) $cv['title'], ENT_QUOTES) ?>"
                                            data-is-primary="<?= $isPrimary ? '1' : '0' ?>">
                                            Xóa
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
</div>

<script>
document.querySelectorAll('.js-cv-delete-form').forEach(function (form) {
    form.addEventListener('submit', function (e) {
        e.preventDefault();
        var btn = form.querySelector('button[type="submit"]');
        var title = btn.getAttribute('data-cv-title') || 'CV này';
        var isPrimary = btn.getAttribute('data-is-primary') === '1';
        var extra = isPrimary
            ? ' Đây là CV mặc định; hệ thống sẽ tự chọn CV khác làm mặc định nếu còn.'
            : '';
        Swal.fire({
            icon: 'warning',
            title: 'Xóa CV?',
            text: 'Bạn có chắc muốn xóa "' + title + '"?' + extra,
            showCancelButton: true,
            confirmButtonColor: '#dc3545',
            cancelButtonColor: '#6c757d',
            confirmButtonText: 'Xóa',
            cancelButtonText: 'Hủy'
        }).then(function (result) {
            if (result.isConfirmed) {
                form.submit();
            }
        });
    });
});
</script>

<?php include '../includes/footer.php'; ?>
