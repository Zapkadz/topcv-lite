<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
require_once __DIR__ . '/../config/db.php';
require_once __DIR__ . '/../includes/job_rules.php';
require_once __DIR__ . '/../includes/schema_saved_jobs.php';
require_once __DIR__ . '/../includes/services/SavedJobService.php';

if (!isset($_SESSION['user_id']) || ($_SESSION['role'] ?? '') !== 'candidate') {
    header('Location: ../index.php');
    exit();
}

$user_id = (int) $_SESSION['user_id'];
$tab = $_GET['tab'] ?? 'applications';
if (!in_array($tab, ['applications', 'saved'], true)) {
    $tab = 'applications';
}

$limit = 10;
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
if ($page < 1) {
    $page = 1;
}

$my_jobs = [];
$saved_jobs = [];
$total_records = 0;
$total_pages = 0;

if ($tab === 'applications') {
    $sql_count = 'SELECT COUNT(*)
                  FROM applications app
                  JOIN candidates cand ON app.candidate_id = cand.id
                  WHERE cand.user_id = ?';
    $stmt_count = $conn->prepare($sql_count);
    $stmt_count->execute([$user_id]);
    $total_records = (int) $stmt_count->fetchColumn();
    $total_pages = (int) ceil($total_records / $limit);
    $offset = ($page - 1) * $limit;

    $sql = "SELECT app.*, j.title, c.name AS company_name, j.id AS job_id
            FROM applications app
            JOIN jobs j ON app.job_id = j.id
            JOIN companies c ON j.company_id = c.id
            JOIN candidates cand ON app.candidate_id = cand.id
            WHERE cand.user_id = ?
            ORDER BY app.created_at DESC
            LIMIT {$limit} OFFSET {$offset}";
    $stmt = $conn->prepare($sql);
    $stmt->execute([$user_id]);
    $my_jobs = $stmt->fetchAll(PDO::FETCH_ASSOC);
} elseif (saved_jobs_schema_ready($conn)) {
    $result = SavedJobService::listForUser($conn, $user_id, $page, $limit);
    $saved_jobs = $result['rows'];
    $total_records = $result['total'];
    $total_pages = (int) ceil($total_records / $limit);
}

include '../includes/header.php';
?>

<div class="container py-5">
    <?php if ($tab === 'saved' && !saved_jobs_schema_ready($conn)): ?>
        <?= saved_jobs_schema_migration_hint_html() ?>
    <?php endif; ?>

    <ul class="nav nav-pills mb-4">
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'applications' ? 'active' : '' ?>"
               href="my-jobs.php?tab=applications">Đã ứng tuyển</a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $tab === 'saved' ? 'active' : '' ?>"
               href="my-jobs.php?tab=saved">Đã lưu</a>
        </li>
    </ul>

    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="mb-0 fw-bold">
                <?php if ($tab === 'saved'): ?>
                    <i class="fas fa-bookmark text-warning"></i> Việc làm đã lưu
                <?php else: ?>
                    <i class="fas fa-file-contract text-success"></i> Lịch sử ứng tuyển
                <?php endif; ?>
            </h5>
            <span class="badge bg-light text-dark border">Tổng: <?= $total_records ?></span>
        </div>
        <div class="card-body">
            <?php if ($tab === 'applications'): ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Công việc</th>
                                <th>Thời gian nộp</th>
                                <th>CV đã nộp</th>
                                <th>Trạng thái</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($my_jobs as $row): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="../job-detail.php?id=<?= (int) $row['job_id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </a>
                                    </strong><br>
                                    <span class="text-muted small"><?= htmlspecialchars($row['company_name']) ?></span>
                                </td>
                                <td><?= date('H:i - d/m/Y', strtotime($row['created_at'])) ?></td>
                                <td>
                                    <?php
                                    $hasJsonCv = !empty($row['cv_snapshot_json']);
                                    $fileCv = trim((string) ($row['cv_snapshot'] ?? ''));
                                    $hasFileCv = $fileCv !== '';
                                    ?>
                                    <?php if ($hasJsonCv): ?>
                                        <a href="application-cv-snapshot.php?app_id=<?= (int) $row['id'] ?>"
                                            target="_blank" rel="noopener"
                                            class="text-primary fw-bold text-decoration-none">
                                            <i class="fas fa-id-card"></i> Xem CV online
                                        </a>
                                    <?php elseif ($hasFileCv): ?>
                                        <a href="../<?= htmlspecialchars($fileCv) ?>"
                                            target="_blank" rel="noopener"
                                            class="text-danger fw-bold text-decoration-none">
                                            <i class="fas fa-file-pdf"></i> Xem CV (file)
                                        </a>
                                    <?php else: ?>
                                        <span class="text-muted small">—</span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <?php
                                    if ($row['status'] === 'pending') {
                                        echo '<span class="badge bg-warning text-dark">Chờ duyệt</span>';
                                    } elseif ($row['status'] === 'viewed') {
                                        echo '<span class="badge bg-info">NTD đã xem</span>';
                                    } elseif ($row['status'] === 'interview') {
                                        echo '<span class="badge bg-success">Mời phỏng vấn</span>';
                                    } elseif ($row['status'] === 'rejected') {
                                        echo '<span class="badge bg-secondary">Từ chối</span>';
                                    }
                                    ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($my_jobs) === 0): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">Bạn chưa ứng tuyển công việc nào.</p>
                        <a href="../index.php" class="btn btn-success">Tìm việc ngay</a>
                    </div>
                <?php endif; ?>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead class="table-light">
                            <tr>
                                <th>Công việc</th>
                                <th>Ngày lưu</th>
                                <th>Hạn nộp</th>
                                <th>Trạng thái tin</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($saved_jobs as $row): ?>
                            <tr>
                                <td>
                                    <strong>
                                        <a href="../job-detail.php?id=<?= (int) $row['id'] ?>" class="text-decoration-none text-dark">
                                            <?= htmlspecialchars($row['title']) ?>
                                        </a>
                                    </strong><br>
                                    <span class="text-muted small"><?= htmlspecialchars($row['company_name']) ?></span>
                                </td>
                                <td><?= date('d/m/Y H:i', strtotime($row['saved_at'])) ?></td>
                                <td><?= !empty($row['deadline']) ? date('d/m/Y', strtotime($row['deadline'])) : '—' ?></td>
                                <td><?= job_saved_listing_badge_html($row) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
                <?php if (count($saved_jobs) === 0 && saved_jobs_schema_ready($conn)): ?>
                    <div class="text-center py-5">
                        <p class="text-muted">Bạn chưa lưu tin tuyển dụng nào.</p>
                        <a href="../jobs.php" class="btn btn-success">Khám phá việc làm</a>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <?php if ($total_pages > 1): ?>
            <nav aria-label="Page navigation" class="mt-4">
                <ul class="pagination justify-content-center">
                    <li class="page-item <?= ($page <= 1) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?tab=<?= urlencode($tab) ?>&page=<?= $page - 1 ?>">Trước</a>
                    </li>
                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                        <li class="page-item <?= ($page === $i) ? 'active' : '' ?>">
                            <a class="page-link" href="?tab=<?= urlencode($tab) ?>&page=<?= $i ?>"><?= $i ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($page >= $total_pages) ? 'disabled' : '' ?>">
                        <a class="page-link" href="?tab=<?= urlencode($tab) ?>&page=<?= $page + 1 ?>">Sau</a>
                    </li>
                </ul>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
