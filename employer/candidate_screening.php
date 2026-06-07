<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/employer_screening_rules.php';
require_once __DIR__ . '/../includes/schema_applications_cv.php';
require_once __DIR__ . '/../includes/services/ApplicationService.php';
include 'auth_check.php';

$userId = (int) ($_SESSION['user_id'] ?? 0);

$stmt = $conn->prepare('SELECT id, name FROM companies WHERE user_id = ? LIMIT 1');
$stmt->execute([$userId]);
$company = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$company) {
    header('Location: company.php');
    exit();
}

$companyId = (int) $company['id'];
$schemaReady = applications_cv_columns_ready($conn);

$activeJobs = [];
$expiredJobs = [];
$pendingHub = 0;

if ($schemaReady) {
    $activeJobs = ApplicationService::listScreeningJobs($conn, $companyId, 'active');
    $expiredJobs = ApplicationService::listScreeningJobs($conn, $companyId, 'expired');
    $pendingHub = ApplicationService::countPendingForScreeningHub($conn, $companyId);
}

/**
 * @param list<array<string, mixed>> $jobs
 */
function employer_screening_render_jobs_table(array $jobs, string $section): void
{
    if ($jobs === []) {
        echo '<p class="text-muted mb-0 small">Không có tin nào trong nhóm này.</p>';

        return;
    }
    ?>
    <div class="table-responsive">
        <table class="table table-hover align-middle mb-0">
            <thead class="bg-light">
                <tr>
                    <th class="ps-4">Vị trí tuyển dụng</th>
                    <th>Hạn nộp</th>
                    <th class="text-center">Tổng UV</th>
                    <th class="text-center">Chờ duyệt</th>
                    <th>Trạng thái</th>
                    <th class="text-end pe-4">Hành động</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($jobs as $job): ?>
                    <?php
                    $jobId = (int) ($job['id'] ?? 0);
                    $totalApps = (int) ($job['total_apps'] ?? 0);
                    $pendingApps = (int) ($job['pending_apps'] ?? 0);
                    ?>
                    <tr>
                        <td class="ps-4">
                            <div class="fw-bold text-dark"><?= htmlspecialchars((string) ($job['title'] ?? '')) ?></div>
                        </td>
                        <td class="text-muted small"><?= employer_screening_format_deadline($job['deadline'] ?? null) ?></td>
                        <td class="text-center">
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle"><?= $totalApps ?></span>
                        </td>
                        <td class="text-center">
                            <?php if ($pendingApps > 0): ?>
                                <span class="badge bg-warning text-dark"><?= $pendingApps ?></span>
                            <?php else: ?>
                                <span class="text-muted">0</span>
                            <?php endif; ?>
                        </td>
                        <td><?= employer_screening_job_badge_html($job, $section) ?></td>
                        <td class="text-end pe-4">
                            <a href="job_candidates.php?job_id=<?= $jobId ?>" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-users"></i> Xem ứng viên
                            </a>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php
}

include '../includes/header.php';
?>

<div class="container py-5">
    <?php if (!$schemaReady): ?>
        <?= applications_cv_migration_hint_html() ?>
    <?php endif; ?>

    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 mb-4">
        <div>
            <nav aria-label="breadcrumb">
                <ol class="breadcrumb small mb-2">
                    <li class="breadcrumb-item"><a href="dashboard.php" class="text-decoration-none">Bảng tin</a></li>
                    <li class="breadcrumb-item active" aria-current="page">Sàng lọc ứng viên</li>
                </ol>
            </nav>
            <h3 class="fw-bold text-success mb-1"><i class="fas fa-user-check"></i> Sàng lọc ứng viên</h3>
            <p class="text-muted mb-0">
                Chọn tin tuyển dụng để xem và xử lý hồ sơ theo từng vị trí.
                <?php if ($schemaReady && $pendingHub > 0): ?>
                    <span class="badge bg-warning text-dark ms-1"><?= $pendingHub ?> CV chờ duyệt</span>
                <?php endif; ?>
            </p>
        </div>
        <div class="d-flex flex-wrap gap-2">
            <a href="applicants.php" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-inbox"></i> Hộp thư CV (tất cả)
            </a>
            <a href="manage-jobs.php" class="btn btn-outline-primary btn-sm">
                <i class="fas fa-briefcase"></i> Quản lý tin
            </a>
        </div>
    </div>

    <?php if ($schemaReady): ?>
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-bullhorn text-success"></i> Đang tuyển</h5>
                <p class="small text-muted mb-0 mt-1">Tin đã duyệt, còn hạn nộp — vẫn nhận hồ sơ mới.</p>
            </div>
            <div class="card-body p-0">
                <?php employer_screening_render_jobs_table($activeJobs, 'active'); ?>
            </div>
        </div>

        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold"><i class="fas fa-clock text-secondary"></i> Hết hạn — còn CV cần xử lý</h5>
                <p class="small text-muted mb-0 mt-1">Không nhận thêm hồ sơ; bạn vẫn có thể xem và cập nhật ứng viên đã nộp.</p>
            </div>
            <div class="card-body p-0">
                <?php employer_screening_render_jobs_table($expiredJobs, 'expired'); ?>
            </div>
        </div>

        <div class="alert alert-light border small mb-0">
            <i class="fas fa-robot text-muted"></i>
            <strong>AI gợi ý xếp hạng ứng viên</strong> — sẽ có trên trang chi tiết từng tin (phase EMP-B).
        </div>
    <?php endif; ?>
</div>

<?php include '../includes/footer.php'; ?>
